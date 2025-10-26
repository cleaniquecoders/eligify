<?php

namespace CleaniqueCoders\Eligify\Support;

use CleaniqueCoders\Eligify\Enums\FieldType;
use CleaniqueCoders\Eligify\Enums\RuleOperator;
use CleaniqueCoders\Eligify\Enums\RulePriority;
use CleaniqueCoders\Eligify\Enums\ScoringMethod;

class Config
{
    /**
     * Get a configuration value with dot notation support
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return config("eligify.{$key}", $default);
    }

    /**
     * Get all supported operators
     */
    public static function getOperators(): array
    {
        return static::get('operators', []);
    }

    /**
     * Get operator configuration
     */
    public static function getOperator(string $operator): ?array
    {
        return static::get("operators.{$operator}");
    }

    /**
     * Check if an operator is supported
     */
    public static function isValidOperator(string $operator): bool
    {
        return RuleOperator::tryFrom($operator) !== null;
    }

    /**
     * Get operators available for a specific field type
     */
    public static function getOperatorsForType(string $type): array
    {
        $fieldType = FieldType::tryFrom($type);
        if (! $fieldType) {
            return [];
        }

        return array_map(
            fn ($operator) => $operator->value,
            RuleOperator::forFieldType($fieldType)
        );
    }

    /**
     * Get field type configuration
     */
    public static function getFieldType(string $type): ?array
    {
        return static::get("field_types.{$type}");
    }

    /**
     * Check if a field type is supported
     */
    public static function isValidFieldType(string $type): bool
    {
        return FieldType::tryFrom($type) !== null;
    }

    /**
     * Validate operator for field type
     */
    public static function isValidOperatorForType(string $operator, string $type): bool
    {
        $allowedOperators = static::getOperatorsForType($type);

        return in_array($operator, $allowedOperators);
    }

    /**
     * Get default scoring configuration
     */
    public static function getScoring(): array
    {
        return static::get('scoring', []);
    }

    /**
     * Get pass threshold
     */
    public static function getPassThreshold(): float
    {
        return static::get('scoring.pass_threshold', 65);
    }

    /**
     * Get default rule weight by priority
     */
    public static function getRuleWeight(string $priority): int
    {
        $rulePriority = RulePriority::tryFrom($priority);

        return $rulePriority ? $rulePriority->getWeight() : 5;
    }

    /**
     * Get all available scoring methods
     */
    public static function getScoringMethods(): array
    {
        return ScoringMethod::toArray();
    }

    /**
     * Get default scoring method
     */
    public static function getDefaultScoringMethod(): string
    {
        return static::get('scoring.method', ScoringMethod::WEIGHTED->value);
    }

    /**
     * Check if scoring method is valid
     */
    public static function isValidScoringMethod(string $method): bool
    {
        return ScoringMethod::tryFrom($method) !== null;
    }

    /**
     * Get evaluation configuration
     */
    public static function getEvaluation(): array
    {
        return static::get('evaluation', []);
    }

    /**
     * Check if caching is enabled
     */
    public static function isCacheEnabled(): bool
    {
        return static::get('evaluation.cache_enabled', true);
    }

    /**
     * Get cache TTL in minutes
     */
    public static function getCacheTtl(): int
    {
        return static::get('evaluation.cache_ttl', 60);
    }

    /**
     * Check if fail-fast is enabled
     */
    public static function isFailFastEnabled(): bool
    {
        return static::get('evaluation.fail_fast', false);
    }

    /**
     * Get audit configuration
     */
    public static function getAudit(): array
    {
        return static::get('audit', []);
    }

    /**
     * Check if audit logging is enabled
     */
    public static function isAuditEnabled(): bool
    {
        return static::get('audit.enabled', true);
    }

    /**
     * Get events that should be audited
     */
    public static function getAuditEvents(): array
    {
        return static::get('audit.events', []);
    }

    /**
     * Check if an event should be audited
     */
    public static function shouldAuditEvent(string $event): bool
    {
        return in_array($event, static::getAuditEvents());
    }

    /**
     * Get preset configuration
     */
    public static function getPreset(string $name): ?array
    {
        return static::get("presets.{$name}");
    }

    /**
     * Get all available presets
     */
    public static function getPresets(): array
    {
        return static::get('presets', []);
    }

    /**
     * Get performance configuration
     */
    public static function getPerformance(): array
    {
        return static::get('performance', []);
    }

    /**
     * Get batch size for bulk operations
     */
    public static function getBatchSize(): int
    {
        return static::get('performance.batch_size', 100);
    }

    /**
     * Check if query optimization is enabled
     */
    public static function isQueryOptimizationEnabled(): bool
    {
        return static::get('performance.optimize_queries', true);
    }

    /**
     * Get decision labels
     */
    public static function getDecisions(): array
    {
        return static::get('evaluation.decisions', [
            'pass' => ['Approved'],
            'fail' => ['Rejected'],
        ]);
    }

    /**
     * Get random decision label for result
     */
    public static function getRandomDecision(bool $passed): string
    {
        $decisions = static::getDecisions();
        $type = $passed ? 'pass' : 'fail';
        $labels = $decisions[$type] ?? ['Unknown'];

        return $labels[array_rand($labels)];
    }

    /**
     * Cast value to appropriate type based on field type
     */
    public static function castValue(mixed $value, string $fieldType): mixed
    {
        $typeConfig = static::getFieldType($fieldType);

        if (! $typeConfig || ! isset($typeConfig['cast'])) {
            return $value;
        }

        return match ($typeConfig['cast']) {
            'int' => (int) $value,
            'float' => (float) $value,
            'string' => (string) $value,
            'bool' => (bool) $value,
            'array' => is_array($value) ? $value : [$value],
            'datetime' => is_string($value) ? new \DateTime($value) : $value,
            default => $value,
        };
    }

    /**
     * Validate rule configuration
     */
    public static function validateRule(array $rule): array
    {
        $errors = [];

        // Check required fields
        $required = ['field', 'operator', 'value'];
        foreach ($required as $field) {
            if (! isset($rule[$field])) {
                $errors[] = "Missing required field: {$field}";
            }
        }

        // Validate operator
        if (isset($rule['operator']) && ! static::isValidOperator($rule['operator'])) {
            $errors[] = "Invalid operator: {$rule['operator']}";
        }

        // Validate weight
        if (isset($rule['weight']) && (! is_numeric($rule['weight']) || $rule['weight'] < 0)) {
            $errors[] = 'Weight must be a positive number';
        }

        return $errors;
    }
}
