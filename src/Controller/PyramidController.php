<?php

namespace App\Controller;

use App\Components\Workflow\CompleteUpdateWorkflow;
use App\Constants\PyramidZoomLevels;
use App\Constants\UploadStatuses;
use App\Constants\UploadTypes;
use App\Exception\AppException;
use App\Exception\PlageApiException;
use App\Form\GeneratePyramidForm\GeneratePyramidType;
use App\Form\PublishPyramidType;
use App\Form\UpdatePyramidType;
use App\Service\PlageApiService;
use App\Utils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/datastores/{datastoreId}/pyramid", name="plage_pyramid_")
 */
class PyramidController extends AbstractController
{
    /** @var PlageApiService */
    private $plageApi;

    /** @var ParameterBagInterface */
    private $params;

    /** @var TranslatorInterface */
    private $translator;

    private $apiPlageAnnexeUrl;

    public function __construct(PlageApiService $plageApi, ParameterBagInterface $parameters, TranslatorInterface $translator)
    {
        $this->plageApi = $plageApi;
        $this->apiPlageAnnexeUrl = $parameters->get('api_plage_annexe_url');
        $this->params = $parameters;
        $this->translator = $translator;
    }

    /**
     * @Route("/add", name="add", methods={"GET","POST"}, options={"expose"=true})
     */
    public function add($datastoreId, Request $request)
    {
        $samplePyramidId = $request->query->get('samplePyramidId', null);
        
        $vectordbId = $request->query->get('vectordbId', null);

        // just for precaution, normally these errors "shouldn't" occur
        if (!$vectordbId) {
            $this->addFlash('error', "Aucune donnée en entrée n'a été fournie");
            return $this->redirectToRoute('plage_datastore_view', ['datastoreId' => $datastoreId]);
        }

        try {
            $vectordb = $this->plageApi->storedData->get($datastoreId, $vectordbId);
        } catch (PlageApiException $ex) {
            if (Response::HTTP_NOT_FOUND == $ex->getCode()) {
                $this->addFlash('error', "La donnée en entrée n'existe pas");
            }
            return $this->redirectToRoute('plage_datastore_view', ['datastoreId' => $datastoreId]);
        }

        // Verification de l'existence de l'extent
        if (! array_key_exists('extent', $vectordb)) {
            $this->addFlash('error', "L'étendue géographique des données n'a pas pu être déterminée. Il n'est pas possible de générer une pyramide de tuiles vectorielles à partir de ces données.");
            return $this->redirectToRoute('plage_datastore_view', ['datastoreId' => $datastoreId]);
        }

        try {
            // supprime l'upload si elle existe toujours
            if (array_key_exists('upload_id', $vectordb['tags'])) {
                $upload = $this->plageApi->upload->get($datastoreId, $vectordb['tags']['upload_id']);
                if (UploadStatuses::DELETED !== $upload['status']) {
                    $this->plageApi->upload->remove($datastoreId, $upload['_id']);
                }
            }
        } catch (PlageApiException $ex) {
            $this->addFlash('warning', $ex->getMessage());
        }

        try {
            if ($request->query->has('procCreatPyramidSampleId')) {
                $procCreatPyramidSample = $this->plageApi->processing->getExecution($datastoreId, $request->query->get('procCreatPyramidSampleId'));
            }
        } catch (PlageApiException $ex) {
            $this->addFlash('error', $ex->getMessage());

            return $this->redirectToRoute('plage_datastore_view', ['datastoreId' => $datastoreId]);
        }

        // Types de generalisation
        $tippecanoes = $this->getGeneralizations();

        // Suppression des tables sans colonne geometrique
        $typeInfos = $vectordb['type_infos'];
        if (isset($typeInfos['relations'])) {
            $relations = [];
            foreach ($typeInfos['relations'] as $table) {
                // Recherche de la colonne geometrique
                if ($this->hasGeometricColumns($table)) {
                    $relations[] = $table;
                }
            }
            $typeInfos['relations'] = $relations;
        }

        $streamName = 'Tuiles '.$vectordb['name'];

        $topLevelMin    = isset($procCreatPyramidSample) ? $procCreatPyramidSample['parameters']['top_level'] : PyramidZoomLevels::TOP_LEVEL_MIN;
        $bottomLevelMax = isset($procCreatPyramidSample) ? $procCreatPyramidSample['parameters']['bottom_level'] : PyramidZoomLevels::BOTTOM_LEVEL_MAX;
       
        try {
            $form = $this->createForm(GeneratePyramidType::class, null, [
                'datastoreId' => $datastoreId,
                'type_infos' => $typeInfos,
                'stream_name' => $streamName,
                'proc_creat_pyramid_sample' => $procCreatPyramidSample ?? null,
            ]);

            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    // Suppression de l'echantillon
                    if ($samplePyramidId) {
                        // suppression de l'offering, puis la config
                        $offering = $this->plageApi->configuration->getAllOfferings($datastoreId, [
                            'stored_data' => $samplePyramidId,
                        ]);

                        $offering = $this->plageApi->configuration->getOffering($datastoreId, $offering[0]['_id']);
                        $configuration = $this->plageApi->configuration->get($datastoreId, $offering['configuration']['_id']);

                        $this->plageApi->configuration->removeOffering($datastoreId, $offering['_id']);
                        $this->plageApi->configuration->remove($datastoreId, $configuration['_id']);

                        // suppression de la pyramide échantillon
                        $this->plageApi->storedData->remove($datastoreId, $samplePyramidId);
                    }

                    $formData = $form->getData();

                    // Les niveaux de zoom
                    $levels = json_decode($formData['levels'], true);
                    $mainLevels = $levels['main'];

                    $compositionData = json_decode($formData['composition'], true);

                    $composition = [];
                    foreach ($compositionData as $tableName => $columns) {
                        $tableLevel = $mainLevels;
                        if (isset($levels[$tableName])) {
                            $tableLevel = $levels[$tableName];
                        }

                        $composition[] = [
                            'table' => $tableName,
                            'bottom_level' => strval($tableLevel['bottomLevel']),
                            'top_level' => strval($tableLevel['topLevel']),
                            'attributes' => implode(',', $columns),
                        ];
                    }

                    $parameters = [
                        'tms' => 'PM',
                        'bottom_level' => strval($mainLevels['bottomLevel']),
                        'top_level' => strval($mainLevels['topLevel']),
                        'composition' => $composition,
                        'tippecanoe_options' => $formData['tippecanoe'],
                    ];
                    if ($formData['sample']) {
                        $bbox = json_decode($formData['bbox']);
                        $parameters['bbox'] = $bbox;
                    }

                    /** @var array */
                    $apiPlageProcessings = $this->params->get('api_plage_processings');
                    $requestBody = [
                        'processing' => $apiPlageProcessings['create_vect_pyr'],
                        'inputs' => ['stored_data' => [$vectordbId]],
                        'output' => ['stored_data' => ['name' => $vectordb['name']]],
                        'parameters' => $parameters,
                    ];

                    $processingExecution = $this->plageApi->processing->addExecution($datastoreId, $requestBody);
                    $pyramidId = $processingExecution['output']['stored_data']['_id'];

                    $this->plageApi->storedData->addTags($datastoreId, $vectordbId, [
                        'pyramid_id' => $pyramidId,
                    ]);

                    $pyramidTags = [
                        'upload_id' => $vectordb['tags']['upload_id'],
                        'proc_int_id' => $vectordb['tags']['proc_int_id'],
                        'vectordb_id' => $vectordbId,
                        'proc_pyr_creat_id' => $processingExecution['_id'],
                    ];

                    if ($formData['sample']) {
                        $pyramidTags['is_sample'] = true;
                    }

                    $this->plageApi->storedData->addTags($datastoreId, $pyramidId, $pyramidTags);
                    $this->plageApi->processing->launchExecution($datastoreId, $processingExecution['_id']);

                    return $this->redirectToRoute('plage_datastore_view', ['datastoreId' => $datastoreId]);
                } catch (PlageApiException $ex) {
                    $this->addFlash('error', $ex->getMessage());
                }
            }
        } catch (InvalidArgumentException $ex) {
            $this->addFlash(
                'error',
                $this->translator->trans('pyramid.form_add.invalid_argument_message', [], 'PlageWebClient')
            );

            return $this->redirectToRoute('plage_datastore_view', ['datastoreId' => $datastoreId]);
        }

        return $this->render('pages/pyramid/add.html.twig', [
            'datastoreId' => $datastoreId,
            'datastore' => $this->plageApi->datastore->get($datastoreId),
            'form' => $form->createView(),
            'topLevelMin' => $topLevelMin,
            'bottomLevelMax' => $bottomLevelMax,
            'type_infos' => $typeInfos,
            'proc_creat_pyramid_sample' => $procCreatPyramidSample ?? null,
            'tippecanoes' => $tippecanoes,
        ]);
    }

    /**
     * @Route("/{pyramidId}/publish", name="publish", methods={"GET","POST"}, options={"expose"=true})
     */
    public function publish($datastoreId, $pyramidId, Request $request)
    {
        try {
            $endpoints = $this->plageApi->datastore->getEndpoints($datastoreId);
            $pyramid = $this->plageApi->storedData->get($datastoreId, $pyramidId);
        } catch (PlageApiException $ex) {
            if (Response::HTTP_NOT_FOUND == $ex->getCode()) {
                $this->addFlash('error', "La donnée en entrée n'existe pas");
            }

            return $this->redirectToRoute('plage_datastore_view', ['datastoreId' => $datastoreId]);
        }

        try {
            // supprime l'upload si elle existe toujours
            if (array_key_exists('upload_id', $pyramid['tags'])) {
                $upload = $this->plageApi->upload->get($datastoreId, $pyramid['tags']['upload_id']);
                if (UploadStatuses::DELETED !== $upload['status']) {
                    $this->plageApi->upload->remove($datastoreId, $upload['_id']);
                }
            }

            // supprime le vectordb s'il existe toujours
            if (array_key_exists('vectordb_id', $pyramid['tags'])) {
                $vectordb = $this->plageApi->storedData->get($datastoreId, $pyramid['tags']['vectordb_id']);
                if (UploadStatuses::DELETED !== $vectordb['status']) {
                    $this->plageApi->storedData->remove($datastoreId, $vectordb['_id']);
                }
            }
        } catch (PlageApiException $ex) {
            $this->addFlash('warning', $ex->getMessage());
        }

        $form = $this->createForm(PublishPyramidType::class, null, [
            'datastoreId' => $datastoreId,
            'pyramidId' => $pyramidId,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $formData = $form->getData();

                // Recherche de bottom_level et top_level
                $levels = $this->getBottomAndToLevel($pyramid);

                $requestBody = [
                    'type' => 'WMTS-TMS',
                    'name' => $formData['name'],
                    'layer_name' => $formData['name'],
                    'type_infos' => [
                        'title' => Utils::convertCharsToHtmlEntities($formData['title']),
                        'abstract' => Utils::convertCharsToHtmlEntities($formData['description']),
                        'used_data' => [
                            [
                                'stored_data' => $pyramidId,
                                'bottom_level' => $levels['bottom_level'],
                                'top_level' => $levels['top_level'],
                            ],
                        ],
                    ],
                    'attribution' => [
                        'title' => Utils::convertCharsToHtmlEntities($formData['legal_notices']),
                        'url' => $formData['attribution_url'],
                    ],
                ];

                if ($keywords = json_decode($formData['keywords'], true)) {
                    foreach ($keywords as &$keyword) {
                        $keyword = Utils::convertCharsToHtmlEntities($keyword);
                    }

                    $requestBody['type_infos']['keywords'] = $keywords;
                }

                $filteredEndpoints = array_filter($endpoints, [$this, 'filterTMSEndpoints']);

                if (1 != count($filteredEndpoints)) {
                    throw new \Exception('Il y a plusieurs endpoints de type WMTS-TMS');
                }

                // Ajout de la configuration
                $configuration = $this->plageApi->configuration->add($datastoreId, $requestBody);

                // Publication
                $endpointId = $filteredEndpoints[0]['endpoint']['_id'];
                $offering = $this->plageApi->configuration->publish($datastoreId, $configuration['_id'], $endpointId);

                // Recuperation de l'URL
                $url = $this->getUrl($offering['urls']);
                $this->plageApi->storedData->addTags($datastoreId, $pyramidId, ['tms_url' => $url]);

                return $this->redirectToRoute('plage_pyramid_share', ['datastoreId' => $datastoreId, 'pyramidId' => $pyramidId]);
            } catch (PlageApiException $ex) {
                $this->addFlash('error', $ex->getMessage());
            } catch (\Exception $ex) {
                $this->addFlash('error', $ex->getMessage());
            }
        }

        // Get TMS URL for preview only
        $datastore = $this->plageApi->datastore->get($datastoreId);
        $tmsUrl = $this->getTMSEndpoint($datastore);

        return $this->render('pages/pyramid/publish.html.twig', [
            'datastoreId' => $datastoreId,
            'datastore' => $datastore,
            'form' => $form->createView(),
            'tms_url' => $tmsUrl,
        ]);
    }

    /**
     * @Route("/{pyramidId}/update-publish", name="update_publish", methods={"GET","POST"}, options={"expose"=true})
     */
    public function updatePublish($datastoreId, $pyramidId, Request $request)
    {
        // Get TMS URL for preview only
        $datastore = $this->plageApi->datastore->get($datastoreId);
        $tmsUrl = $this->getTMSEndpoint($datastore);

        // Recherche de la publication
        $offerings = $this->plageApi->configuration->getAllOfferings($datastoreId, [
            'stored_data' => $pyramidId,
        ]);
        if (0 == count($offerings)) {
            $this->addFlash('error', 'Pas de publication pour ce flux');

            return $this->redirectToRoute('plage_datastore_view', ['datastoreId' => $datastoreId]);
        }

        $offeringId = $offerings[0]['_id'];

        // Recuperation de l'offering et de la configuration
        $offering = $this->plageApi->configuration->getOffering($datastoreId, $offeringId);
        $configuration = $this->plageApi->configuration->get($datastoreId, $offering['configuration']['_id']);

        $name = $configuration['name'];
        $urlPreview = "$tmsUrl/1.0.0/$name/{z}/{x}/{y}.pbf";
        $typeInfos = $configuration['type_infos'];

        $data = [
            'name' => $configuration['name'],
            'address_preview' => $urlPreview,
            'title' => Utils::convertHtmlEntitiesToChars($typeInfos['title']),
            'description' => Utils::convertHtmlEntitiesToChars($typeInfos['abstract']),
        ];

        // Les mots cles
        $keywords = null;
        if (isset($typeInfos['keywords'])) {
            $keywords = array_map(function ($keyword) {
                return ['value' => Utils::convertHtmlEntitiesToChars($keyword)];
            }, $typeInfos['keywords']);
            $keywords = json_encode($keywords);
        }

        // Les mentions legales
        if (isset($configuration['attribution'])) {
            $data['legal_notices'] = Utils::convertHtmlEntitiesToChars($configuration['attribution']['title']);
            $data['attribution_url'] = $configuration['attribution']['url'];
        }

        $form = $this->createForm(PublishPyramidType::class, $data, [
            'datastoreId' => $datastoreId,
            'pyramidId' => $pyramidId,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Suppression de la publication
                $this->plageApi->configuration->removeOffering($datastoreId, $offeringId);
                $this->plageApi->configuration->remove($datastoreId, $configuration['_id']);
                $this->plageApi->storedData->removeTags($datastoreId, $pyramidId, ['tms_url']);

                // Nouvelle publication
                $formData = $form->getData();

                $requestBody = [
                    'type' => 'WMTS-TMS',
                    'name' => $formData['name'],
                    'layer_name' => $formData['name'],
                    'type_infos' => [
                        'title' => Utils::convertCharsToHtmlEntities($formData['title']),
                        'abstract' => Utils::convertCharsToHtmlEntities($formData['description']),
                        'used_data' => [
                            [
                                'stored_data' => $pyramidId,
                                'bottom_level' => $typeInfos['used_data'][0]['bottom_level'],
                                'top_level' => $typeInfos['used_data'][0]['top_level'],
                            ],
                        ],
                    ],
                    'attribution' => [
                        'title' => Utils::convertCharsToHtmlEntities($formData['legal_notices']),
                        'url' => $formData['attribution_url'],
                    ],
                ];

                if ($keywords = json_decode($formData['keywords'], true)) {
                    foreach ($keywords as &$keyword) {
                        $keyword = Utils::convertCharsToHtmlEntities($keyword);
                    }

                    $requestBody['type_infos']['keywords'] = $keywords;
                }

                $endpoints = $this->plageApi->datastore->getEndpoints($datastoreId);
                $filteredEndpoints = array_filter($endpoints, [$this, 'filterTMSEndpoints']);

                if (1 != count($filteredEndpoints)) {
                    throw new \Exception('Il y a plusieurs endpoints de type WMTS-TMS');
                }

                // Ajout de la configuration
                $configuration = $this->plageApi->configuration->add($datastoreId, $requestBody);

                // Publication
                $endpointId = $filteredEndpoints[0]['endpoint']['_id'];
                $offering = $this->plageApi->configuration->publish($datastoreId, $configuration['_id'], $endpointId);

                // Recuperation de l'URL
                $url = $this->getUrl($offering['urls']);
                $this->plageApi->storedData->addTags($datastoreId, $pyramidId, ['tms_url' => $url]);

                return $this->redirectToRoute('plage_pyramid_share', ['datastoreId' => $datastoreId, 'pyramidId' => $pyramidId]);
            } catch (PlageApiException $ex) {
                $this->addFlash('error', $ex->getMessage());
            } catch (\Exception $ex) {
                $this->addFlash('error', $ex->getMessage());
            }
        }

        return $this->render('pages/pyramid/publish.html.twig', [
            'datastoreId' => $datastoreId,
            'datastore' => $datastore,
            'form' => $form->createView(),
            'tms_url' => $tmsUrl,
            'keywords' => $keywords,
        ]);
    }

    /**
     * @Route("/{pyramidId}/share", name="share", methods={"GET","POST"}, options={"expose"=true})
     */
    public function share($datastoreId, $pyramidId)
    {
        $pyramid = $this->plageApi->storedData->get($datastoreId, $pyramidId);

        // Recherche des styles
        $result = $this->plageApi->storedData->getStyles($datastoreId, $pyramidId);

        return $this->render('pages/pyramid/share.html.twig', [
            'datastoreId' => $datastoreId,
            'datastore' => $this->plageApi->datastore->get($datastoreId),
            'pyramid' => $pyramid,
            'styles' => $result['styles'],
            'defaultStyle' => $result['defaultStyle'],
        ]);
    }

    private function getBottomAndToLevel($pyramid)
    {
        if (!isset($pyramid['type_infos']) || !isset($pyramid['type_infos']['levels'])) {
            return ['bottom_level' => PyramidZoomLevels::BOTTOM_LEVEL_DEFAULT, 'top_level' => PyramidZoomLevels::TOP_LEVEL_DEFAULT];
        }

        $levels = $pyramid['type_infos']['levels'];

        return ['bottom_level' => end($levels), 'top_level' => reset($levels)];
    }

    private function getUrl($urls)
    {
        foreach ($urls as $url) {
            $parts = explode('|', $url);
            if (2 == count($parts) && 'tms' == $parts[0]) {
                return $parts[1];
            }
        }
        throw new \Exception("Impossible de récupérer l'url du flux");
    }

    /**
     * Recupere les types de generalisation (tippecanoe).
     */
    private function getGeneralizations()
    {
        $path = $this->params->get('public_path').'/img/tippecanoe';

        return [
            '--simplification=10' => [
                'value' => '-S10',
                'label' => $this->translator->trans('pyramid.form_add.tippecanoe.simplify_forms', [], 'PlageWebClient'),
                'explain' => $this->translator->trans('pyramid.form_add.tippecanoe.simplify_forms_explain', [], 'PlageWebClient'),
                'default' => true,
                'image' => "$path/simplify_forms_merged.jpg",
            ],
            '--no-simplification-of-shared-nodes --simplification=15' => [
                'value' => '-pn –S15',
                'label' => $this->translator->trans('pyramid.form_add.tippecanoe.keep_nodes', [], 'PlageWebClient'),
                'explain' => $this->translator->trans('pyramid.form_add.tippecanoe.keep_nodes_explain', [], 'PlageWebClient'),
                'image' => "$path/keep_nodes_merged.jpg",
            ],
            '--drop-smallest-as-needed --simplification=15 ' => [
                'value' => '-an –S15',
                'label' => $this->translator->trans('pyramid.form_add.tippecanoe.delete_smallest', [], 'PlageWebClient'),
                'explain' => $this->translator->trans('pyramid.form_add.tippecanoe.delete_smallest_explain', [], 'PlageWebClient'),
                'image' => "$path/delete_smallest_merged.jpg",
            ],
            '--grid-low-zooms –D8 --simplification=15' => [
                'value' => '-aL –D8 –S15',
                'label' => $this->translator->trans('pyramid.form_add.tippecanoe.keep_cover', [], 'PlageWebClient'),
                'explain' => $this->translator->trans('pyramid.form_add.tippecanoe.keep_cover_explain', [], 'PlageWebClient'),
                'image' => "$path/keep_cover_merged.jpg",
            ],
            '--coalesce --coalesce-densest-as-needed --drop-smallest-as-needed --simplification=15' => [
                'value' => '-ac –aD -an –S15',
                'label' => $this->translator->trans('pyramid.form_add.tippecanoe.keep_densest_delete_smallest', [], 'PlageWebClient'),
                'explain' => $this->translator->trans('pyramid.form_add.tippecanoe.keep_densest_delete_smallest_explain', [], 'PlageWebClient'),
                'image' => "$path/keep_densest_delete_smallest_merged.jpg",
            ],
            '--coalesce --drop-smallest-as-needed --simplification=10' => [
                'value' => '-ac -an –S10 ',
                'label' => $this->translator->trans('pyramid.form_add.tippecanoe.merge_same_attributes_and_simplify', [], 'PlageWebClient'),
                'explain' => $this->translator->trans('pyramid.form_add.tippecanoe.merge_same_attributes_and_simplify_explain', [], 'PlageWebClient'),
                'image' => "$path/merge_same_attributes_and_simplify_merged.jpg",
            ],
            '--detect-shared-borders --simplification=20' => [
                'value' => '-ab –S20',
                'label' => $this->translator->trans('pyramid.form_add.tippecanoe.keep_shared_edges', [], 'PlageWebClient'),
                'explain' => $this->translator->trans('pyramid.form_add.tippecanoe.keep_shared_edges_explain', [], 'PlageWebClient'),
                'image' => "$path/keep_shared_edges_merged.jpg",
            ],
        ];
    }

    /**
     * AJAX Request
     * Suppression d'une donnee publiee.
     *
     * @Route("/{pyramidId}/delete_published",
     *      name="delete_published_ajax",
     *      options={"expose"=true},
     *      methods={"POST"}
     * )
     * Response JSON
     */
    public function ajaxDeletePublished($datastoreId, $pyramidId)
    {
        try {
            // Recherche de la publication
            $offerings = $this->plageApi->configuration->getAllOfferings($datastoreId, [
                'stored_data' => $pyramidId,
            ]);
            if (0 == count($offerings)) {
                return new JsonResponse('Pas de publication pour ce flux', JsonResponse::HTTP_BAD_REQUEST);
            }

            $offeringId = $offerings[0]['_id'];

            // Suppression de la publication (offering et puis configuration)
            $offering = $this->plageApi->configuration->getOffering($datastoreId, $offeringId);
            $this->plageApi->configuration->removeOffering($datastoreId, $offeringId);
            $this->plageApi->configuration->remove($datastoreId, $offering['configuration']['_id']);

            // Suppression des annexes
            $styles = $this->plageApi->storedData->getStyles($datastoreId, $pyramidId);
            $ids = array_keys($styles['styles']);
            foreach ($ids as $annexeId) {
                $this->plageApi->annexe->remove($datastoreId, $annexeId);
            }

            // Suppression de la stored data
            $this->plageApi->storedData->remove($datastoreId, $pyramidId);

            return new JsonResponse();
        } catch (PlageApiException $ex) {
            $data = ['message' => $ex->getMessage(), 'details' => $ex->getDetails()];

            return new JsonResponse($data, $ex->getCode());
        }
    }

    /**
     * AJAX Request
     * Depublication d'une donnee publiee.
     *
     * @Route("/{pyramidId}/unpublish",
     *      name="unpublish_ajax",
     *      options={"expose"=true},
     *      methods={"POST"}
     * )
     * Response JSON
     */
    public function ajaxUnpublish($datastoreId, $pyramidId)
    {
        try {
            // Recherche de la publication
            $offerings = $this->plageApi->configuration->getAllOfferings($datastoreId, [
                'stored_data' => $pyramidId,
            ]);
            if (0 == count($offerings)) {
                return new JsonResponse('Pas de publication pour ce flux', JsonResponse::HTTP_BAD_REQUEST);
            }

            $offeringId = $offerings[0]['_id'];

            // Suppression de la publication
            $offering = $this->plageApi->configuration->getOffering($datastoreId, $offeringId);
            $this->plageApi->configuration->removeOffering($datastoreId, $offerings[0]['_id']);
            $this->plageApi->configuration->remove($datastoreId, $offering['configuration']['_id']);
            $this->plageApi->storedData->removeTags($datastoreId, $pyramidId, ['tms_url']);

            return new JsonResponse();
        } catch (PlageApiException $ex) {
            $data = ['message' => $ex->getMessage(), 'details' => $ex->getDetails()];

            return new JsonResponse($data, $ex->getCode());
        }
    }

    /**
     * @Route("/update", name="update", methods={"GET","POST"}, options={"expose"=true})
     */
    public function update($datastoreId, Request $request)
    {
        $pyramid = null;
        if ($request->query->has('pyramidId')) {
            $pyramidId = $request->query->get('pyramidId');

            try {
                $pyramid = $this->plageApi->storedData->get($datastoreId, $pyramidId);
            } catch (PlageApiException $ex) {
                if (Response::HTTP_NOT_FOUND == $ex->getCode()) {
                    $this->addFlash('error', "La donnée en entrée n'existe pas");

                    return $this->redirectToRoute('plage_datastore_view', ['datastoreId' => $datastoreId]);
                }
            }
        }

        $form = $this->createForm(UpdatePyramidType::class, null, [
            'datastoreId' => $datastoreId,
            'pyramid' => $pyramid ?? null,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $formData = $form->getData();

                $initialPyramid = $this->plageApi->storedData->get($datastoreId, $formData['pyramid_id']);
                $initialUpload = $this->plageApi->upload->get($datastoreId, $initialPyramid['tags']['upload_id']);

                // créer nouvelle upload
                $newUploadData = [
                    'name' => $formData['name'],
                    'description' => $initialUpload['description'].' update_complete',
                    'type' => UploadTypes::VECTOR,
                    'srs' => $formData['srs'],
                ];

                $workflowIntProgress = (new CompleteUpdateWorkflow())->getInitialProgress();

                $tags = [
                    'file_data' => $formData['file_data'],
                    'workflow_integration_step' => 0,
                    'workflow_integration_progress' => json_encode($workflowIntProgress),
                    'workflow_class_name' => CompleteUpdateWorkflow::class,
                    'initial_pyramid_id' => $formData['pyramid_id'],
                ];

                $upload = $this->plageApi->upload->add($datastoreId, $newUploadData);
                $this->plageApi->upload->addTags($datastoreId, $upload['_id'], $tags);

                return $this->redirectToRoute('plage_upload_integration', ['datastoreId' => $datastoreId, 'uploadId' => $upload['_id']]);
            } catch (PlageApiException $ex) {
                $this->addFlash('error', $ex->getMessage());

                $args = ['datastoreId' => $datastoreId];
                if ($request->query->has('pyramidId')) {
                    $args['pyramidId'] = $request->query->get('pyramidId');
                }

                return $this->redirectToRoute('plage_pyramid_update', $args);
            }
        }

        return $this->render('pages/pyramid/update.html.twig', [
            'datastoreId' => $datastoreId,
            'datastore' => $this->plageApi->datastore->get($datastoreId),
            'form' => $form->createView(),
        ]);
    }

    /**
     * Visualiser et comparer les 2 pyramides (la pyramide initial et la pyramide de mise à jour).
     *
     * @Route("/{pyramidId}/update-compare", name="update_compare", methods={"GET"}, options={"expose"=true})
     */
    public function updateCompare($datastoreId, $pyramidId)
    {
        $pyramid = null; // la pyramide servant de mise à jour complète
        try {
            $pyramid = $this->plageApi->storedData->get($datastoreId, $pyramidId);

            if (!array_key_exists('initial_pyramid_id', $pyramid['tags'])) {
                throw new AppException("Le flux actuel [$pyramidId] n'est pas un flux de mise à jour ou la référence vers le flux initial est manquante", Response::HTTP_BAD_REQUEST);
            }

            $initialPyramid = $this->plageApi->storedData->get($datastoreId, $pyramid['tags']['initial_pyramid_id']);

            // vérif si la pyramide initiale n'est pas publiée (techniquement ne devrait pas arriver)
            $initialOffering = $this->plageApi->configuration->getAllOfferings($datastoreId, [
                'stored_data' => $initialPyramid['_id'],
            ]);

            if (0 == count($initialOffering)) {
                throw new AppException("Le flux initial [{$initialPyramid['_id']}] n'est pas publié", Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // on ne republie pas la pyramide de mise à jour si elle est déjà publié
            if (!array_key_exists('tms_url', $pyramid['tags'])) {
                $initialOffering = $this->plageApi->configuration->getOffering($datastoreId, $initialOffering[0]['_id']);

                $initialConfig = $this->plageApi->configuration->getAll($datastoreId, [
                    'stored_data' => $initialPyramid['_id'],
                ])[0];

                $initialConfig = $this->plageApi->configuration->get($datastoreId, $initialConfig['_id']);

                $requestBody = [
                    'type' => 'WMTS-TMS',
                    'name' => $initialConfig['name'].'maj',
                    'layer_name' => $initialConfig['name'].'maj',
                    'type_infos' => [
                        'title' => $initialConfig['type_infos']['title'].' maj complete',
                        'abstract' => $initialConfig['type_infos']['abstract'].' maj complete',
                        'used_data' => [
                            [
                                'stored_data' => $pyramidId,
                                'bottom_level' => $initialConfig['type_infos']['used_data'][0]['bottom_level'],
                                'top_level' => $initialConfig['type_infos']['used_data'][0]['top_level'],
                            ],
                        ],
                    ],
                ];

                if (array_key_exists('keywords', $initialConfig['type_infos']) && count($initialConfig['type_infos']['keywords']) > 0) {
                    $requestBody['type_infos']['keywords'] = $initialConfig['type_infos']['keywords'];
                }

                $endpoints = $this->plageApi->datastore->getEndpoints($datastoreId);
                $filteredEndpoints = array_filter($endpoints, [$this, 'filterTMSEndpoints']);

                if (1 != count($filteredEndpoints)) {
                    throw new AppException('Il y a plusieurs endpoints de type WMTS-TMS', Response::HTTP_INTERNAL_SERVER_ERROR);
                }

                // Ajout de la configuration
                $configuration = $this->plageApi->configuration->add($datastoreId, $requestBody);

                // Publication
                $endpointId = $filteredEndpoints[0]['endpoint']['_id'];
                $offering = $this->plageApi->configuration->publish($datastoreId, $configuration['_id'], $endpointId);

                // Recuperation de l'URL
                $url = $this->getUrl($offering['urls']);
                $this->plageApi->storedData->addTags($datastoreId, $pyramidId, ['tms_url' => $url]);

                $pyramid = $this->plageApi->storedData->get($datastoreId, $pyramidId);
            }
        } catch (PlageApiException|AppException $ex) {
            if (Response::HTTP_NOT_FOUND == $ex->getCode()) {
                $this->addFlash('error', "Aucun flux ne correspond à l'identifiant [$pyramidId]");
            }
            $this->addFlash('error', $ex->getMessage());
        }

        return $this->render('pages/pyramid/update_compare.html.twig', [
            'datastore' => $this->plageApi->datastore->get($datastoreId),
            'pyramid' => $pyramid,
            'pyramid_initial' => $initialPyramid ?? null,
        ]);
    }

    /**
     * Valider ou refuser la mise à jour d'une pyramide.
     *
     * @Route("/{pyramidId}/update-validate", name="update_validate", methods={"GET"})
     */
    public function updateValidate($datastoreId, $pyramidId, Request $request)
    {
        try {
            if (!$request->query->has('validate')) {
                throw new AppException('Paramètre query [validate] manquant', Response::HTTP_BAD_REQUEST);
            }
            $validate = $request->query->get('validate');

            $pyramid = $this->plageApi->storedData->get($datastoreId, $pyramidId);
            if (!array_key_exists('initial_pyramid_id', $pyramid['tags'])) {
                throw new AppException("La pyramide n'est pas une pyramide de mise à jour", Response::HTTP_BAD_REQUEST);
            }

            switch ($validate) {
                case 'yes':
                    $initialPyramid = $this->plageApi->storedData->get($datastoreId, $pyramid['tags']['initial_pyramid_id']);

                    // récupération et sauvegarde de l'ancienne offering et config
                    $initialOffering = $this->plageApi->configuration->getAllOfferings($datastoreId, [
                        'stored_data' => $initialPyramid['_id'],
                    ]);

                    $initialOffering = $this->plageApi->configuration->getOffering($datastoreId, $initialOffering[0]['_id']);
                    $initialConfig = $this->plageApi->configuration->get($datastoreId, $initialOffering['configuration']['_id']);

                    // publication de la nouvelle pyramide avec les mêmes paramètres que l'ancienne
                    $configRequestData = [
                        'type' => 'WMTS-TMS',
                        'name' => $initialConfig['name'],
                        'layer_name' => $initialConfig['name'],
                        'type_infos' => [
                            'title' => $initialConfig['type_infos']['title'],
                            'abstract' => $initialConfig['type_infos']['abstract'],
                            'used_data' => [
                                [
                                    'stored_data' => $pyramidId,
                                    'bottom_level' => $initialConfig['type_infos']['used_data'][0]['bottom_level'],
                                    'top_level' => $initialConfig['type_infos']['used_data'][0]['top_level'],
                                ],
                            ],
                        ],
                    ];

                    if (array_key_exists('keywords', $initialConfig['type_infos']) && count($initialConfig['type_infos']['keywords']) > 0) {
                        $configRequestData['type_infos']['keywords'] = $initialConfig['type_infos']['keywords'];
                    }

                    $endpoints = $this->plageApi->datastore->getEndpoints($datastoreId);
                    $filteredEndpoints = array_filter($endpoints, [$this, 'filterTMSEndpoints']);

                    if (1 != count($filteredEndpoints)) {
                        throw new AppException('Il y a plusieurs endpoints de type WMTS-TMS', Response::HTTP_INTERNAL_SERVER_ERROR);
                    }

                    // suppression de l'ancien offering
                    $this->plageApi->configuration->removeOffering($datastoreId, $initialOffering['_id']);

                    // suppresion de l'ancienne config
                    $this->plageApi->configuration->remove($datastoreId, $initialConfig['_id']);

                    // Ajout de la nouvelle configuration
                    $configuration = $this->plageApi->configuration->add($datastoreId, $configRequestData);

                    // Publication de la nouvelle pyramide
                    $endpointId = $filteredEndpoints[0]['endpoint']['_id'];
                    $offering = $this->plageApi->configuration->publish($datastoreId, $configuration['_id'], $endpointId);

                    // Recuperation de l'URL
                    $url = $this->getUrl($offering['urls']);
                    $this->plageApi->storedData->addTags($datastoreId, $pyramidId, ['tms_url' => $url]);

                    // suppresion de l'ancienne donnée
                    $this->plageApi->storedData->remove($datastoreId, $initialPyramid['_id']);
                    $this->plageApi->storedData->removeTags($datastoreId, $pyramidId, ['initial_pyramid_id']);

                    $this->addFlash('success', 'Mise à jour de la pyramide effectuée avec succès');
                    break;

                case 'no': // on supprime la nouvelle "tout simplement"
                    // suppression de l'offering
                    $offering = $this->plageApi->configuration->getAllOfferings($datastoreId, [
                        'stored_data' => $pyramid['_id'],
                    ]);

                    $offering = $this->plageApi->configuration->getOffering($datastoreId, $offering[0]['_id']);
                    $this->plageApi->configuration->removeOffering($datastoreId, $offering['_id']);

                    // suppression de la config
                    $this->plageApi->configuration->remove($datastoreId, $offering['configuration']['_id']);

                    // suppression de la donnée
                    $this->plageApi->storedData->remove($datastoreId, $pyramidId);

                    // suppression du tag de la pyramide initiale
                    $this->plageApi->storedData->removeTags($datastoreId, $pyramid['tags']['initial_pyramid_id'], ['update_pyramid_id']);
                    $this->addFlash('notice', 'Mise à jour de la pyramide annulée');

                    break;
                default:
                    throw new AppException('Valeur du paramètre [validate] invalide', Response::HTTP_BAD_REQUEST);
            }
        } catch (PlageApiException|AppException $ex) {
            if (Response::HTTP_NOT_FOUND == $ex->getCode()) {
                $this->addFlash('error', "Aucun flux ne correspond à l'identifiant [$pyramidId]");
            }
            $this->addFlash('error', $ex->getMessage());
        }

        return $this->redirectToRoute('plage_datastore_view', [
            'datastoreId' => $datastoreId,
        ]);
    }

    /**
     * Visualiser et comparer les 2 pyramides.
     *
     * @Route("/{pyramidId}/sample-check", name="sample_check", methods={"GET"}, options={"expose"=true})
     */
    public function sampleCheck($datastoreId, $pyramidId)
    {
        $pyramid = null; // la pyramide servant de mise à jour complète
        try {
            $pyramid = $this->plageApi->storedData->get($datastoreId, $pyramidId);

            if (!array_key_exists('tms_url', $pyramid['tags'])) {
                $uid = Utils::generateUid();
                $sampleName = "sample_$uid";
                $procCreatPyramid = $this->plageApi->processing->getExecution($datastoreId, $pyramid['tags']['proc_pyr_creat_id']);

                $endpoints = $this->plageApi->datastore->getEndpoints($datastoreId);
                $filteredEndpoints = array_filter($endpoints, [$this, 'filterTMSEndpoints']);

                if (1 != count($filteredEndpoints)) {
                    throw new AppException('Il y a plusieurs endpoints de type WMTS-TMS', Response::HTTP_INTERNAL_SERVER_ERROR);
                }

                // Ajout de la configuration
                $configuration = $this->plageApi->configuration->create($datastoreId, $pyramidId, $sampleName, $sampleName, $sampleName, $sampleName, $procCreatPyramid['parameters']['bottom_level'], $procCreatPyramid['parameters']['top_level']);

                // Publication
                $endpointId = $filteredEndpoints[0]['endpoint']['_id'];
                $offering = $this->plageApi->configuration->publish($datastoreId, $configuration['_id'], $endpointId);

                // Recuperation de l'URL
                $url = $this->getUrl($offering['urls']);
                $this->plageApi->storedData->addTags($datastoreId, $pyramidId, ['tms_url' => $url]);

                $pyramid = $this->plageApi->storedData->get($datastoreId, $pyramidId);
            }
        } catch (PlageApiException|AppException $ex) {
            if (Response::HTTP_NOT_FOUND == $ex->getCode()) {
                $this->addFlash('error', "Aucun flux ne correspond à l'identifiant [$pyramidId]");
            }
            $this->addFlash('error', $ex->getMessage());
        }

        return $this->render('pages/pyramid/sample_check.html.twig', [
            'datastore' => $this->plageApi->datastore->get($datastoreId),
            'pyramid' => $pyramid,
        ]);
    }

    /**
     * Visualiser et comparer les 2 pyramides.
     *
     * @Route("/{pyramidId}/sample-validate", name="sample_validate", methods={"GET"})
     */
    public function sampleValidate($datastoreId, $pyramidId, Request $request)
    {
        try {
            if (!$request->query->has('validate')) {
                throw new AppException('Paramètre query [validate] manquant', Response::HTTP_BAD_REQUEST);
            }
            $validate = $request->query->get('validate');

            $pyramid = $this->plageApi->storedData->get($datastoreId, $pyramidId);
            if (!array_key_exists('is_sample', $pyramid['tags']) || !$pyramid['tags']['is_sample']) {
                throw new AppException("La pyramide n'est pas une pyramide échantillon", Response::HTTP_BAD_REQUEST);
            }

            switch ($validate) {
                case 'yes':
                    // Valider l'échantillon (on supprime l'échantillon et on lance la génération avec les mêmes paramètres mais sans le paramètre bbox)
                    // suppression de l'offering, puis la config
                    $offering = $this->plageApi->configuration->getAllOfferings($datastoreId, [
                        'stored_data' => $pyramid['_id'],
                    ]);

                    $offering = $this->plageApi->configuration->getOffering($datastoreId, $offering[0]['_id']);
                    $configuration = $this->plageApi->configuration->get($datastoreId, $offering['configuration']['_id']);

                    $this->plageApi->configuration->removeOffering($datastoreId, $offering['_id']);
                    $this->plageApi->configuration->remove($datastoreId, $configuration['_id']);

                    // suppression de la pyramide échantillon
                    $this->plageApi->storedData->remove($datastoreId, $pyramid['_id']);

                    // création pyramide sans bbox
                    $procCreatPyramidSample = $this->plageApi->processing->getExecution($datastoreId, $pyramid['tags']['proc_pyr_creat_id']);
                    $parameters = $procCreatPyramidSample['parameters'];
                    unset($parameters['bbox']);

                    $requestBody = [
                        'processing' => $procCreatPyramidSample['processing']['_id'],
                        'inputs' => ['stored_data' => [$procCreatPyramidSample['inputs']['stored_data'][0]['_id']]],
                        'output' => ['stored_data' => ['name' => $procCreatPyramidSample['output']['stored_data']['name']]],
                        'parameters' => $parameters,
                    ];

                    $newProcCreatPyramid = $this->plageApi->processing->addExecution($datastoreId, $requestBody);

                    $this->plageApi->storedData->addTags($datastoreId, $newProcCreatPyramid['output']['stored_data']['_id'], [
                        'upload_id' => $pyramid['tags']['upload_id'],
                        'proc_int_id' => $pyramid['tags']['proc_int_id'],
                        'vectordb_id' => $pyramid['tags']['vectordb_id'],
                        'proc_pyr_creat_id' => $newProcCreatPyramid['_id'],
                    ]);

                    $this->plageApi->processing->launchExecution($datastoreId, $newProcCreatPyramid['_id']);

                    $this->addFlash('success', "Création de la pyramide sur l'emprise complète des données a été lancée avec succès");
                    break;

                case 'no':
                    return $this->redirectToRoute('plage_pyramid_add', [
                        'datastoreId' => $datastoreId,
                        'vectordbId' => $pyramid['tags']['vectordb_id'],
                        'procCreatPyramidSampleId' => $pyramid['tags']['proc_pyr_creat_id'],
                        'samplePyramidId' => $pyramid['_id']
                    ]);

                default:
                    throw new AppException('Valeur du paramètre [validate] invalide', Response::HTTP_BAD_REQUEST);
            }
        } catch (PlageApiException|AppException $ex) {
            if (Response::HTTP_NOT_FOUND == $ex->getCode()) {
                $this->addFlash('error', "Aucun flux ne correspond à l'identifiant [$pyramidId]");
            }
            $this->addFlash('error', $ex->getMessage());
        }

        return $this->redirectToRoute('plage_datastore_view', [
            'datastoreId' => $datastoreId,
        ]);
    }

    /**
     * Find tms endpoint url.
     */
    private function getTMSEndpoint($datastore)
    {
        $tmsUrl = null;

        $filteredEndpoints = array_filter($datastore['endpoints'], [$this, 'filterTMSEndpoints']);

        if (1 != count($filteredEndpoints)) {
            $this->addFlash('error', "Impossible d'associer un endpoint TMS sur lequel seront publiées les tuiles vectorielles.");
        }

        $tmsUrl = $this->getUrl($filteredEndpoints[0]['endpoint']['urls']);

        return $tmsUrl;
    }

    /**
     * Callback function for array_filter.
     */
    private function filterTMSEndpoints($arrayElement)
    {
        $endpoint = $arrayElement['endpoint'];

        return 'WMTS-TMS' == $endpoint['type'];
    }

    /**
     * Retourne vrai si la table contient des colonnes geometriques.
     *
     * @param array $table
     *
     * @return bool
     */
    private function hasGeometricColumns($table)
    {
        if (!isset($table['attributes'])) {
            return false;
        }

        $filtered = array_filter($table['attributes'], function ($type, $name) {
            return preg_match('/^geometry/', $type);
        }, ARRAY_FILTER_USE_BOTH);

        return 1 == count($filtered);
    }
}
