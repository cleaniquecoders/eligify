<?php

declare(strict_types=1);

namespace CleaniqueCoders\Eligify\Listeners;

use CleaniqueCoders\Eligify\Audit\AuditLogger;
use CleaniqueCoders\Eligify\Events\RuleExecuted;

class LogRuleExecuted
{
    protected AuditLogger $auditLogger;

    public function __construct(AuditLogger $auditLogger)
    {
        $this->auditLogger = $auditLogger;
    }

    /**
     * Handle the event.
     */
    public function handle(RuleExecuted $event): void
    {
        // Log the rule execution event
        $this->auditLogger->log('rule_event_executed', $event->rule->criteria, [
            'event_triggered' => true,
            'rule_id' => $event->rule->id,
            'rule_field' => $event->rule->field,
            'rule_operator' => $event->rule->operator,
            'rule_expected_value' => $event->rule->expected_value,
            'actual_value' => $event->data[$event->rule->field] ?? null,
            'rule_passed' => $event->result['passed'] ?? false,
            'rule_score' => $event->result['score'] ?? 0,
            'criteria_name' => $event->rule->criteria->name,
            'triggered_at' => now()->toISOString(),
        ]);
    }
}
