<?php

declare(strict_types=1);

namespace CleaniqueCoders\Eligify\Builder;

use CleaniqueCoders\Eligify\Enums\GroupCombination;
use CleaniqueCoders\Eligify\Enums\RuleOperator;
use CleaniqueCoders\Eligify\Enums\RulePriority;
use CleaniqueCoders\Eligify\Models\RuleGroup;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * GroupBuilder
 *
 * Builder for creating and configuring rule groups within a criteria.
 * Allows adding rules to a group and configuring group-level logic.
 */
class GroupBuilder
{
    /**
     * The parent criteria builder
     */
    protected CriteriaBuilder $parentBuilder;

    /**
     * The rule group being built
     */
    protected RuleGroup $group;

    /**
     * Pending rules to add to this group
     */
    protected Collection $pendingRules;

    /**
     * Group-level callbacks
     */
    protected $onPassCallback = null;

    protected $onFailCallback = null;

    /**
     * Create a new GroupBuilder instance
     */
    public function __construct(CriteriaBuilder $parentBuilder, string $groupName)
    {
        $this->parentBuilder = $parentBuilder;
        $this->pendingRules = collect();

        // Create or get the rule group
        $this->group = $parentBuilder->getCriteria()->groups()->firstOrCreate(
            ['name' => $groupName],
            [
                'uuid' => (string) str()->uuid(),
                'description' => null,
                'logic_type' => GroupCombination::ALL->value,
                'weight' => 1.0,
                'order' => $parentBuilder->getCriteria()->groups()->count(),
                'is_active' => true,
                'meta' => [],
            ]
        );
    }

    /**
     * Add a rule to this group
     */
    public function addRule(
        string $field,
        string $operator,
        mixed $value,
        ?int $weight = null,
        RulePriority $priority = RulePriority::MEDIUM
    ): self {
        $config = config('eligify');

        // Validate rule
        $this->validateRule($field, $operator, $value, $config);

        $ruleData = [
            'field' => $field,
            'operator' => $operator,
            'value' => $this->normalizeValue($value),
            'weight' => $weight ?? $config['rule_weights'][$priority->value],
            'order' => $this->pendingRules->count(),
            'is_active' => true,
            'meta' => [],
        ];

        $this->pendingRules->push($ruleData);

        return $this;
    }

    /**
     * Add multiple rules to this group
     */
    public function addRules(array $rules): self
    {
        foreach ($rules as $rule) {
            $this->addRule(
                $rule['field'],
                $rule['operator'],
                $rule['value'],
                $rule['weight'] ?? null,
                RulePriority::tryFrom($rule['priority'] ?? RulePriority::MEDIUM->value) ?? RulePriority::MEDIUM
            );
        }

        return $this;
    }

    /**
     * Set group logic to ALL (all rules must pass)
     */
    public function requireAll(): self
    {
        $this->group->update([
            'logic_type' => GroupCombination::ALL->value,
            'min_required' => null,
            'boolean_expression' => null,
        ]);

        return $this;
    }

    /**
     * Set group logic to ANY (at least one rule must pass)
     */
    public function requireAny(): self
    {
        $this->group->update([
            'logic_type' => GroupCombination::ANY->value,
            'min_required' => null,
            'boolean_expression' => null,
        ]);

        return $this;
    }

    /**
     * Set group logic to MIN (at least N rules must pass)
     */
    public function requireMin(int $minRequired): self
    {
        if ($minRequired < 1) {
            throw new InvalidArgumentException('Min required must be at least 1');
        }

        $this->group->update([
            'logic_type' => GroupCombination::MIN->value,
            'min_required' => $minRequired,
            'boolean_expression' => null,
        ]);

        return $this;
    }

    /**
     * Set group logic to MAJORITY (more than half must pass)
     */
    public function requireMajority(): self
    {
        $this->group->update([
            'logic_type' => GroupCombination::MAJORITY->value,
            'min_required' => null,
            'boolean_expression' => null,
        ]);

        return $this;
    }

    /**
     * Set group logic to BOOLEAN (custom boolean expression)
     */
    public function requireLogic(string $expression): self
    {
        // Basic validation of boolean expression
        $this->validateBooleanExpression($expression);

        $this->group->update([
            'logic_type' => GroupCombination::BOOLEAN->value,
            'min_required' => null,
            'boolean_expression' => $expression,
        ]);

        return $this;
    }

    /**
     * Set the group's weight for scoring
     */
    public function weight(float $weight): self
    {
        if ($weight <= 0) {
            throw new InvalidArgumentException('Weight must be greater than 0');
        }

        $this->group->update(['weight' => $weight]);

        return $this;
    }

