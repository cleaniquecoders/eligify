<?php

namespace CleaniqueCoders\Eligify\Engine;

use CleaniqueCoders\Eligify\Data\Snapshot;
use CleaniqueCoders\Eligify\Enums\RuleOperator;
use CleaniqueCoders\Eligify\Enums\ScoringMethod;
use CleaniqueCoders\Eligify\Models\Criteria;
use CleaniqueCoders\Eligify\Models\Rule;
use Illuminate\Support\Collection;

class RuleEngine
{
    protected array $config;

    protected array $executionLog = [];

    public function __construct()
    {
        $this->config = config('eligify');
    }

    /**
     * Evaluate all rules for a given criteria against provided data
     *
     * @param  Criteria  $criteria  The criteria containing rules to evaluate
     * @param  array|Snapshot  $data  The data to evaluate against (accepts both formats)
     * @return array Evaluation result with passed status, score, and failed rules
     */
    public function evaluate(Criteria $criteria, array|Snapshot $data): array
    {
        $this->executionLog = [];

        // Convert Snapshot to array if needed
        $dataArray = $data instanceof Snapshot ? $data->toArray() : $data;

        $rules = $criteria->rules()->where('is_active', true)->orderBy('order')->get();

        if ($rules->isEmpty()) {
            return $this->buildResult(true, 100, [], 'No rules to evaluate');
        }

        $results = $this->evaluateRules($rules, $dataArray);
        $score = $this->calculateScore($results, $criteria);
        $passThreshold = $this->getPassThreshold($criteria);

        $passed = $score >= $passThreshold;
        $failedRules = $results->where('passed', false);

        $decision = $this->getDecision($passed);

        return $this->buildResult($passed, $score, $failedRules->toArray(), $decision);
    }

    /**
     * Evaluate a collection of rules against data
     */
    protected function evaluateRules(Collection $rules, array $data): Collection
    {
        return $rules->map(function (Rule $rule) use ($data) {
            $result = $this->evaluateRule($rule, $data);

            $this->executionLog[] = [
                'rule_id' => $rule->id,
                'field' => $rule->getAttribute('field'),
                'operator' => $rule->getAttribute('operator'),
                'expected' => $rule->getAttribute('value'),
                'actual' => data_get($data, $rule->getAttribute('field')),
                'passed' => $result['passed'],
                'score' => $result['score'],
                'weight' => $rule->getAttribute('weight'),
                'execution_time_ms' => $result['execution_time_ms'] ?? 0,
            ];

            return array_merge($result, [
                'rule' => $rule,
                'weight' => $rule->getAttribute('weight'),
            ]);
        });
    }

    /**
     * Evaluate a single rule against data (public method for advanced engine)
     */
    public function evaluateRule(Rule $rule, array $data): array
    {
        $startTime = microtime(true);

        try {
            $fieldValue = data_get($data, $rule->getAttribute('field'));
            $expectedValue = $rule->getAttribute('value');
            $operator = RuleOperator::from($rule->getAttribute('operator'));

            $passed = $this->executeOperator($operator, $fieldValue, $expectedValue);
            $score = $passed ? $rule->getAttribute('weight') : 0;

            return [
                'passed' => $passed,
                'score' => $score,
                'field_value' => $fieldValue,
                'expected_value' => $expectedValue,
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ];

        } catch (\Exception $e) {
            return [
                'passed' => false,
                'score' => 0,
                'field_value' => null,
                'expected_value' => $rule->getAttribute('value'),
                'error' => $e->getMessage(),
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ];
        }
    }

    /**
     * Execute the appropriate comparison based on operator
     */
    protected function executeOperator(RuleOperator $operator, mixed $fieldValue, mixed $expectedValue): bool
    {
        return match ($operator) {
            RuleOperator::EQUAL => $this->isEqual($fieldValue, $expectedValue),
            RuleOperator::NOT_EQUAL => $this->isNotEqual($fieldValue, $expectedValue),
            RuleOperator::GREATER_THAN => $this->isGreaterThan($fieldValue, $expectedValue),
            RuleOperator::GREATER_THAN_OR_EQUAL => $this->isGreaterThanOrEqual($fieldValue, $expectedValue),
            RuleOperator::LESS_THAN => $this->isLessThan($fieldValue, $expectedValue),
            RuleOperator::LESS_THAN_OR_EQUAL => $this->isLessThanOrEqual($fieldValue, $expectedValue),
            RuleOperator::IN => $this->isIn($fieldValue, $expectedValue),
            RuleOperator::NOT_IN => $this->isNotIn($fieldValue, $expectedValue),
            RuleOperator::BETWEEN => $this->isBetween($fieldValue, $expectedValue),
            RuleOperator::NOT_BETWEEN => $this->isNotBetween($fieldValue, $expectedValue),
            RuleOperator::CONTAINS => $this->contains($fieldValue, $expectedValue),
            RuleOperator::STARTS_WITH => $this->startsWith($fieldValue, $expectedValue),
            RuleOperator::ENDS_WITH => $this->endsWith($fieldValue, $expectedValue),
            RuleOperator::EXISTS => $this->exists($fieldValue),
            RuleOperator::NOT_EXISTS => $this->notExists($fieldValue),
            RuleOperator::REGEX => $this->matchesRegex($fieldValue, $expectedValue),
        };
    }

    /**
     * Operator implementations
     */
    protected function isEqual(mixed $fieldValue, mixed $expectedValue): bool
    {
        return $fieldValue == $expectedValue;
    }

    protected function isNotEqual(mixed $fieldValue, mixed $expectedValue): bool
    {
        return $fieldValue != $expectedValue;
    }

