<?php

namespace App\Components\Workflow;

use App\Constants\ProcessingStatuses;
use App\Constants\StoredDataStatuses;
use App\Constants\UploadCheckTypes;
use App\Constants\UploadStatuses;
use App\Service\PlageApiService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class IntegrationWorkflow extends AbstractWorkflow
{
    public const WORKFLOW_INTEGRATION = [
        'SEND_FILES_API' => 'sendFilesApi',
        'UPLOAD_CHECKS' => 'uploadChecks',
        'INTEGRATION_PROCESSING' => 'integrationProcessing',
    ];

    public function __construct($steps = null)
    {
        if (!$steps) {
            $steps = $this::WORKFLOW_INTEGRATION;
        }
        parent::__construct($steps);
    }

    public function sendFilesApi($args = [])
    {
        $datastoreId = $args['datastoreId'];
        $upload = $args['upload'];

        /** @var PlageApiService */
        $plageApi = $args['plageApi'];

        if (UploadStatuses::CLOSED == $upload['status']) {
            $plageApi->upload->open($datastoreId, $upload['_id']);
        }

        // augmenter temporairement la limite de mémoire
        ini_set('memory_limit', '512M');
        $plageApi->upload->addFile($datastoreId, $upload['_id'], $upload['tags']['file_data']);
        ini_restore('memory_limit');
    }

    public function sendFilesApiCheckFinished($args = [])
    {
        $upload = $args['upload'];

        return UploadStatuses::CLOSED == $upload['status'];
    }

    /**
     * @SuppressWarnings("unused")
     */
    public function sendFilesApiCheckSuccess($args = [])
    {
        return true;
    }

    /**
     * @SuppressWarnings("unused")
     */
    public function uploadChecks($args = [])
    {
        // nothing to do, API orchestrator will launch the job automatically
    }

    public function uploadChecksCheckFinished($args = [])
    {
        $datastoreId = $args['datastoreId'];
        $upload = $args['upload'];

        /** @var PlageApiService */
        $plageApi = $args['plageApi'];

        $uploadChecks = $plageApi->upload->getCheckExecutions($datastoreId, $upload['_id']);

        return 0 == count($uploadChecks[UploadCheckTypes::ASKED]) && 0 == count($uploadChecks[UploadCheckTypes::IN_PROGRESS]); // no "asked" or "in_progress" check left
    }

    public function uploadChecksCheckSuccess($args = [])
    {
        $datastoreId = $args['datastoreId'];
        $upload = $args['upload'];

        /** @var PlageApiService */
        $plageApi = $args['plageApi'];

        $uploadChecks = $plageApi->upload->getCheckExecutions($datastoreId, $upload['_id']);

        return 0 == count($uploadChecks[UploadCheckTypes::FAILED]); // no check has failed
    }

    public function integrationProcessing($args = [])
    {
        $datastoreId = $args['datastoreId'];
        $upload = $args['upload'];

        /** @var PlageApiService */
        $plageApi = $args['plageApi'];

        /** @var ParameterBagInterface */
        $params = $args['params'];

        /** @var array */
        $apiPlageProcessings = $params->get('api_plage_processings');
        $procExecBody = [
            'processing' => $apiPlageProcessings['int_vect_files_db'],
            'inputs' => [
                'upload' => [$upload['_id']],
            ],
            'output' => [
                'stored_data' => ['name' => $upload['name']],
            ],
        ];

        $processingExec = $plageApi->processing->addExecution($datastoreId, $procExecBody);
        $vectorDb = $processingExec['output']['stored_data'];

        // add tags on upload
        $plageApi->upload->addTags($datastoreId, $upload['_id'], [
            'vectordb_id' => $vectorDb['_id'],
            'proc_int_id' => $processingExec['_id'],
        ]);

        // add tags on vectordb stored data
        $plageApi->storedData->addTags($datastoreId, $vectorDb['_id'], [
            'upload_id' => $upload['_id'],
            'proc_int_id' => $processingExec['_id'],
        ]);

        // check if current upload is for a pyramid update
        if (array_key_exists('initial_pyramid_id', $upload['tags'])) {
            $plageApi->storedData->addTags($datastoreId, $vectorDb['_id'], [
                'initial_pyramid_id' => $upload['tags']['initial_pyramid_id'],
            ]);
        }

        $plageApi->processing->launchExecution($datastoreId, $processingExec['_id']);
    }

    public function integrationProcessingCheckFinished($args = [])
    {
        $datastoreId = $args['datastoreId'];
        $upload = $args['upload'];

        /** @var PlageApiService */
        $plageApi = $args['plageApi'];

        $processingExec = $plageApi->processing->getExecution($datastoreId, $upload['tags']['proc_int_id']);

        return !in_array($processingExec['status'], [ProcessingStatuses::CREATED, ProcessingStatuses::WAITING, ProcessingStatuses::PROGRESS]);
    }

    public function integrationProcessingCheckSuccess($args = [])
    {
        $datastoreId = $args['datastoreId'];
        $upload = $args['upload'];

        /** @var PlageApiService */
        $plageApi = $args['plageApi'];

        $processingExec = $plageApi->processing->getExecution($datastoreId, $upload['tags']['proc_int_id']);
        $vectordb = $plageApi->storedData->get($datastoreId, $upload['tags']['vectordb_id']);

        return ProcessingStatuses::SUCCESS == $processingExec['status'] && StoredDataStatuses::GENERATED == $vectordb['status'];
    }
}
