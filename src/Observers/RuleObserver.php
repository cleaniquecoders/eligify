<?php

namespace CleaniqueCoders\Eligify\Observers;

use CleaniqueCoders\Eligify\Audit\AuditLogger;
use CleaniqueCoders\Eligify\Models\Rule;
use CleaniqueCoders\Eligify\Support\Cache;

class RuleObserver
{
    protected AuditLogger $auditLogger;

    protected Cache $cache;

    // Store original attributes temporarily
    protected static array $originalAttributes = [];

    protected static array $attributesBeforeDeletion = [];

    public function __construct(AuditLogger $auditLogger)
    {
        $this->auditLogger = $auditLogger;
        $this->cache = new Cache;
    }

    /**
     * Handle the Rule "created" event.
     */
    public function created(Rule $rule): void
    {
        $this->auditLogger->logRuleCreated($rule);

        // Invalidate cache after creation
        $this->invalidateCache($rule);
    }

    /**
     * Handle the Rule "updating" event.
     */
    public function updating(Rule $rule): void
    {
        // Store original attributes before update using model key
        static::$originalAttributes[$rule->getKey()] = $rule->getOriginal();
    }

    /**
     * Handle the Rule "updated" event.
     */
    public function updated(Rule $rule): void
    {
        $originalAttributes = static::$originalAttributes[$rule->getKey()] ?? [];

        // Clean up stored attributes
        unset(static::$originalAttributes[$rule->getKey()]);

        $this->auditLogger->logRuleUpdated($rule, $originalAttributes);

        // Invalidate cache after update
        $this->invalidateCache($rule);
    }

    /**
     * Handle the Rule "deleting" event.
     */
    public function deleting(Rule $rule): void
    {
        // Store attributes before deletion
        static::$attributesBeforeDeletion[$rule->getKey()] = $rule->getAttributes();
    }

    /**
     * Handle the Rule "deleted" event.
     */
    public function deleted(Rule $rule): void
    {
        // Get stored attributes for audit logging
        $attributes = static::$attributesBeforeDeletion[$rule->getKey()] ?? [];

        // Clean up stored attributes
        unset(static::$attributesBeforeDeletion[$rule->getKey()]);

        // Create a temporary rule instance for audit logging
        $tempRule = new Rule;
        foreach ($attributes as $key => $value) {
            $tempRule->setAttribute($key, $value);
        }

        $this->auditLogger->logRuleDeleted($tempRule);

        // Invalidate cache after deletion
        $this->invalidateCache($rule);
    }

    /**
     * Handle the Rule "restored" event.
     */
    public function restored(Rule $rule): void
    {
        // Invalidate cache after restoration
        $this->invalidateCache($rule);
    }

    /**
     * Invalidate cache for the criteria this rule belongs to
     */
    protected function invalidateCache(Rule $rule): void
    {
        if (! config('eligify.evaluation.cache_enabled', true)) {
            return;
        }

        try {
            $criteria = $rule->criteria;

            if (! $criteria) {
                return;
            }

            // Invalidate evaluation cache for the criteria
            $this->cache->invalidateCriteriaEvaluations($criteria);

            // Invalidate compilation cache for the criteria
            $this->cache->invalidateCompilation($criteria);

            logger()->debug('Cache invalidated for rule change', [
                'rule_id' => $rule->id,
                'criteria_id' => $criteria->id,
                'criteria_name' => $criteria->name,
            ]);
        } catch (\Throwable $e) {
            logger()->error('Failed to invalidate cache for rule', [
                'rule_id' => $rule->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
