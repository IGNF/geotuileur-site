<?php

namespace App\Components\Workflow;

use App\Constants\WorkflowStepStatuses;
use Psr\Log\LoggerInterface;

class WorkflowRunner
{
    private const FUNC_SUFFIX_CHECK_FINISHED = 'CheckFinished';
    private const FUNC_SUFFIX_CHECK_SUCCESS = 'CheckSuccess';
    private const FUNC_SUFFIX_ACTION_AFTER_SUCCESS = 'ActionAfterSuccess';

    public static function runWorkflow($workflow, $args, LoggerInterface $logger)
    {
        if ($workflow->currentStep == count($workflow->steps)) {
            return $workflow->progress;
        }

        $currentStep = array_keys($workflow->progress)[$workflow->currentStep];

        switch ($workflow->progress[$currentStep]) {
            case WorkflowStepStatuses::WAITING:
                $function = [$workflow, $workflow->steps[$currentStep]];

                try {
                    $logger->info('{class} : Attempting to execute [{function}]', [
                        'class' => self::class,
                        'function' => $workflow->steps[$currentStep],
                    ]);

                    call_user_func_array($function, [$args]);
                    $workflow->progress[$currentStep] = WorkflowStepStatuses::IN_PROGRESS;

                    $logger->info('{class} : Executed successfully [{function}]', [
                        'class' => self::class,
                        'function' => $workflow->steps[$currentStep],
                    ]);
                } catch (\Throwable $th) {
                    $workflow->progress[$currentStep] = WorkflowStepStatuses::FAILURE;

                    $logger->info('{class} : Failed to execute [{function}]', [
                        'class' => self::class,
                        'function' => $workflow->steps[$currentStep],
                        'error' => $th,
                    ]);
                }

                break;

            case WorkflowStepStatuses::IN_PROGRESS:
                $function = [$workflow, $workflow->steps[$currentStep].self::FUNC_SUFFIX_CHECK_FINISHED];

                $finished = call_user_func_array($function, [$args]);

                if ($finished) {
                    $function = [$workflow, $workflow->steps[$currentStep].self::FUNC_SUFFIX_CHECK_SUCCESS];

                    $success = call_user_func_array($function, [$args]);

                    if ($success) {
                        // if there is an action after success
                        if (method_exists($workflow, $workflow->steps[$currentStep].self::FUNC_SUFFIX_ACTION_AFTER_SUCCESS)) {
                            try {
                                $function = [$workflow, $workflow->steps[$currentStep].self::FUNC_SUFFIX_ACTION_AFTER_SUCCESS];
                                call_user_func_array($function, [$args]);

                                // the current step is marked as success only if after action is also successful
                                $workflow->progress[$currentStep] = WorkflowStepStatuses::SUCCESS;
                                ++$workflow->currentStep;
                            } catch (\Throwable $th) {
                                $workflow->progress[$currentStep] = WorkflowStepStatuses::FAILURE;
                            }
                        } else {
                            // if there is no after success action, the step is marked as success
                            $workflow->progress[$currentStep] = WorkflowStepStatuses::SUCCESS;
                            ++$workflow->currentStep;
                        }
                    } else {
                        $workflow->progress[$currentStep] = WorkflowStepStatuses::FAILURE;
                    }
                }

                break;

            case WorkflowStepStatuses::SUCCESS:
                // throw new \Exception(WorkflowStepStatuses::SUCCESS);
                break;

            case WorkflowStepStatuses::FAILURE:
                // throw new \Exception(WorkflowStepStatuses::FAILURE);
                break;

            default:
                break;
        }
    }
}
