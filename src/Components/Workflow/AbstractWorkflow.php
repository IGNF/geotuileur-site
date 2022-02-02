<?php

namespace App\Components\Workflow;

use App\Constants\WorkflowStepStatuses;

abstract class AbstractWorkflow
{
    public $steps = [];
    public $progress = [];
    public $currentStep = 0;

    public function __construct($steps)
    {
        $this->steps = $steps;
        $this->progress = $this->getInitialProgress();
        $this->currentStep = 0;
    }

    public function getInitialProgress()
    {
        $initialProgress = [];
        foreach (array_keys($this->steps) as $stepName) {
            $initialProgress[$stepName] = WorkflowStepStatuses::WAITING;
        }

        return $initialProgress;
    }
}
