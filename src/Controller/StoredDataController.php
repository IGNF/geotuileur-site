<?php

namespace App\Controller;

use App\Constants\StoredDataTypes;
use App\Exception\PlageApiException;
use App\Service\PlageApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Yaml\Yaml;

/**
 * @Route("/datastores/{datastoreId}/stored_data", name="plage_stored_data_")
 */
class StoredDataController extends AbstractController
{
    /** @var PlageApiService */
    private $plageApi;

    public function __construct(PlageApiService $plageApi)
    {
        $this->plageApi = $plageApi;
    }

    /**
     * @Route("/{storedDataId}", name="get", methods={"GET"}, options={"expose"=true})
     */
    public function getById($datastoreId, $storedDataId): Response
    {
        try {
            $storedData = $this->plageApi->storedData->getDetailed($datastoreId, $storedDataId);

            return new JsonResponse($storedData);
        } catch (PlageApiException $ex) {
            return new JsonResponse($storedDataId, $ex->getStatusCode());
        }
    }

    /**
     * @Route("/{storedDataId}/report", name="report", methods={"GET"}, options={"expose"=true})
     */
    public function report($datastoreId, $storedDataId): Response
    {
        try {
            $datastore = $this->plageApi->datastore->get($datastoreId);

            // common to both VECTOR-DB and ROK4-PYRAMID-VECTOR
            $storedData = $this->plageApi->storedData->getDetailed($datastoreId, $storedDataId);
            $inputUpload = $this->plageApi->upload->get($datastoreId, $storedData['tags']['upload_id']);

            $projections = Yaml::parseFile(__DIR__.'/../../config/app/projections.yml');
            $inputUpload['proj_name'] = $projections[$inputUpload['srs']];

            $inputUpload['file_tree'] = $this->plageApi->upload->getFileTree($datastoreId, $inputUpload['_id']);
            $inputUpload['checks'] = $this->plageApi->upload->getCheckExecutions($datastoreId, $inputUpload['_id']);

            foreach ($inputUpload['checks'] as &$checkType) {
                foreach ($checkType as &$checkExecution) {
                    $checkExecution = array_merge($checkExecution, $this->plageApi->upload->getCheckExecution($datastoreId, $checkExecution['_id']));
                    $checkExecution['logs'] = $this->plageApi->upload->getCheckExecutionLogs($datastoreId, $checkExecution['_id']);
                }
            }

            $procIntegrationExec = $this->plageApi->processing->getExecution($datastoreId, $storedData['tags']['proc_int_id']);
            $procIntegrationExec['logs'] = $this->plageApi->processing->getExecutionLogs($datastoreId, $procIntegrationExec['_id']);

            // specific to ROK4-PYRAMID-VECTOR
            if (StoredDataTypes::ROK4_PYRAMID_VECTOR == $storedData['type']) {
                $procPyramidCreationExec = $this->plageApi->processing->getExecution($datastoreId, $storedData['tags']['proc_pyr_creat_id']);
                $procPyramidCreationExec['logs'] = $this->plageApi->processing->getExecutionLogs($datastoreId, $procPyramidCreationExec['_id']);
            }
        } catch (PlageApiException $ex) {
            if (Response::HTTP_NOT_FOUND == $ex->getStatusCode()) {
                $this->addFlash('error', "La donnée demandée n'existe pas");

                return $this->redirectToRoute('plage_datastore_view', [
                    'datastoreId' => $datastoreId,
                ]);
            }

            throw $ex;
        }

        return $this->render('pages/stored_data/report.html.twig', [
            'datastore' => $datastore,
            'stored_data' => $storedData,
            'input_upload' => $inputUpload,
            'proc_int_exec' => $procIntegrationExec,
            'proc_pyr_creat_exec' => $procPyramidCreationExec ?? null,
        ]);
    }

    /**
     * AJAX Request
     * Suppression d'une donnee.
     *
     * @Route("/{storedDataId}/delete",
     *      name="delete_ajax",
     *      options={"expose"=true},
     *      methods={"POST"}
     * )
     * Response JSON
     */
    public function ajaxDelete($datastoreId, $storedDataId)
    {
        try {
            $this->plageApi->storedData->remove($datastoreId, $storedDataId);

            return new JsonResponse();
        } catch (PlageApiException $ex) {
            $data = ['message' => $ex->getMessage(), 'details' => $ex->getDetails()];

            return new JsonResponse($data, $ex->getCode());
        }
    }
}
