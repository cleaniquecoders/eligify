<?php

namespace CleaniqueCoders\Eligify\Listeners;

use CleaniqueCoders\Eligify\Audit\AuditLogger;
use CleaniqueCoders\Eligify\Events\EvaluationCompleted;

class LogEvaluationCompleted
{
    protected AuditLogger $auditLogger;

    public function __construct(AuditLogger $auditLogger)
    {
        $this->auditLogger = $auditLogger;
    }

    /**
     * Handle the event.
     */
    public function handle(EvaluationCompleted $event): void
    {
        // Log the evaluation completion event
        $this->auditLogger->log('evaluation_event_completed', $event->criteria, [
            'event_triggered' => true,
            'criteria_name' => $event->criteria->name,
            'result_passed' => $event->result['passed'] ?? false,
            'result_score' => $event->result['score'] ?? 0,
            'failed_rules_count' => count($event->result['failed_rules'] ?? []),
            'event_data_keys' => array_keys($event->data),
            'triggered_at' => now()->toISOString(),
        ]);
    }
}
