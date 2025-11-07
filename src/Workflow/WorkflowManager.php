<?php

declare(strict_types=1);

namespace CleaniqueCoders\Eligify\Workflow;

use CleaniqueCoders\Eligify\Audit\AuditLogger;
use CleaniqueCoders\Eligify\Events\EvaluationCompleted;
use CleaniqueCoders\Eligify\Models\Criteria;

class WorkflowManager
{
    protected array $callbacks = [];

    protected array $config;

    protected ?AuditLogger $auditLogger = null;

    public function __construct()
    {
        $this->config = config('eligify.workflow', [
            'enabled' => true,
            'dispatch_events' => true,
            'log_callback_errors' => true,
            'fail_on_callback_error' => false,
        ]);

        if (config('eligify.audit.enabled', true)) {
            $this->auditLogger = app(AuditLogger::class);
        }
    }

    /**
     * Register a callback for a specific event
     */
    public function addCallback(string $event, callable $callback, array $conditions = []): self
    {
        if (! isset($this->callbacks[$event])) {
            $this->callbacks[$event] = [];
        }

        $this->callbacks[$event][] = [
            'callback' => $callback,
            'conditions' => $conditions,
        ];

        return $this;
    }

    /**
     * Execute callbacks for a specific event
     */
    public function executeCallbacks(string $event, array $context = []): void
    {
        // Check if workflow is enabled
        if (! ($this->config['enabled'] ?? true)) {
            return;
        }

        if (! isset($this->callbacks[$event])) {
            return;
        }

        $executionStart = microtime(true);
        $executedCallbacks = 0;
        $errors = [];

        foreach ($this->callbacks[$event] as $callbackData) {
            if ($this->shouldExecuteCallback($callbackData['conditions'], $context)) {
                try {
                    $this->executeCallback($callbackData['callback'], $context);
                    $executedCallbacks++;
                } catch (\Throwable $e) {
                    $errors[] = $e->getMessage();
                }
            }
        }

        $executionTime = microtime(true) - $executionStart;

        // Log workflow execution if audit logger is available
        if ($this->auditLogger && isset($context['criteria'])) {
            $this->auditLogger->logWorkflowExecution(
                $context['criteria'],
                $event,
                [
                    'callbacks_executed' => $executedCallbacks,
                    'total_callbacks' => count($this->callbacks[$event]),
                    'context_keys' => array_keys($context),
                ],
                $executionTime,
                empty($errors),
                implode('; ', $errors) ?: null
            );
        }
    }

    /**
     * Execute a single callback with error handling and timeout
     */
    protected function executeCallback(callable $callback, array $context): void
    {
        $callbackStart = microtime(true);
        $success = false;
        $error = null;

        try {
            // Set execution timeout if configured
            $timeout = $this->config['callback_timeout'] ?? 30;

            if ($timeout > 0) {
                set_time_limit($timeout);
            }

            call_user_func($callback, $context['data'] ?? [], $context['result'] ?? [], $context);
            $success = true;
        } catch (\Throwable $e) {
            $error = $e->getMessage();

            // Log callback execution errors
            if ($this->config['log_callback_errors'] ?? true) {
                logger()->error('Eligify callback execution failed', [
                    'error' => $e->getMessage(),
                    'context' => $context,
                ]);
            }

            // Re-throw if configured to fail on callback errors
            if ($this->config['fail_on_callback_error'] ?? false) {
                throw $e;
            }
        } finally {
            $executionTime = microtime(true) - $callbackStart;

            // Log individual callback execution if audit logger is available
            if ($this->auditLogger && isset($context['criteria'])) {
                $this->auditLogger->logCallbackExecution(
                    $context['criteria'],
                    'callback_execution',
                    $success,
                    $executionTime,
                    $error
                );
            }
        }
    }

