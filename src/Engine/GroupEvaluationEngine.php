<?php

declare(strict_types=1);

namespace CleaniqueCoders\Eligify\Engine;

use CleaniqueCoders\Eligify\Enums\GroupCombination;
use CleaniqueCoders\Eligify\Models\Criteria;
use CleaniqueCoders\Eligify\Models\RuleGroup;
use Illuminate\Support\Collection;

/**
 * Handles evaluation of rule groups with various combination logics
 */
class GroupEvaluationEngine
{
    protected RuleEngine $ruleEngine;

    protected array $groupResults = [];

    protected array $executionLog = [];

    public function __construct(RuleEngine $ruleEngine)
    {
        $this->ruleEngine = $ruleEngine;
    }

    /**
     * Evaluate all groups in a criteria
     *
     * @return array Group evaluation results with pass/fail status for each group
     */
    public function evaluateGroups(Criteria $criteria, array $data): array
    {
        $this->groupResults = [];
        $this->executionLog = [];

        $groups = $criteria->groups()->orderBy('order')->get();

        if ($groups->isEmpty()) {
            return [
                'groups' => [],
                'group_results' => [],
                'group_combination_logic' => null,
                'combination_passed' => true,
            ];
        }

        // Evaluate each group
        foreach ($groups as $group) {
            $this->groupResults[$group->id] = $this->evaluateGroup($group, $data);
        }

        // Apply group combination logic
        $combinationPassed = $this->evaluateGroupCombination($criteria, $this->groupResults);

        return [
            'groups' => $groups->toArray(),
            'group_results' => $this->groupResults,
            'group_combination_logic' => $criteria->meta['group_combination_logic'] ?? null,
            'combination_passed' => $combinationPassed,
            'execution_log' => $this->executionLog,
        ];
    }

    /**
     * Evaluate a single rule group
     */
    protected function evaluateGroup(RuleGroup $group, array $data): array
    {
        $startTime = microtime(true);

        try {
            $rules = $group->rules()->where('is_active', true)->orderBy('order')->get();
            $logicType = $group->getLogicType();

            if ($rules->isEmpty()) {
                return [
                    'group_id' => $group->id,
                    'name' => $group->name,
                    'logic_type' => $logicType->value,
                    'passed' => true,
                    'rule_results' => [],
                    'score' => 100,
                    'details' => 'No active rules in group',
                    'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
                ];
            }

            // Evaluate all rules in the group
            $ruleResults = $rules->map(function ($rule) use ($data) {
                $result = $this->ruleEngine->evaluateRule($rule, $data);

                return array_merge($result, [
                    'rule_id' => $rule->id,
                    'rule_name' => $rule->name ?? $rule->getAttribute('field'),
                ]);
            })->toArray();

            // Apply group logic to determine pass/fail
            $passed = $this->applyGroupLogic($logicType, collect($ruleResults));
            $score = $this->calculateGroupScore($group, collect($ruleResults));

            $this->executionLog[] = [
                'group_id' => $group->id,
                'name' => $group->name,
                'logic_type' => $logicType->value,
                'passed' => $passed,
                'score' => $score,
                'rule_count' => $rules->count(),
                'passed_rules' => collect($ruleResults)->where('passed', true)->count(),
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ];

            return [
                'group_id' => $group->id,
                'name' => $group->name,
                'description' => $group->description,
                'logic_type' => $logicType->value,
                'passed' => $passed,
                'score' => $score,
                'weight' => $group->weight,
                'rule_results' => $ruleResults,
                'rule_count' => $rules->count(),
                'passed_rules' => collect($ruleResults)->where('passed', true)->count(),
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ];

        } catch (\Exception $e) {
            return [
                'group_id' => $group->id,
                'name' => $group->name,
                'passed' => false,
                'error' => $e->getMessage(),
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ];
        }
    }

    /**
     * Apply logic to group rules to determine pass/fail
     */
    protected function applyGroupLogic(GroupCombination $logicType, Collection $ruleResults): bool
    {
        return match ($logicType) {
            GroupCombination::ALL => $this->allRulesPassed($ruleResults),
            GroupCombination::ANY => $this->anyRulePassed($ruleResults),
            GroupCombination::MIN => false, // MIN requires min_required value (handled separately)
            GroupCombination::MAJORITY => $this->majorityRulesPassed($ruleResults),
            GroupCombination::BOOLEAN => false, // BOOLEAN uses boolean_expression (handled separately)
        };
    }

    /**
     * Apply logic with MIN required value
     */
    protected function applyGroupLogicWithMin(GroupCombination $logicType, Collection $ruleResults, ?int $minRequired = null): bool
    {
        return match ($logicType) {
            GroupCombination::ALL => $this->allRulesPassed($ruleResults),
            GroupCombination::ANY => $this->anyRulePassed($ruleResults),
            GroupCombination::MIN => $this->minRulesPassed($ruleResults, $minRequired ?? 1),
            GroupCombination::MAJORITY => $this->majorityRulesPassed($ruleResults),
            GroupCombination::BOOLEAN => false, // BOOLEAN uses boolean_expression (handled separately)
        };
    }

