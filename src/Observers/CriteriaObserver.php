<?php

namespace CleaniqueCoders\Eligify\Observers;

use CleaniqueCoders\Eligify\Audit\AuditLogger;
use CleaniqueCoders\Eligify\Models\Criteria;

class CriteriaObserver
{
    protected AuditLogger $auditLogger;

    // Store original attributes temporarily
    protected static array $originalAttributes = [];

    public function __construct(AuditLogger $auditLogger)
    {
        $this->auditLogger = $auditLogger;
    }

    /**
     * Handle the Criteria "created" event.
     */
    public function created(Criteria $criteria): void
    {
        $this->auditLogger->logCriteriaCreated($criteria);
    }

    /**
     * Handle the Criteria "updating" event.
     */
    public function updating(Criteria $criteria): void
    {
        // Store original attributes before update using model key
        static::$originalAttributes[$criteria->getKey()] = $criteria->getOriginal();
    }

    /**
     * Handle the Criteria "updated" event.
     */
    public function updated(Criteria $criteria): void
    {
        $originalAttributes = static::$originalAttributes[$criteria->getKey()] ?? [];

        // Clean up stored attributes
        unset(static::$originalAttributes[$criteria->getKey()]);

        // Check if this is an activation/deactivation
        if (isset($originalAttributes['is_active']) &&
            $originalAttributes['is_active'] !== $criteria->is_active) {

            if ($criteria->is_active) {
                $this->auditLogger->logCriteriaActivated($criteria);
            } else {
                $this->auditLogger->logCriteriaDeactivated($criteria);
            }
        }

        // Log general update
        $this->auditLogger->logCriteriaUpdated($criteria, $originalAttributes);
    }

    /**
     * Handle the Criteria "deleted" event.
     */
    public function deleted(Criteria $criteria): void
    {
        $this->auditLogger->log('criteria_deleted', $criteria, [
            'criteria_name' => $criteria->name,
            'criteria_slug' => $criteria->slug,
            'deleted_at' => now(),
        ], $criteria->getAttributes());
    }
}
