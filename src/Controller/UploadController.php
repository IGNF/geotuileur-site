<?php

namespace App\Controller;

use App\Components\Workflow\AbstractWorkflow;
use App\Components\Workflow\IntegrationWorkflow;
use App\Components\Workflow\WorkflowRunner;
use App\Constants\StoredDataStatuses;
use App\Constants\UploadTypes;
use App\Exception\AppException;
use App\Exception\PlageApiException;
use App\Form\UploadType;
use App\Service\PlageApiService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/datastores/{datastoreId}/uploads", name="plage_upload_")
 */
class UploadController extends AbstractController
{
    /** @var PlageApiService */
    private $plageApi;

    /** @var ParameterBagInterface */
    private $params;

    public function __construct(PlageApiService $plageApi, ParameterBagInterface $params)
    {
        $this->plageApi = $plageApi;
        $this->params = $params;
    }

    /**
     * @Route("/add", name="add", methods={"GET","POST"}, options={"expose"=true})
     */
    public function add($datastoreId, Request $request)
    {
        $storedDataList = $this->plageApi->storedData->getAll($datastoreId);
        $storedDataChoices = $storedDataList; // needed for update

        $form = $this->createForm(UploadType::class, null, [
            'datastoreId' => $datastoreId,
            'storedDataChoices' => $storedDataChoices,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $fileData = $form->get('file_data')->getData();
                if (!$fileData) {
                    throw new AppException('Pas de fichier zip joint');
                }

                $formData = $form->getData();
                $uploadData = [
                    'name' => $formData['pyramid_name'],
                    'description' => $formData['pyramid_name'],
                    'type' => UploadTypes::VECTOR,
                    'srs' => $formData['srs'],
                ];

                $workflowIntProgress = (new IntegrationWorkflow())->getInitialProgress();

                $tags = [
                    'file_data' => $formData['file_data'],
                    'workflow_integration_step' => 0,
                    'workflow_integration_progress' => json_encode($workflowIntProgress),
                    'workflow_class_name' => IntegrationWorkflow::class,
                ];

                $upload = $this->plageApi->upload->add($datastoreId, $uploadData);
                $this->plageApi->upload->addTags($datastoreId, $upload['_id'], $tags);

                return $this->redirectToRoute('plage_upload_integration', ['datastoreId' => $datastoreId, 'uploadId' => $upload['_id']]);
            } catch (PlageApiException|AppException $ex) {
                $this->addFlash('error', $ex->getMessage());

                return $this->redirectToRoute('plage_upload_add', ['datastoreId' => $datastoreId]);
            }
        }

        return $this->render('pages/upload/add.html.twig', [
            'datastoreId' => $datastoreId,
            'datastore' => $this->plageApi->datastore->get($datastoreId),
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{uploadId}",
     *      name="get",
     *      methods={"GET"},
     *      options={"expose"=true}
     * )
     */
    public function getById($datastoreId, $uploadId): Response
    {
        try {
            $upload = $this->plageApi->upload->get($datastoreId, $uploadId);

            return new JsonResponse($upload);
        } catch (PlageApiException $ex) {
            return new JsonResponse($uploadId, $ex->getStatusCode());
        }
    }

    /**
     * @Route("/{uploadId}/integration", name="integration", methods={"GET"}, options={"expose"=true})
     */
    public function integration($datastoreId, $uploadId)
    {
        $upload = $this->plageApi->upload->get($datastoreId, $uploadId);

        // rediriger vers le tableau de bord si vectordb est supprimé
        if (array_key_exists('vectordb_id', $upload['tags'])) {
            $vectordb = $this->plageApi->storedData->get($datastoreId, $upload['tags']['vectordb_id']);
            if (StoredDataStatuses::DELETED == $vectordb['status']) {
                $this->addFlash('notice', sprintf('La donnée %s n\'existe plus, elle a été supprimée.', $vectordb['_id']));

                return $this->redirectToRoute('plage_datastore_view', ['datastoreId' => $datastoreId]);
            }
        }

        $upload['tags']['workflow_integration_progress'] = json_decode($upload['tags']['workflow_integration_progress'], true);

        $workflowClassName = $upload['tags']['workflow_class_name'];

        /** @var IntegrationWorkflow */
        $integrationWorkflow = new $workflowClassName();
        $workflowSteps = $integrationWorkflow->steps;

        return $this->render('pages/upload/integration.html.twig', [
            'datastoreId' => $datastoreId,
            'datastore' => $this->plageApi->datastore->get($datastoreId),
            'upload' => $upload,
            'workflow_steps' => $workflowSteps,
        ]);
    }

    /**
     * @Route("/{uploadId}/integration/progress", name="integration_progress", methods={"POST"}, options={"expose"=true})
     */
    public function integrationProgress($datastoreId, $uploadId, LoggerInterface $logger)
    {
        $upload = $this->plageApi->upload->get($datastoreId, $uploadId);
        $workflowIntProgress = json_decode($upload['tags']['workflow_integration_progress'], true);
        $workflowIntStep = $upload['tags']['workflow_integration_step'];

        $workflowClassName = $upload['tags']['workflow_class_name'];

        /** @var AbstractWorkflow */
        $integrationWorkflow = new $workflowClassName();

        if ($workflowIntStep == count($integrationWorkflow->steps)) {
            return $this->json($workflowIntProgress);
        }

        $integrationWorkflow->currentStep = $workflowIntStep;
        $integrationWorkflow->progress = $workflowIntProgress;

        $args = [
            'datastoreId' => $datastoreId,
            'upload' => $upload,
            'plageApi' => $this->plageApi,
            'params' => $this->params,
        ];
        WorkflowRunner::runWorkflow($integrationWorkflow, $args, $logger);

        $this->plageApi->upload->addTags($datastoreId, $uploadId, [
            'workflow_integration_step' => $integrationWorkflow->currentStep,
            'workflow_integration_progress' => json_encode($integrationWorkflow->progress),
        ]);

        return $this->json($integrationWorkflow->progress);
    }

    /**
     * @Route("/{uploadId}/integration/logs", name="integration_logs", methods={"GET"}, options={"expose"=true})
     */
    public function integrationProcLogs($datastoreId, $uploadId)
    {
        $upload = $this->plageApi->upload->get($datastoreId, $uploadId);
        $logs = '';

        if (array_key_exists('proc_int_id', $upload['tags'])) {
            $logs = $this->plageApi->processing->getExecutionLogs($datastoreId, $upload['tags']['proc_int_id']);
        }

        return new Response($logs);
    }

    /**
     * @Route("/{uploadId}/delete", name="delete", methods={"GET"})
     */
    public function delete($datastoreId, $uploadId)
    {
        try {
            $this->plageApi->upload->remove($datastoreId, $uploadId);
            $this->addFlash('success', 'Livraison supprimée avec succès');
        } catch (PlageApiException $ex) {
            $this->addFlash('error', $ex->getMessage());
        } finally {
            return $this->redirectToRoute('plage_datastore_view', [
                'datastoreId' => $datastoreId,
            ]);
        }
    }

    /**
     * @Route("/{uploadId}/delete-ajax", name="delete_ajax", methods={"POST"}, options={"expose"=true})
     */
    public function deleteAjax($datastoreId, $uploadId)
    {
        try {
            $this->plageApi->upload->remove($datastoreId, $uploadId);

            return new JsonResponse();
        } catch (PlageApiException $ex) {
            return new JsonResponse($ex->getMessage(), $ex->getCode());
        }
    }
}
