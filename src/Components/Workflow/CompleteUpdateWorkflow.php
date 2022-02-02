<?php

namespace App\Components\Workflow;

use App\Service\PlageApiService;

class CompleteUpdateWorkflow extends IntegrationWorkflow
{
    public const WORKFLOW_INTEGRATION = [
        'SEND_FILES_API' => 'sendFilesApi',
        'UPLOAD_CHECKS' => 'uploadChecks',
        'INTEGRATION_PROCESSING' => 'integrationProcessing',
        'LAUNCH_PYRAMID_CREAT_PROCESSING' => 'launchPyramidCreationProcessing',
    ];

    public function __construct()
    {
        parent::__construct($this::WORKFLOW_INTEGRATION);
    }

    /**
     * fonctions héritées depuis IntegrationWorkflow.
     *
     * sendFilesApi
     * sendFilesApiCheckFinished
     * sendFilesApiCheckSuccess
     * uploadChecks
     * uploadChecksCheckFinished
     * uploadChecksCheckSuccess
     * integrationProcessing
     * integrationProcessingCheckFinished
     * integrationProcessingCheckSuccess
     * integrationProcessingActionAfterSuccess
     */
    public function launchPyramidCreationProcessing($args = [])
    {
        /** @var PlageApiService */
        $plageApi = $args['plageApi'];

        $datastoreId = $args['datastoreId'];
        $upload = $args['upload'];

        $vectordb = $plageApi->storedData->get($datastoreId, $upload['tags']['vectordb_id']);

        // récupère ancienne pyramide
        $initialPyramid = $plageApi->storedData->get($datastoreId, $vectordb['tags']['initial_pyramid_id']);

        // récupère ancienne exécution de traitement de création de la pyramide initiale
        $initialPyramidProcExec = $plageApi->processing->getExecution($datastoreId, $initialPyramid['tags']['proc_pyr_creat_id']);

        // crée nouvelle exécution avec les mêmes paramètres qu'avant
        $requestBody = [
            'processing' => $initialPyramidProcExec['processing']['_id'],
            'inputs' => ['stored_data' => [$vectordb['_id']]],
            'output' => ['stored_data' => ['name' => $vectordb['name']]],
            'parameters' => $initialPyramidProcExec['parameters'],
        ];

        $updateProcessingExecution = $plageApi->processing->addExecution($datastoreId, $requestBody);
        $updatePyramidId = $updateProcessingExecution['output']['stored_data']['_id'];

        // ajout tags sur le vectordb
        $plageApi->storedData->addTags($datastoreId, $vectordb['_id'], [
            'pyramid_id' => $updatePyramidId,
        ]);

        // ajout tags sur la nouvelle pyramide
        $plageApi->storedData->addTags($datastoreId, $updatePyramidId, [
            'upload_id' => $vectordb['tags']['upload_id'],
            'proc_int_id' => $vectordb['tags']['proc_int_id'],
            'vectordb_id' => $vectordb['_id'],
            'proc_pyr_creat_id' => $updateProcessingExecution['_id'],
            'initial_pyramid_id' => $vectordb['tags']['initial_pyramid_id'],
        ]);

        // ajout tags sur l'ancienne pyramide
        $plageApi->storedData->addTags($datastoreId, $initialPyramid['_id'], [
            'update_pyramid_id' => $updatePyramidId,
        ]);

        // lancer le traitement
        $plageApi->processing->launchExecution($datastoreId, $updateProcessingExecution['_id']);
    }

    public function launchPyramidCreationProcessingCheckFinished($args = [])
    {
        return true; // on se contente de lancer le traitement, on ne vérifie pas ici s'il a terminé
    }

    public function launchPyramidCreationProcessingCheckSuccess($args = [])
    {
        return true; // on se contente de lancer le traitement, on ne vérifie pas ici s'il a réussi
    }
}
