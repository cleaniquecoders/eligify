<?php

namespace CleaniqueCoders\Eligify\Builder;

use CleaniqueCoders\Eligify\Enums\RuleOperator;
use CleaniqueCoders\Eligify\Enums\RulePriority;
use CleaniqueCoders\Eligify\Models\Criteria;
use CleaniqueCoders\Eligify\Models\Rule;
use CleaniqueCoders\Eligify\Support\Config;
use CleaniqueCoders\Eligify\Workflow\WorkflowManager;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class CriteriaBuilder
{
    protected Criteria $criteria;

    protected Collection $pendingRules;

    protected $onPassCallback = null;

    protected $onFailCallback = null;

    protected WorkflowManager $workflowManager;

    protected array $config;

    public function __construct(string $criteriaName)
    {
        $this->config = config('eligify');
        $this->pendingRules = collect();
        $this->workflowManager = new WorkflowManager;

        // Find or create criteria
        $this->criteria = Criteria::firstOrCreate(
            ['slug' => str($criteriaName)->slug()],
            [
                'name' => $criteriaName,
                'description' => "Auto-generated criteria for {$criteriaName}",
                'is_active' => true,
            ]
        );
    }

    /**
     * Add a rule to this criteria
     */
    public function addRule(
        string $field,
        string $operator,
        mixed $value,
        ?int $weight = null,
        RulePriority $priority = RulePriority::MEDIUM
    ): self {
        $this->validateRule($field, $operator, $value);

        $this->pendingRules->push([
            'field' => $field,
            'operator' => $operator,
            'value' => $this->normalizeValue($value),
            'weight' => $weight ?? $this->config['rule_weights'][$priority->value],
            'order' => $this->pendingRules->count() + 1,
            'is_active' => true,
        ]);

        return $this;
    }

    /**
     * Add multiple rules at once
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
     * Set callback for when evaluation passes
     */
    public function onPass(callable $callback): self
    {
        $this->onPassCallback = $callback;

        return $this;
    }

    /**
     * Set callback for when evaluation fails
     */
    public function onFail(callable $callback): self
    {
        $this->onFailCallback = $callback;

        return $this;
    }

    /**
     * Set callback for before evaluation starts
     */
    public function beforeEvaluation(callable $callback): self
    {
        $this->workflowManager->addCallback('before_evaluation', $callback);

        return $this;
    }

    /**
     * Set callback for after evaluation completes
     */
    public function afterEvaluation(callable $callback): self
    {
        $this->workflowManager->addCallback('after_evaluation', $callback);

        return $this;
    }

    /**
     * Set callback for excellent scores (90+)
     */
    public function onExcellent(callable $callback): self
    {
        $this->workflowManager->addCallback('on_excellent', $callback);

        return $this;
    }

    /**
     * Set callback for good scores (80-89)
     */
    public function onGood(callable $callback): self
    {
        $this->workflowManager->addCallback('on_good', $callback);

        return $this;
    }

    /**
     * Set conditional callback with specific conditions
     */
    public function onCondition(array $conditions, callable $callback): self
    {
        $this->workflowManager->addConditionalCallback($callback, $conditions);

        return $this;
    }

    /**
     * Set callback for specific score range
     */
    public function onScoreRange(int $minScore, int $maxScore, callable $callback): self
    {
        $this->workflowManager->addScoreRangeCallback($minScore, $maxScore, $callback);

        return $this;
    }

    /**
     * Set async callback (queued)
     */
    public function onPassAsync(callable $callback): self
    {
        $this->workflowManager->addCallback('on_pass_async', function ($data, $result, $context) use ($callback) {
            $this->workflowManager->executeAsyncCallback($callback, $context);
        });

        return $this;
    }

    /**
     * Set async callback for failures (queued)
     */
    public function onFailAsync(callable $callback): self
    {
        $this->workflowManager->addCallback('on_fail_async', function ($data, $result, $context) use ($callback) {
            $this->workflowManager->executeAsyncCallback($callback, $context);
        });

        return $this;
    }

    /**
     * Set the pass threshold for this criteria
     */
    public function passThreshold(int $threshold): self
    {
        $this->criteria->update([
            'meta' => array_merge($this->criteria->meta ?? [], [
                'pass_threshold' => $threshold,
            ]),
        ]);

        return $this;
    }

    /**
     * Set description for this criteria
     */
    public function description(string $description): self
    {
        $this->criteria->update(['description' => $description]);

        return $this;
    }

    /**
     * Activate or deactivate this criteria
     */
    public function active(bool $active = true): self
    {
        $this->criteria->update(['is_active' => $active]);

        return $this;
    }

    /**
     * Get the criteria instance
     */
    public function getCriteria(): Criteria
    {
        return $this->criteria;
    }

    /**
     * Get pending rules
     */
    public function getPendingRules(): Collection
    {
        return $this->pendingRules;
    }

    /**
     * Get the pass callback
     */
    public function getOnPassCallback()
    {
        return $this->onPassCallback;
    }

    /**
     * Get the fail callback
     */
    public function getOnFailCallback()
    {
        return $this->onFailCallback;
    }

    /**
     * Get the workflow manager
     */
    public function getWorkflowManager(): WorkflowManager
    {
        return $this->workflowManager;
    }

    /**
     * Save all pending rules to the database
     */
    public function save(): self
    {
        $this->pendingRules->each(function (array $ruleData) {
            Rule::create(array_merge($ruleData, [
                'criteria_id' => $this->criteria->id,
            ]));
        });

        $this->pendingRules = collect();

        return $this;
    }

    /**
     * Validate a rule configuration
     */
    protected function validateRule(string $field, string $operator, mixed $value): void
    {
        // Validate operator exists
        if (! RuleOperator::tryFrom($operator)) {
            throw new InvalidArgumentException("Invalid operator: {$operator}");
        }

        // Check if operator is supported in config
        if (! isset($this->config['operators'][$operator])) {
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
            default => $this->validateScalarValue($value),
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

        [$min, $max] = $value;
        if (! is_numeric($min) || ! is_numeric($max)) {
            throw new InvalidArgumentException('BETWEEN and NOT_BETWEEN operators require numeric range values');
        }

        if ($min >= $max) {
            throw new InvalidArgumentException('BETWEEN range minimum must be less than maximum');
        }
    }

    /**
     * Validate regex patterns
     */
    protected function validateRegexValue(mixed $value): void
    {
        if (! is_string($value)) {
            throw new InvalidArgumentException('REGEX operator requires string pattern');
        }

        // Test if it's a valid regex
        if (@preg_match($value, '') === false) {
            throw new InvalidArgumentException('Invalid regex pattern provided');
        }
    }

    /**
     * Validate scalar values
     */
    protected function validateScalarValue(mixed $value): void
    {
        if (is_array($value) || is_object($value)) {
            throw new InvalidArgumentException('Scalar operators require scalar values');
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

        // Convert boolean strings
        if (is_string($value)) {
            if (strtolower($value) === 'true') {
                return true;
            }
            if (strtolower($value) === 'false') {
                return false;
            }
        }

        return $value;
    }
}