    /**
     * Check if callback should be executed based on conditions
     */
    protected function shouldExecuteCallback(array $conditions, array $context): bool
    {
        if (empty($conditions)) {
            return true;
        }

        $result = $context['result'] ?? [];
        $data = $context['data'] ?? [];

        foreach ($conditions as $condition => $expectedValue) {
            switch ($condition) {
                case 'min_score':
                    if (($result['score'] ?? 0) < $expectedValue) {
                        return false;
                    }
                    break;

                case 'max_score':
                    if (($result['score'] ?? 0) > $expectedValue) {
                        return false;
                    }
                    break;

                case 'passed':
                    if (($result['passed'] ?? false) !== $expectedValue) {
                        return false;
                    }
                    break;

                case 'failed_rules_count':
                    $failedCount = count($result['failed_rules'] ?? []);
                    if ($failedCount !== $expectedValue) {
                        return false;
                    }
                    break;

                case 'field_equals':
                    [$field, $value] = $expectedValue;
                    if (($data[$field] ?? null) !== $value) {
                        return false;
                    }
                    break;

                case 'score_range':
                    [$min, $max] = $expectedValue;
                    $score = $result['score'] ?? 0;
                    if ($score < $min || $score > $max) {
                        return false;
                    }
                    break;

                case 'custom':
                    // Support custom condition evaluation
                    if (is_callable($expectedValue)) {
                        if (! call_user_func($expectedValue, $context)) {
                            return false;
                        }
                    }
                    break;

                default:
                    // Support other custom condition evaluations
                    if (is_callable($expectedValue)) {
                        if (! call_user_func($expectedValue, $context)) {
                            return false;
                        }
                    }
                    break;
            }
        }

        return true;
    }

    /**
     * Execute workflow for evaluation lifecycle
     */
    public function executeEvaluationWorkflow(Criteria $criteria, array $data, array $result): void
    {
        $context = [
            'criteria' => $criteria,
            'data' => $data,
            'result' => $result,
        ];

        // Execute lifecycle callbacks
        $this->executeCallbacks('before_evaluation', $context);
        $this->executeCallbacks('after_evaluation', $context);

        // Execute result-based callbacks
        if ($result['passed']) {
            $this->executeCallbacks('on_pass', $context);

            // Execute score-based callbacks
            $score = $result['score'] ?? 0;
            if ($score >= 90) {
                $this->executeCallbacks('on_excellent', $context);
            } elseif ($score >= 80) {
                $this->executeCallbacks('on_good', $context);
            }
        } else {
            $this->executeCallbacks('on_fail', $context);
        }

        // Execute conditional callbacks
        $this->executeCallbacks('on_condition', $context);

        // Dispatch Laravel event if configured
        if ($this->config['dispatch_events'] ?? true) {
            event(new EvaluationCompleted($criteria, $data, $result));
        }
    }

    /**
     * Add a conditional callback
     */
    public function addConditionalCallback(callable $callback, array $conditions): self
    {
        return $this->addCallback('on_condition', $callback, $conditions);
    }

    /**
     * Add a score-range callback
     */
    public function addScoreRangeCallback(int $minScore, int $maxScore, callable $callback): self
    {
        return $this->addCallback('on_condition', $callback, [
            'score_range' => [$minScore, $maxScore],
        ]);
    }

    /**
     * Clear all callbacks for an event
     */
    public function clearCallbacks(string $event): self
    {
        unset($this->callbacks[$event]);

        return $this;
    }

    /**
     * Clear all callbacks
     */
    public function clearAllCallbacks(): self
    {
        $this->callbacks = [];

        return $this;
    }

    /**
     * Get all registered callbacks
     */
    public function getCallbacks(): array
    {
        return $this->callbacks;
    }

    /**
     * Get the current configuration
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Set configuration (for testing)
     */
    public function setConfig(array $config): self
    {
        $this->config = array_merge($this->config, $config);

        return $this;
    }

    /**
     * Execute async callback (queue job)
     */
    public function executeAsyncCallback(callable $callback, array $context): void
    {
        if ($this->config['enable_async_callbacks'] ?? false) {
            // Dispatch as job if queue is configured
            // This would require creating a job class
            dispatch(function () use ($callback, $context) {
                $this->executeCallback($callback, $context);
            });
        } else {
            $this->executeCallback($callback, $context);
        }
    }
}
