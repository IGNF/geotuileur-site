<?php

namespace App\Controller;

use App\Constants\StorageTypes;
use App\Constants\StoredDataStatuses;
use App\Constants\StoredDataTypes;
use App\Exception\PlageApiException;
use App\Service\PlageApi\AdministratorApiService;
use App\Service\PlageApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/datastores", name="plage_datastore_")
 */
class DatastoreController extends AbstractController
{
    private PlageApiService $plageApi;
    private ParameterBagInterface $params;
    private AdministratorApiService $adminApiService;

    public function __construct(PlageApiService $plageApi, ParameterBagInterface $params, AdministratorApiService $adminApi)
    {
        $this->plageApi = $plageApi;
        $this->adminApiService = $adminApi;
        $this->params = $params;
    }

    /**
     * Liste des espaces de travail.
     *
     * @Route("/", name="index", methods={"GET"})
     */
    public function index(): Response
    {
        /** @var array */
        $serviceAccount = $this->params->get('service_account');

        $myDatastores = array_values($this->plageApi->user->getMyDatastores());

        $datastores = [];
        $datastoreBAS = [
            '_id' => -1,
            'name' => 'Bac à sable',
            'technical_name' => 'bacasable-xxxxxxxxxxxxx',
        ];
        $regexBacasable = '/^bacasable-[a-zA-Z0-9]{13}$/';

        foreach ($myDatastores as $datastore) {
            preg_match($regexBacasable, $datastore['technical_name'], $matches);

            if (0 == count($matches)) { // n'est pas un bacasable
                $datastores[] = $datastore;
            } else { // est un bacasable
                $datastoreBAS = $datastore;
            }
        }

        return $this->render('pages/datastore/index.html.twig', [
            'serviceAccount' => $serviceAccount['username'],
            'datastores' => $datastores,
            'datastoreBAS' => $datastoreBAS,
        ]);
    }

    /**
     * Creation d'un datastore 'Bac a sable'.
     *
     * @Route("/create-sandbox", name="create_sandbox", methods={"GET"})
     */
    public function createSandbox()
    {
        $uuid = uniqid();

        // Creation d'une communaute 'Bac à sable'
        $community = $this->adminApiService->createCommunity('Bac à sable', "bacasable-$uuid");

        // Creation et activation d'un datastore
        $datastore = $this->adminApiService->createDatastore($community['_id']);
        $this->adminApiService->activateDatastore($datastore['_id']);

        // C'est ici que ça se passe si on veut ajouter d'autres choses dans le datastore, par exemple une donnée partagée.

        // Affiche le tableau de bord du nouveau datastore
        return $this->redirectToRoute('plage_datastore_view', ['datastoreId' => $datastore['_id']]);
    }

    /**
     * Tableau de bord d'un espace de travail.
     *
     * @Route("/{datastoreId}", name="view", methods={"GET"}, options={"expose"=true})
     */
    public function view($datastoreId): Response
    {
        $user = $this->plageApi->user->getMe();
        $datastore = $this->plageApi->datastore->get($datastoreId);
        $community = $this->plageApi->community->get($datastore['community']['_id']);

        $membersCount = -1;

        $userCommunityRights = $this->plageApi->user->getMyCommunityRights($datastore['community']['_id']);
        if ($userCommunityRights['rights']['community_rights']) {
            $comMembers = $this->plageApi->community->getMembers($community['_id']);
            $membersCount = count($comMembers);
        }

        return $this->render('pages/datastore/dashboard.html.twig', [
            'datastore' => $datastore,
            'community' => $community,
            'user' => $user,
            'community_members_count' => $membersCount,
        ]);
    }

    /**
     * @Route("/{datastoreId}/get-datastore-ajax", name="get_datastore_ajax", methods={"GET"}, options={"expose"=true})
     */
    public function getAjax($datastoreId): JsonResponse
    {
        $datastore = $this->plageApi->datastore->get($datastoreId);

        return $this->json($datastore);
    }