    protected function isGreaterThan(mixed $fieldValue, mixed $expectedValue): bool
    {
        return is_numeric($fieldValue) && is_numeric($expectedValue) && $fieldValue > $expectedValue;
    }

    protected function isGreaterThanOrEqual(mixed $fieldValue, mixed $expectedValue): bool
    {
        return is_numeric($fieldValue) && is_numeric($expectedValue) && $fieldValue >= $expectedValue;
    }

    protected function isLessThan(mixed $fieldValue, mixed $expectedValue): bool
    {
        return is_numeric($fieldValue) && is_numeric($expectedValue) && $fieldValue < $expectedValue;
    }

    protected function isLessThanOrEqual(mixed $fieldValue, mixed $expectedValue): bool
    {
        return is_numeric($fieldValue) && is_numeric($expectedValue) && $fieldValue <= $expectedValue;
    }

    protected function isIn(mixed $fieldValue, mixed $expectedValue): bool
    {
        return is_array($expectedValue) && in_array($fieldValue, $expectedValue, true);
    }

    protected function isNotIn(mixed $fieldValue, mixed $expectedValue): bool
    {
        return is_array($expectedValue) && ! in_array($fieldValue, $expectedValue, true);
    }

    protected function isBetween(mixed $fieldValue, mixed $expectedValue): bool
    {
        if (! is_array($expectedValue) || count($expectedValue) !== 2) {
            return false;
        }

        [$min, $max] = $expectedValue;

        return is_numeric($fieldValue) && $fieldValue >= $min && $fieldValue <= $max;
    }

    protected function isNotBetween(mixed $fieldValue, mixed $expectedValue): bool
    {
        return ! $this->isBetween($fieldValue, $expectedValue);
    }

    protected function contains(mixed $fieldValue, mixed $expectedValue): bool
    {
        if (is_string($fieldValue) && is_string($expectedValue)) {
            return str_contains($fieldValue, $expectedValue);
        }

        if (is_array($fieldValue)) {
            return in_array($expectedValue, $fieldValue, true);
        }

        return false;
    }

    protected function startsWith(mixed $fieldValue, mixed $expectedValue): bool
    {
        return is_string($fieldValue) && is_string($expectedValue) && str_starts_with($fieldValue, $expectedValue);
    }

    protected function endsWith(mixed $fieldValue, mixed $expectedValue): bool
    {
        return is_string($fieldValue) && is_string($expectedValue) && str_ends_with($fieldValue, $expectedValue);
    }

    protected function exists(mixed $fieldValue): bool
    {
        return $fieldValue !== null && $fieldValue !== '';
    }

    protected function notExists(mixed $fieldValue): bool
    {
        return $fieldValue === null || $fieldValue === '';
    }

    protected function matchesRegex(mixed $fieldValue, mixed $expectedValue): bool
    {
        return is_string($fieldValue) && is_string($expectedValue) && preg_match($expectedValue, $fieldValue) === 1;
    }

    /**
     * Calculate the overall score based on rule results
     */
    protected function calculateScore(Collection $results, Criteria $criteria): float
    {
        $scoringMethod = $this->getScoringMethod($criteria);

        return match ($scoringMethod) {
            ScoringMethod::WEIGHTED => $this->calculateWeightedScore($results),
            default => $this->calculateSimpleScore($results),
        };
    }

    /**
     * Calculate weighted score based on rule weights
     */
    protected function calculateWeightedScore(Collection $results): float
    {
        $totalWeight = $results->sum('weight');

        if ($totalWeight === 0) {
            return 0;
        }

        $achievedScore = $results->where('passed', true)->sum('score');

        return min(100, ($achievedScore / $totalWeight) * 100);
    }

    /**
     * Calculate simple percentage score
     */
    protected function calculateSimpleScore(Collection $results): float
    {
        $totalRules = $results->count();

        if ($totalRules === 0) {
            return 0;
        }

        $passedRules = $results->where('passed', true)->count();

        return ($passedRules / $totalRules) * 100;
    }

    /**
     * Get the pass threshold for criteria
     */
    protected function getPassThreshold(Criteria $criteria): float
    {
        return $criteria->meta['pass_threshold'] ?? $this->config['scoring']['pass_threshold'];
    }

    /**
     * Get the scoring method for criteria
     */
    protected function getScoringMethod(Criteria $criteria): ScoringMethod
    {
        $method = $criteria->meta['scoring_method'] ?? $this->config['scoring']['method'];

        return ScoringMethod::tryFrom($method) ?? ScoringMethod::WEIGHTED;
    }

    /**
     * Get decision label based on pass/fail status
     */
    protected function getDecision(bool $passed): string
    {
        $decisions = $this->config['evaluation']['decisions'];
        $options = $passed ? $decisions['pass'] : $decisions['fail'];

        return $options[array_rand($options)];
    }

    /**
     * Build the final evaluation result
     */
    protected function buildResult(bool $passed, float $score, array $failedRules, string $decision): array
    {
        return [
            'passed' => $passed,
            'score' => round($score, 2),
            'failed_rules' => $failedRules,
            'decision' => $decision,
            'execution_log' => $this->executionLog,
            'evaluated_at' => now(),
        ];
    }

    /**
     * Get the execution log
     */
    public function getExecutionLog(): array
    {
        return $this->executionLog;
    }

    /**
     * Clear the execution log
     */
    public function clearExecutionLog(): void
    {
        $this->executionLog = [];
    }

    /**
     * Compare values using a specific operator (public method for advanced engine)
     */
    public function compareValues(mixed $fieldValue, string $operator, mixed $expectedValue): bool
    {
        $ruleOperator = RuleOperator::from($operator);

        return $this->executeOperator($ruleOperator, $fieldValue, $expectedValue);
    }
}
