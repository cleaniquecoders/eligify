<?php

namespace CleaniqueCoders\Eligify\Observers;

use CleaniqueCoders\Eligify\Audit\AuditLogger;
use CleaniqueCoders\Eligify\Models\Rule;

class RuleObserver
{
    protected AuditLogger $auditLogger;

    // Store original attributes temporarily
    protected static array $originalAttributes = [];

    protected static array $attributesBeforeDeletion = [];

    public function __construct(AuditLogger $auditLogger)
    {
        $this->auditLogger = $auditLogger;
    }

    /**
     * Handle the Rule "created" event.
     */
    public function created(Rule $rule): void
    {
        $this->auditLogger->logRuleCreated($rule);
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
    }
}