    /**
     * All rules must pass
     */
    protected function allRulesPassed(Collection $ruleResults): bool
    {
        return $ruleResults->every(fn ($result) => $result['passed'] === true);
    }

    /**
     * At least one rule must pass
     */
    protected function anyRulePassed(Collection $ruleResults): bool
    {
        return $ruleResults->contains(fn ($result) => $result['passed'] === true);
    }

    /**
     * At least N rules must pass
     */
    protected function minRulesPassed(Collection $ruleResults, int $minRequired): bool
    {
        $passedCount = $ruleResults->filter(fn ($result) => $result['passed'] === true)->count();

        return $passedCount >= $minRequired;
    }

    /**
     * More than half must pass
     */
    protected function majorityRulesPassed(Collection $ruleResults): bool
    {
        $total = $ruleResults->count();
        $passed = $ruleResults->filter(fn ($result) => $result['passed'] === true)->count();

        return $passed > ($total / 2);
    }

    /**
     * Evaluate boolean expression (e.g., "(a AND b) OR c")
     */
    public function evaluateBooleanExpression(string $expression, Collection $ruleResults): bool
    {
        // Create a map of rule indices to pass/fail values
        $ruleMap = [];
        foreach ($ruleResults as $index => $result) {
            $letter = chr(97 + $index); // a, b, c, etc.
            $ruleMap[$letter] = $result['passed'] ? 'true' : 'false';
        }

        // Replace letters with true/false in expression
        $evaluableExpression = $expression;
        foreach ($ruleMap as $letter => $value) {
            $evaluableExpression = str_replace($letter, $value, $evaluableExpression);
        }

        // Replace AND, OR, NOT with PHP operators
        $evaluableExpression = str_replace(['AND', 'OR', 'NOT'], ['&&', '||', '!'], $evaluableExpression);

        try {
            // Use eval carefully with validated input
            $result = false;
            eval('$result = '.$evaluableExpression.';');

            return (bool) $result;
        } catch (\Exception $e) {
            // Log expression evaluation error
            return false;
        }
    }

    /**
     * Calculate group score
     */
    protected function calculateGroupScore(RuleGroup $group, Collection $ruleResults): float
    {
        if ($ruleResults->isEmpty()) {
            return 0;
        }

        $passed = $ruleResults->filter(fn ($result) => $result['passed'] === true)->count();
        $total = $ruleResults->count();

        return ($passed / $total) * 100;
    }

    /**
     * Evaluate group combination logic from criteria metadata
     */
    protected function evaluateGroupCombination(Criteria $criteria, array $groupResults): bool
    {
        $combinationLogic = $criteria->meta['group_combination_logic'] ?? 'ALL';

        if ($combinationLogic === 'ALL') {
            return $this->allGroupsPassed($groupResults);
        }

        if ($combinationLogic === 'ANY') {
            return $this->anyGroupPassed($groupResults);
        }

        if ($combinationLogic === 'BOOLEAN') {
            $booleanExpr = $criteria->meta['group_boolean_expression'] ?? null;
            if ($booleanExpr) {
                return $this->evaluateGroupBooleanExpression($booleanExpr, $groupResults);
            }
        }

        return true;
    }

    /**
     * All groups must pass
     */
    protected function allGroupsPassed(array $groupResults): bool
    {
        foreach ($groupResults as $result) {
            if (isset($result['passed']) && $result['passed'] === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * At least one group must pass
     */
    protected function anyGroupPassed(array $groupResults): bool
    {
        foreach ($groupResults as $result) {
            if (isset($result['passed']) && $result['passed'] === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * Evaluate boolean expression for groups
     */
    protected function evaluateGroupBooleanExpression(string $expression, array $groupResults): bool
    {
        // Map group IDs to letters (a, b, c, etc.)
        $groupMap = [];
        $index = 0;
        foreach ($groupResults as $result) {
            if (isset($result['group_id'])) {
                $letter = chr(97 + $index);
                $groupMap[$letter] = ($result['passed'] ?? false) ? 'true' : 'false';
                $index++;
            }
        }

        // Replace letters with true/false in expression
        $evaluableExpression = $expression;
        foreach ($groupMap as $letter => $value) {
            $evaluableExpression = str_replace($letter, $value, $evaluableExpression);
        }

        // Replace AND, OR, NOT with PHP operators
        $evaluableExpression = str_replace(['AND', 'OR', 'NOT'], ['&&', '||', '!'], $evaluableExpression);

        try {
            $result = false;
            eval('$result = '.$evaluableExpression.';');

            return (bool) $result;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get group results
     */
    public function getGroupResults(): array
    {
        return $this->groupResults;
    }

    /**
     * Get execution log
     */
    public function getExecutionLog(): array
    {
        return $this->executionLog;
    }

    /**
     * Check if a specific group passed
     */
    public function groupPassed(string|int $groupId): bool
    {
        return $this->groupResults[$groupId]['passed'] ?? false;
    }

    /**
     * Get score for a specific group
     */
    public function getGroupScore(string|int $groupId): float
    {
        return $this->groupResults[$groupId]['score'] ?? 0;
    }
}
