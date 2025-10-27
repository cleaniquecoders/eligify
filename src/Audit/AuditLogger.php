<?php

namespace CleaniqueCoders\Eligify\Audit;

use CleaniqueCoders\Eligify\Models\AuditLog;
use CleaniqueCoders\Eligify\Models\Criteria;
use CleaniqueCoders\Eligify\Models\Rule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuditLogger
{
    protected array $config;

    public function __construct()
    {
        $this->config = config('eligify.audit', []);
    }

    /**
     * Log an audit event
     */
    public function log(
        string $event,
        Model $auditable,
        array $context = [],
        array $beforeState = [],
        array $afterState = []
    ): ?AuditLog {
        if (! $this->shouldLog($event)) {
            return null;
        }

        $auditData = [
            'uuid' => (string) Str::uuid(),
            'event' => $event,
            'auditable_type' => get_class($auditable),
            'auditable_id' => $auditable->getKey(),
            'context' => $this->sanitizeContext($context),
            'user_id' => Auth::id(),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ];

        // Add before/after state for change events
        if (! empty($beforeState) || ! empty($afterState)) {
            $auditData['context']['before_state'] = $beforeState;
            $auditData['context']['after_state'] = $afterState;
        }

        return AuditLog::create($auditData);
    }

    /**
     * Log evaluation completed event
     */
    public function logEvaluation(Criteria $criteria, array $data, array $result): ?AuditLog
    {
        return $this->log('evaluation_completed', $criteria, [
            'criteria_name' => $criteria->name,
            'input_data' => $this->shouldIncludeSensitiveData() ? $data : ['[REDACTED]'],
            'result' => $result,
            'execution_time' => $result['execution_time'] ?? null,
        ]);
    }

    /**
     * Log rule creation
     */
    public function logRuleCreated(Rule $rule): ?AuditLog
    {
        return $this->log('rule_created', $rule, [
            'rule_field' => $rule->field,
            'rule_operator' => $rule->operator,
            'rule_value' => $rule->expected_value,
            'criteria_id' => $rule->criteria_id,
        ]);
    }

    /**
     * Log rule update with before/after state
     */
    public function logRuleUpdated(Rule $rule, array $originalAttributes): ?AuditLog
    {
        $changes = [];
        foreach ($rule->getDirty() as $key => $newValue) {
            if (isset($originalAttributes[$key])) {
                $changes[$key] = [
                    'from' => $originalAttributes[$key],
                    'to' => $newValue,
                ];
            }
        }

        return $this->log('rule_updated', $rule, [
            'changes' => $changes,
            'rule_field' => $rule->field,
            'criteria_id' => $rule->criteria_id,
        ], $originalAttributes, $rule->getAttributes());
    }

    /**
     * Log rule deletion
     */
    public function logRuleDeleted(Rule $rule): ?AuditLog
    {
        return $this->log('rule_deleted', $rule, [
            'rule_field' => $rule->field,
            'rule_operator' => $rule->operator,
            'rule_value' => $rule->expected_value,
            'criteria_id' => $rule->criteria_id,
        ], $rule->getAttributes());
    }

    /**
     * Log rule execution
     */
    public function logRuleExecuted(Rule $rule, array $data, bool $passed): ?AuditLog
    {
        return $this->log('rule_executed', $rule, [
            'rule_field' => $rule->field,
            'rule_operator' => $rule->operator,
            'rule_value' => $rule->expected_value,
            'input_data' => $data,
            'passed' => $passed,
            'criteria_id' => $rule->criteria_id,
        ]);
    }

    /**
     * Log criteria operations
     */
    public function logCriteriaCreated(Criteria $criteria): ?AuditLog
    {
        return $this->log('criteria_created', $criteria, [
            'criteria_name' => $criteria->name,
            'criteria_slug' => $criteria->slug,
            'description' => $criteria->description,
        ]);
    }

    public function logCriteriaUpdated(Criteria $criteria, array $originalAttributes): ?AuditLog
    {
        $changes = [];
        foreach ($criteria->getDirty() as $key => $newValue) {
            if (isset($originalAttributes[$key])) {
                $changes[$key] = [
                    'from' => $originalAttributes[$key],
                    'to' => $newValue,
                ];
            }
        }

        return $this->log('criteria_updated', $criteria, [
            'changes' => $changes,
            'criteria_name' => $criteria->name,
        ], $originalAttributes, $criteria->getAttributes());
    }

    public function logCriteriaActivated(Criteria $criteria): ?AuditLog
    {
        return $this->log('criteria_activated', $criteria, [
            'criteria_name' => $criteria->name,
            'activated_at' => now(),
        ]);
    }

    public function logCriteriaDeactivated(Criteria $criteria): ?AuditLog
    {
        return $this->log('criteria_deactivated', $criteria, [
            'criteria_name' => $criteria->name,
            'deactivated_at' => now(),
        ]);
    }

    /**
     * Log workflow execution
     */
    public function logWorkflowExecution(
        Criteria $criteria,
        string $event,
        array $context,
        ?float $executionTime = null,
        bool $success = true,
        ?string $error = null
    ): ?AuditLog {
        return $this->log('workflow_executed', $criteria, [
            'workflow_event' => $event,
            'execution_time' => $executionTime,
            'success' => $success,
            'error' => $error,
            'workflow_context' => $context,
        ]);
    }

    /**
     * Log callback execution
     */
    public function logCallbackExecution(
        Criteria $criteria,
        string $callbackType,
        bool $success,
        ?float $executionTime = null,
        ?string $error = null
    ): ?AuditLog {
        return $this->log('callback_executed', $criteria, [
            'callback_type' => $callbackType,
            'success' => $success,
            'execution_time' => $executionTime,
            'error' => $error,
        ]);
    }

    /**
     * Get audit trail for a specific auditable model
     */
    public function getAuditTrail(Model $auditable, int $limit = 50): Collection
    {
        return AuditLog::where('auditable_type', get_class($auditable))
            ->where('auditable_id', $auditable->getKey())
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get audit logs by event type
     */
    public function getAuditsByEvent(string $event, int $limit = 100): Collection
    {
        return AuditLog::where('event', $event)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get audit logs within date range
     */
    public function getAuditsByDateRange(
        \DateTimeInterface $from,
        \DateTimeInterface $to,
        int $limit = 500
    ): Collection {
        return AuditLog::whereBetween('created_at', [$from, $to])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get audit logs by user
     */
    public function getAuditsByUser(int $userId, int $limit = 100): Collection
    {
        return AuditLog::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Search audit logs by context content
     */
    public function searchAudits(string $search, int $limit = 100): Collection
    {
        return AuditLog::where(function ($query) use ($search) {
            $query->where('event', 'like', "%{$search}%")
                ->orWhere('context', 'like', "%{$search}%")
                ->orWhereHas('auditable', function ($q) use ($search) {
                    // This will work for polymorphic relationships
                    $q->where('name', 'like', "%{$search}%");
                });
        })
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get audit statistics
     */
    public function getAuditStats(int $days = 30): array
    {
        $from = now()->subDays($days);

        $stats = AuditLog::where('created_at', '>=', $from)
            ->selectRaw('event, COUNT(*) as count')
            ->groupBy('event')
            ->pluck('count', 'event')
            ->toArray();

        return [
            'total_events' => array_sum($stats),
            'event_breakdown' => $stats,
            'period_days' => $days,
            'most_common_event' => array_keys($stats, max($stats))[0] ?? null,
        ];
    }

    /**
     * Clean up old audit logs based on retention policy
     */
    public function cleanup(): int
    {
        if (! $this->config['auto_cleanup']) {
            return 0;
        }

        $retentionDays = $this->config['retention_days'] ?? 365;
        $cutoffDate = now()->subDays($retentionDays);

        return AuditLog::where('created_at', '<', $cutoffDate)->delete();
    }

    /**
     * Export audit logs to array for reporting
     */
    public function exportAudits(
        ?\DateTimeInterface $from = null,
        ?\DateTimeInterface $to = null,
        array $events = []
    ): array {
        $query = AuditLog::query();

        if ($from && $to) {
            $query->whereBetween('created_at', [$from, $to]);
        }

        if (! empty($events)) {
            $query->whereIn('event', $events);
        }

        return $query->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($audit) {
                return [
                    'id' => $audit->id,
                    'uuid' => $audit->uuid,
                    'event' => $audit->event,
                    'auditable_type' => $audit->auditable_type,
                    'auditable_id' => $audit->auditable_id,
                    'user_id' => $audit->user_id,
                    'ip_address' => $audit->ip_address,
                    'context' => $audit->context,
                    'created_at' => $audit->created_at->toISOString(),
                ];
            })
            ->toArray();
    }

    /**
     * Check if an event should be logged
     */
    protected function shouldLog(string $event): bool
    {
        if (! ($this->config['enabled'] ?? true)) {
            return false;
        }

        $eventsToAudit = $this->config['events'] ?? [];

        return empty($eventsToAudit) || in_array($event, $eventsToAudit);
    }

    /**
     * Sanitize context data for logging
     */
    protected function sanitizeContext(array $context): array
    {
        if (! $this->shouldIncludeSensitiveData()) {
            // Remove potentially sensitive fields
            $sensitiveFields = ['password', 'token', 'secret', 'api_key', 'ssn', 'credit_card'];

            foreach ($sensitiveFields as $field) {
                if (isset($context[$field])) {
                    $context[$field] = '[REDACTED]';
                }
            }

            // Recursively sanitize nested arrays
            array_walk_recursive($context, function (&$value, $key) use ($sensitiveFields) {
                if (in_array(strtolower($key), $sensitiveFields)) {
                    $value = '[REDACTED]';
                }
            });
        }

        return $context;
    }

    /**
     * Check if sensitive data should be included in logs
     */
    protected function shouldIncludeSensitiveData(): bool
    {
        return $this->config['include_sensitive_data'] ?? false;
    }
}