    /**
     * @Route("/{datastoreId}/dashboard-data", name="get_dashboard_data", methods={"GET"}, options={"expose"=true})
     */
    public function getDashboardData($datastoreId): JsonResponse
    {
        $actionsRequired = [];
        $inProgress = [];
        $publishedPyramids = [];

        // stored_data-vectordb
        $vectordbList = $this->plageApi->storedData->getAll($datastoreId, [
            'type' => StoredDataTypes::VECTOR_DB,
        ]);

        foreach ($vectordbList as &$vectordb) {
            $vectordb = $this->plageApi->storedData->get($datastoreId, $vectordb['_id']);

            if (
                array_key_exists('pyramid_id', $vectordb['tags']) // ignorer si vectordb a déjà été utilisé pour générer une pyramide
                || (StoredDataStatuses::GENERATED == $vectordb['status'] && array_key_exists('initial_pyramid_id', $vectordb['tags'])) // intégration d'un vectordb (de mise à jour) a réussi
            ) {
                continue;
            }

            if (StoredDataStatuses::GENERATED == $vectordb['status'] || StoredDataStatuses::UNSTABLE == $vectordb['status']) {
                if (array_key_exists('proc_int_id', $vectordb['tags'])) {
                    $vectordbProcInt = $this->plageApi->processing->getExecution($datastoreId, $vectordb['tags']['proc_int_id']);
                    $vectordb['input_upload_id'] = $vectordbProcInt['inputs']['upload'][0]['_id'];

                    $actionsRequired[] = $vectordb;
                }
            } elseif (StoredDataStatuses::GENERATING == $vectordb['status']) {
                $inProgress[] = $vectordb;
            }
        }

        // stored_data-pyramid_vector
        $pyramids = $this->plageApi->storedData->getAll($datastoreId, [
            'type' => StoredDataTypes::ROK4_PYRAMID_VECTOR,
        ]);

        foreach ($pyramids as &$pyramid) {
            $pyramid = $this->plageApi->storedData->get($datastoreId, $pyramid['_id']);

            // ignore pyramids with CREATED status
            if (StoredDataStatuses::CREATED == $pyramid['status']) {
                continue;
            } elseif (StoredDataStatuses::GENERATING == $pyramid['status']) {
                $inProgress[] = $pyramid;
            } elseif (StoredDataStatuses::GENERATED == $pyramid['status']) {
                if (array_key_exists('is_sample', $pyramid['tags']) && $pyramid['tags']['is_sample']) {
                    $actionsRequired[] = $pyramid;
                } elseif (array_key_exists('initial_pyramid_id', $pyramid['tags'])) {
                    $actionsRequired[] = $pyramid;
                } else {
                    $offerings = $this->plageApi->configuration->getAllOfferings($datastoreId, [
                        'stored_data' => $pyramid['_id'],
                    ]);

                    // check if pyramid is already published or not
                    if (0 == count($offerings)) {
                        $actionsRequired[] = $pyramid;
                    } else {
                        $publishedPyramids[] = $pyramid;
                    }
                }
            } elseif (StoredDataStatuses::UNSTABLE == $pyramid['status']) {
                $actionsRequired[] = $pyramid;
            }
        }

        $actionsRequired = $this->plageApi->storedData->sort($actionsRequired, 'last_event_date');
        $inProgress = $this->plageApi->storedData->sort($inProgress, 'last_event_date');
        $publishedPyramids = $this->plageApi->storedData->sort($publishedPyramids, 'last_event_date');

        $results = [];
        foreach ($actionsRequired as $sd) {
            $results['actions_required'][] = $sd['_id'];
        }
        foreach ($inProgress as $sd) {
            $results['in_progress'][] = $sd['_id'];
        }
        foreach ($publishedPyramids as $sd) {
            $results['published_pyramids'][] = $sd['_id'];
        }

        return new JsonResponse($results);
    }

