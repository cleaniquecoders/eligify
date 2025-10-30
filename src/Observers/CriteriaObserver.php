<?php

namespace CleaniqueCoders\Eligify\Observers;

use CleaniqueCoders\Eligify\Audit\AuditLogger;
use CleaniqueCoders\Eligify\Models\Criteria;
use CleaniqueCoders\Eligify\Support\Cache;

class CriteriaObserver
{
    protected AuditLogger $auditLogger;

    protected Cache $cache;

    // Store original attributes temporarily
    protected static array $originalAttributes = [];

    public function __construct(AuditLogger $auditLogger)
    {
        $this->auditLogger = $auditLogger;
        $this->cache = new Cache;
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

        // Invalidate cache after update
        $this->invalidateCache($criteria);
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

        // Invalidate cache after deletion
        $this->invalidateCache($criteria);
    }

    /**
     * Handle the Criteria "restored" event.
     */
    public function restored(Criteria $criteria): void
    {
        // Invalidate cache after restoration
        $this->invalidateCache($criteria);
    }

    /**
     * Invalidate all cache related to this criteria
     */
    protected function invalidateCache(Criteria $criteria): void
    {
        if (! config('eligify.evaluation.cache_enabled', true)) {
            return;
        }

        try {
            // Invalidate evaluation cache
            $this->cache->invalidateCriteriaEvaluations($criteria);

            // Invalidate compilation cache
            $this->cache->invalidateCompilation($criteria);

            logger()->debug('Cache invalidated for criteria', [
                'criteria_id' => $criteria->id,
                'criteria_name' => $criteria->name,
            ]);
        } catch (\Throwable $e) {
            logger()->error('Failed to invalidate cache for criteria', [
                'criteria_id' => $criteria->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
