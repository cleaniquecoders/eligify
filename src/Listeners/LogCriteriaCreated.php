<?php

namespace CleaniqueCoders\Eligify\Listeners;

use CleaniqueCoders\Eligify\Audit\AuditLogger;
use CleaniqueCoders\Eligify\Events\CriteriaCreated;

class LogCriteriaCreated
{
    protected AuditLogger $auditLogger;

    public function __construct(AuditLogger $auditLogger)
    {
        $this->auditLogger = $auditLogger;
    }

    /**
     * Handle the event.
     */
    public function handle(CriteriaCreated $event): void
    {
        // Log the criteria creation event
        $this->auditLogger->log('criteria_event_created', $event->criteria, [
            'event_triggered' => true,
            'criteria_name' => $event->criteria->name,
            'criteria_slug' => $event->criteria->slug,
            'rules_count' => $event->criteria->rules()->count(),
            'is_active' => $event->criteria->is_active,
            'triggered_at' => now()->toISOString(),
        ]);
    }
}