    /**
     * Set group description
     */
    public function description(string $description): self
    {
        $this->group->update(['description' => $description]);

        return $this;
    }

    /**
     * Add metadata to the group
     */
    public function meta(string $key, mixed $value): self
    {
        $meta = $this->group->meta ?? [];
        $meta[$key] = $value;
        $this->group->update(['meta' => $meta]);

        return $this;
    }

    /**
     * Set callback when group passes
     */
    public function onPass(callable $callback): self
    {
        $this->onPassCallback = $callback;

        return $this;
    }

    /**
     * Set callback when group fails
     */
    public function onFail(callable $callback): self
    {
        $this->onFailCallback = $callback;

        return $this;
    }

    /**
     * Get the underlying rule group
     */
    public function getGroup(): RuleGroup
    {
        return $this->group;
    }

    /**
     * Get pending rules
     */
    public function getPendingRules(): Collection
    {
        return $this->pendingRules;
    }

    /**
     * Get on pass callback
     */
    public function getOnPassCallback(): ?callable
    {
        return $this->onPassCallback;
    }

    /**
     * Get on fail callback
     */
    public function getOnFailCallback(): ?callable
    {
        return $this->onFailCallback;
    }

    /**
     * Close the group and return to parent builder
     */
    public function end(): CriteriaBuilder
    {
        // Save all pending rules to this group
        $this->pendingRules->each(function (array $ruleData, int $index) {
            $this->group->rules()->create(array_merge($ruleData, [
                'uuid' => (string) str()->uuid(),
                'criteria_id' => $this->group->criteria_id,
                'order' => $index,
            ]));
        });

        $this->pendingRules = collect();

        return $this->parentBuilder;
    }

    /**
     * Validate a rule configuration
     */
    protected function validateRule(string $field, string $operator, mixed $value, array $config): void
    {
        // Validate operator exists
        if (! RuleOperator::tryFrom($operator)) {
            throw new InvalidArgumentException("Invalid operator: {$operator}");
        }

        // Check if operator is supported in config
        if (! isset($config['operators'][$operator])) {
            throw new InvalidArgumentException("Operator {$operator} is not configured");
        }

        // Validate value based on operator requirements
        $this->validateValueForOperator($operator, $value);
    }

    /**
     * Validate value against operator requirements
     */
    protected function validateValueForOperator(string $operator, mixed $value): void
    {
        $operatorEnum = RuleOperator::from($operator);

        match ($operatorEnum) {
            RuleOperator::IN, RuleOperator::NOT_IN => $this->validateArrayValue($value),
            RuleOperator::BETWEEN, RuleOperator::NOT_BETWEEN => $this->validateRangeValue($value),
            RuleOperator::REGEX => $this->validateRegexValue($value),
            default => true,
        };
    }

    /**
     * Validate array values for IN/NOT_IN operators
     */
    protected function validateArrayValue(mixed $value): void
    {
        if (! is_array($value)) {
            throw new InvalidArgumentException('IN and NOT_IN operators require array values');
        }

        if (empty($value)) {
            throw new InvalidArgumentException('IN and NOT_IN operators require non-empty arrays');
        }
    }

    /**
     * Validate range values for BETWEEN/NOT_BETWEEN operators
     */
    protected function validateRangeValue(mixed $value): void
    {
        if (! is_array($value) || count($value) !== 2) {
            throw new InvalidArgumentException('BETWEEN and NOT_BETWEEN operators require arrays with exactly 2 values');
        }
    }

    /**
     * Validate regex pattern
     */
    protected function validateRegexValue(mixed $value): void
    {
        if (! is_string($value)) {
            throw new InvalidArgumentException('REGEX operator requires string value');
        }

        if (@preg_match($value, '') === false) {
            throw new InvalidArgumentException("Invalid regex pattern: {$value}");
        }
    }

    /**
     * Normalize value for storage
     */
    protected function normalizeValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return $value;
        }

        return [$value];
    }

    /**
     * Validate boolean expression syntax
     */
    protected function validateBooleanExpression(string $expression): void
    {
        // Basic validation - just check for balanced parentheses
        if (substr_count($expression, '(') !== substr_count($expression, ')')) {
            throw new InvalidArgumentException('Boolean expression has unbalanced parentheses');
        }

        // Check for valid operators
        $validOperators = ['AND', 'OR', 'NOT'];
        $tokens = preg_split('/[\s()]+/', $expression, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($tokens as $token) {
            if (! in_array(strtoupper($token), array_merge($validOperators, ['and', 'or', 'not'])) && ! preg_match('/^[a-zA-Z0-9_]+$/', $token)) {
                // Token might be a group name, which is valid
                continue;
            }
        }
    }
}