    /**
     * Liste des membres d'un espace de travail ou ajouter un utilisateur dans l'espace de travail.
     *
     * @Route("/{datastoreId}/members", name="view_members", methods={"GET", "POST"})
     */
    public function viewMembers($datastoreId, Request $request, TranslatorInterface $translator): Response
    {
        $datastore = $this->plageApi->datastore->get($datastoreId);
        $userCommunityRights = $this->plageApi->user->getMyCommunityRights($datastore['community']['_id']);

        if (!$userCommunityRights['rights']['community_rights']) {
            $this->addFlash('warning', "Vous n'avez la permission nécessaire pour consulter la liste des membres de cet espace de travail");

            return $this->redirectToRoute('plage_datastore_view', ['datastoreId' => $datastoreId]);
        }

        $community = $this->plageApi->community->get($datastore['community']['_id']);
        $comMembers = $this->plageApi->community->getMembers($community['_id']);

        $form = $this->createFormBuilder()
            ->add('user_id', TextType::class, [
                'data' => '',
                'translation_domain' => 'PlageWebClient',
                'label' => 'datastore.members.form_add_user_to_community.user_id',
                'constraints' => [
                    new NotBlank(),
                    new Length(24, null, null, 'UTF-8', null, $translator->trans('datastore.members.form_add_user_to_community.user_id_constraint_length', [], 'PlageWebClient', $request->getLocale())),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'translation_domain' => 'PlageWebClient',
                'label' => 'datastore.members.form_add_user_to_community.submit',
                'attr' => [
                    'class' => 'btn btn--plain btn--primary btn-width--lg',
                ],
            ])
            ->getForm()
        ;

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $rights = [
                'community_rights' => false,
                'uploads_rights' => true,
                'processings_rights' => true,
                'stored_data_rights' => true,
                'datastore_rights' => true,
                'broadcast_rights' => true,
            ];

            $this->plageApi->community->addOrModifyUserRights($community['_id'], $data['user_id'], $rights);

            return $this->redirect($request->getUri());
        }

        return $this->render('pages/datastore/members.html.twig', [
            'datastore' => $datastore,
            'community' => $community,
            'members' => $comMembers,
            'user_community_rights' => $userCommunityRights,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Supprimer les droits d'un utilisateur sur un espace de travail.
     *
     * @Route("/{datastoreId}/members/remove-member", name="members_remove", methods={"DELETE"}, options={"expose"=true})
     */
    public function removeMember($datastoreId, Request $request)
    {
        $userId = $request->query->get('user_id', null);

        $regex = '/^[a-z0-9]{24}$/';
        preg_match($regex, $userId, $matches);

        if (0 == count($matches)) {
            return new JsonResponse('Query param [user_id] must be a 24 character string', Response::HTTP_BAD_REQUEST);
        }

        try {
            $datastore = $this->plageApi->datastore->get($datastoreId);
            $this->plageApi->community->removeUserRights($datastore['community']['_id'], $userId);

            return new JsonResponse(null, Response::HTTP_OK);
        } catch (PlageApiException $ex) {
            return new JsonResponse($ex->getMessage(), $ex->getStatusCode());
        }
    }

    /**
     * Gestion de l'espace d'un espace de travail.
     *
     * @Route("/{datastoreId}/manage-storage", name="manage_storage", methods={"GET"})
     */
    public function manageStorage($datastoreId): Response
    {
        $datastore = $this->plageApi->datastore->get($datastoreId);

        // les données stockées
        $storedDataList = $this->plageApi->storedData->getAll($datastoreId);
        $storedDataPostgresList = [];
        $storedDataFilesystemList = [];
        $storedDataS3List = [];

        foreach ($storedDataList as &$storedData) {
            $storedData = $this->plageApi->storedData->get($datastoreId, $storedData['_id']);

            switch ($storedData['storage']['type']) {
                case StorageTypes::POSTGRESQL:
                    $storedDataPostgresList[] = $storedData;
                    break;

                case StorageTypes::FILESYSTEM:
                    $storedDataFilesystemList[] = $storedData;
                    break;

                case StorageTypes::S3:
                    $storedDataS3List[] = $storedData;
                    break;
            }
        }

        // les livraisons
        $uploads = $this->plageApi->upload->getAll($datastoreId);
        foreach ($uploads as &$upload) {
            $upload = $this->plageApi->upload->get($datastoreId, $upload['_id']);
        }

        // les fichiers annexes
        $annexes = $this->plageApi->annexe->getAll($datastoreId);
        foreach ($annexes as &$annexe) {
            $annexe = $this->plageApi->annexe->get($datastoreId, $annexe['_id']);
        }

        // les couches publiées sur l'endpoint
        $endpoint = $datastore['endpoints'][0]['endpoint'];
        $offerings = $this->plageApi->configuration->getAllOfferings($datastoreId, [
            'endpoint' => $endpoint['_id'],
        ]);

        foreach ($offerings as &$offering) {
            try {
                $offering = $this->plageApi->configuration->getOffering($datastoreId, $offering['_id']);
                $offering['configuration'] = $this->plageApi->configuration->get($datastoreId, $offering['configuration']['_id']);
            } catch (PlageApiException $ex) {
                if (Response::HTTP_NOT_FOUND == $ex->getStatusCode()) {
                    continue;
                }
                throw $ex;
            }
        }

        return $this->render('pages/datastore/storage.html.twig', [
            'datastore' => $datastore,
            'stored_data_postgres' => $this->plageApi->storedData->sort($storedDataPostgresList, 'size'),
            'stored_data_filesystem' => $this->plageApi->storedData->sort($storedDataFilesystemList, 'size'),
            'stored_data_s3' => $this->plageApi->storedData->sort($storedDataS3List, 'size'),
            'uploads' => $this->plageApi->upload->sort($uploads, 'size'),
            'annexes' => $this->plageApi->annexe->sort($annexes, 'size'),
            'offerings' => $offerings,
        ]);
    }
}
