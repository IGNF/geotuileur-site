<?php

namespace App\Service\PlageApi;

class ProcessingApiService extends AbstractPlageApiService
{
    public function getAll($datastoreId, $query = [])
    {
        return $this->request('GET', "datastores/$datastoreId/processings", [], $query);
    }

    public function get($datastoreId, $processingId)
    {
        return $this->request('GET', "datastores/$datastoreId/processings/$processingId");
    }

    public function addExecution($datastoreId, $body = [])
    {
        return $this->request('POST', "datastores/$datastoreId/processings/executions", $body);
    }

    public function launchExecution($datastoreId, $executionId)
    {
        return $this->request('POST', "datastores/$datastoreId/processings/executions/$executionId/launch");
    }

    public function getAllExecutions($datastoreId, $query = [])
    {
        return $this->request('GET', "datastores/$datastoreId/processings/executions", [], $query);
    }

    public function getExecution($datastoreId, $processingExecutionId)
    {
        return $this->request('GET', "datastores/$datastoreId/processings/executions/$processingExecutionId");
    }

    public function getExecutionLogs($datastoreId, $processingExecutionId)
    {
        return $this->request('GET', "datastores/$datastoreId/processings/executions/$processingExecutionId/logs", [], [], [], false, false);
    }

    public function removeExecution($datastoreId, $processingExecutionId)
    {
        return $this->request('DELETE', "datastores/$datastoreId/processings/executions/$processingExecutionId");
    }

    public function getExecutionInfo($datastoreId, $processingExecutionId)
    {
        $result = [];
        $processingExecution = $this->getExecution($datastoreId, $processingExecutionId);
        $result['info'] = $this->getDatesAndDelays($processingExecution);
        $result['logs'] = $this->getExecutionLogs($datastoreId, $processingExecutionId);

        return $result;
    }

    private function getDatesAndDelays($infos)
    {
        $result = [
            'creation' => null,
            'start' => null,
            'finish' => null,
        ];

        $creationDate = null;
        $startDate = null;
        $finishDate = null;
        if (isset($infos['creation'])) {
            $creationDate = new \DateTime($infos['creation']);
            $result['creation'] = $creationDate->format('H:i:s');
        }
        if (isset($infos['start'])) {
            $startDate = new \DateTime($infos['start']);
            $result['start'] = $startDate->format('H:i:s');
        }
        if (isset($infos['finish'])) {
            $finishDate = new \DateTime($infos['finish']);
            $result['finish'] = $finishDate->format('H:i:s');
        }

        // Calcul des durees
        if ($startDate && $creationDate) {
            $interval = $startDate->diff($creationDate);
            $result['start_delay'] = $this->formatInterval($interval);
        }
        if ($finishDate && $startDate) {
            $interval = $finishDate->diff($startDate);
            $result['exec_delay'] = $this->formatInterval($interval);
        }

        return $result;
    }

    private function formatInterval(\DateInterval $interval)
    {
        $format = '';
        if ($interval->d) {
            ($interval->d > 1) ? $format .= '%d jours' : '%d jour';
        }
        if ($interval->i) {
            $formatMin = ($interval->i > 1) ? '%i minutes' : '%i minute';
            if ($format) {
                $format .= ' ';
            }

            $format .= $formatMin;
        }

        if ($format) {
            $format .= ' ';
        }

        $format .= '%s secondes';

        return $interval->format($format);
    }
}
