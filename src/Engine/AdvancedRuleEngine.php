<?php

namespace CleaniqueCoders\Eligify\Engine;

use CleaniqueCoders\Eligify\Models\Criteria;
use CleaniqueCoders\Eligify\Models\Rule;

/**
 * Advanced rule engine with support for complex conditions and logic
 */
class AdvancedRuleEngine
{
    protected RuleEngine $baseEngine;

    protected array $inputData = [];

    public function __construct(RuleEngine $baseEngine)
    {
        $this->baseEngine = $baseEngine;
    }

    /**
     * Evaluate criteria with advanced rule logic
     */
    public function evaluate(Criteria $criteria, array $data): array
    {
        // Store input data for threshold decisions
        $this->inputData = $data;
        // Parse complex rule groups
        $ruleGroups = $this->parseRuleGroups($criteria);

        // Evaluate each group
        $groupResults = [];
        foreach ($ruleGroups as $group) {
            $groupResults[] = $this->evaluateRuleGroup($group, $data);
        }

        // Combine group results based on criteria logic
        $finalResult = $this->combineGroupResults($groupResults, $criteria);

        // Apply threshold-based decisions
        $finalResult = $this->applyThresholdDecisions($finalResult, $criteria);

        return $finalResult;
    }

    /**
     * Parse rules into logical groups
     */
    protected function parseRuleGroups(Criteria $criteria): array
    {
        $rules = $criteria->rules()->orderBy('order')->get();
        $groups = [];
        $currentGroup = null;
        $lastGroupLogic = null;

        foreach ($rules as $rule) {
            // Check for group metadata
            $metadata = $rule->meta ?? [];
            $ruleGroupLogic = $metadata['group_logic'] ?? null;

            // If this rule has group logic metadata, it indicates a new group or continuation
            if ($ruleGroupLogic !== null) {
                // Start new group if logic changed or no current group
                if ($currentGroup === null || $ruleGroupLogic !== $lastGroupLogic) {
                    // Save previous group if exists
                    if ($currentGroup !== null && ! empty($currentGroup['rules'])) {
                        $groups[] = $currentGroup;
                    }
                    // Start new group
                    $currentGroup = [
                        'logic' => $ruleGroupLogic,
                        'rules' => [$rule],
                    ];
                    $lastGroupLogic = $ruleGroupLogic;
                } else {
                    // Same logic, add to current group
                    $currentGroup['rules'][] = $rule;
                }
            } else {
                // No group logic - add to default AND group or current group
                if ($currentGroup === null) {
                    $currentGroup = [
                        'logic' => 'AND',
                        'rules' => [$rule],
                    ];
                    $lastGroupLogic = 'AND';
                } else {
                    $currentGroup['rules'][] = $rule;
                }
            }
        }

        // Add the last group
        if ($currentGroup !== null && ! empty($currentGroup['rules'])) {
            $groups[] = $currentGroup;
        }

        return $groups ?: [['logic' => 'AND', 'rules' => $rules->toArray()]];
    }

    /**
     * Evaluate a single rule group
     */
    protected function evaluateRuleGroup(array $group, array $data): array
    {
        $logic = $group['logic'] ?? 'AND';
        $rules = $group['rules'];
        $results = [];
        $passedCount = 0;
        $totalScore = 0;
        $totalWeight = 0;
        $failedRules = [];

        foreach ($rules as $rule) {
            $result = $this->evaluateRule($rule, $data);
            $results[] = $result;

            // Skip counting skipped rules in pass/fail logic
            if (isset($result['skipped']) && $result['skipped']) {
                // Don't count skipped rules
                continue;
            }

            if ($result['passed']) {
                $passedCount++;
            } else {
                $failedRules[] = $result;
            }

            $totalScore += $result['score'];
            $totalWeight += $rule->weight ?? 1;
        }

        // Calculate effective rule count (excluding skipped rules)
        $effectiveRuleCount = count($rules) - count(array_filter($results, fn ($r) => isset($r['skipped']) && $r['skipped']));

        // Determine group pass/fail based on logic
        $groupPassed = $this->determineGroupResult($logic, $passedCount, $effectiveRuleCount);

        return [
            'logic' => $logic,
            'passed' => $groupPassed,
            'rule_count' => $effectiveRuleCount,
            'passed_count' => $passedCount,
            'score' => $totalWeight > 0 ? ($totalScore / $totalWeight) * 100 : 0,
            'failed_rules' => $failedRules,
            'rule_results' => $results,
        ];
    }

    /**
     * Evaluate a single rule with dependency checking
     */
    protected function evaluateRule($rule, array $data): array
    {
        // Check rule dependencies first
        if (! $this->checkRuleDependencies($rule, $data)) {
            return [
                'rule_id' => $rule->uuid ?? $rule['uuid'] ?? null,
                'field' => $rule->field ?? $rule['field'],
                'passed' => false,
                'score' => 0,
                'reason' => 'Dependency not met',
                'skipped' => true,
            ];
        }

        // Use the base engine for actual evaluation
        if ($rule instanceof Rule) {
            return $this->baseEngine->evaluateRule($rule, $data);
        }

        // Handle array-based rule data
        return $this->evaluateArrayRule($rule, $data);
    }

    /**
     * Check rule dependencies
     */
    protected function checkRuleDependencies($rule, array $data): bool
    {
        $dependencies = $rule->meta['dependencies'] ?? $rule['meta']['dependencies'] ?? [];

        if (empty($dependencies)) {
            return true;
        }

        foreach ($dependencies as $dependency) {
            if (! $this->evaluateDependency($dependency, $data)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluate a single dependency
     */
    protected function evaluateDependency(array $dependency, array $data): bool
    {
        $field = $dependency['field'];
        $operator = $dependency['operator'];
        $value = $dependency['value'];

        if (! isset($data[$field])) {
            return false;
        }

        return $this->baseEngine->compareValues($data[$field], $operator, $value);
    }

    /**
     * Evaluate array-based rule
     */
    protected function evaluateArrayRule(array $rule, array $data): array
    {
        $field = $rule['field'];
        $operator = $rule['operator'];
        $expected = $rule['value'];
        $weight = $rule['weight'] ?? 1;

        $actual = $data[$field] ?? null;
        $passed = $this->baseEngine->compareValues($actual, $operator, $expected);

        return [
            'rule_id' => $rule['uuid'] ?? null,
            'field' => $field,
            'operator' => $operator,
            'expected' => $expected,
            'actual' => $actual,
            'passed' => $passed,
            'weight' => $weight,
            'score' => $passed ? $weight * 100 : 0,
        ];
    }

    /**
     * Determine group result based on logic
     */
    protected function determineGroupResult(string $logic, int $passedCount, int $totalCount): bool
    {
        return match (strtoupper($logic)) {
            'AND' => $passedCount === $totalCount,
            'OR' => $passedCount > 0,
            'NAND' => $passedCount !== $totalCount,
            'NOR' => $passedCount === 0,
            'XOR' => $passedCount === 1,
            'MAJORITY' => $passedCount > ($totalCount / 2),
            default => $passedCount === $totalCount, // Default to AND
        };
    }

    /**
     * Combine results from multiple rule groups
     */
    protected function combineGroupResults(array $groupResults, Criteria $criteria): array
    {
        if (empty($groupResults)) {
            return [
                'passed' => false,
                'score' => 0,
                'failed_rules' => [],
                'decision' => 'No rules to evaluate',
                'group_results' => [],
            ];
        }

        $criteriaLogic = $criteria->meta['group_combination_logic'] ?? 'AND';
        $passedGroups = array_filter($groupResults, fn ($group) => $group['passed']);
        $allPassed = $this->determineGroupResult($criteriaLogic, count($passedGroups), count($groupResults));

        // Calculate overall score
        $totalScore = 0;
        $totalWeight = 0;
        $allFailedRules = [];

        foreach ($groupResults as $group) {
            $groupWeight = count($group['rule_results']);
            $totalScore += $group['score'] * $groupWeight;
            $totalWeight += $groupWeight;
            $allFailedRules = array_merge($allFailedRules, $group['failed_rules']);
        }

        $overallScore = $totalWeight > 0 ? $totalScore / $totalWeight : 0;

        return [
            'passed' => $allPassed,
            'score' => round($overallScore, 2),
            'failed_rules' => $allFailedRules,
            'decision' => $this->generateDecision($allPassed, $overallScore),
            'group_results' => $groupResults,
            'group_logic' => $criteriaLogic,
        ];
    }

    /**
     * Apply threshold-based decisions
     */
    protected function applyThresholdDecisions(array $result, Criteria $criteria): array
    {
        $thresholds = $criteria->meta['decision_thresholds'] ?? [];

        if (empty($thresholds)) {
            return $result;
        }

        // Use input data score if available, otherwise use calculated score
        $score = $this->inputData['score'] ?? $result['score'];
        $customDecision = null;

        // Check thresholds in descending order
        krsort($thresholds);
        foreach ($thresholds as $threshold => $decision) {
            if ($score >= $threshold) {
                $customDecision = $decision;
                break;
            }
        }

        if ($customDecision) {
            $result['decision'] = $customDecision;
            $result['threshold_applied'] = true;
        }

        return $result;
    }

    /**
     * Generate decision text
     */
    protected function generateDecision(bool $passed, float $score): string
    {
        if ($passed) {
            return match (true) {
                $score >= 90 => 'Excellent',
                $score >= 80 => 'Very Good',
                $score >= 70 => 'Good',
                default => 'Approved',
            };
        }

        return match (true) {
            $score >= 50 => 'Needs Improvement',
            $score >= 30 => 'Poor',
            default => 'Rejected',
        };
    }

    /**
     * Get rule execution plan
     */
    public function getExecutionPlan(Criteria $criteria): array
    {
        $groups = $this->parseRuleGroups($criteria);
        $plan = [];

        foreach ($groups as $index => $group) {
            $groupPlan = [
                'group_id' => $index + 1,
                'logic' => $group['logic'],
                'rules' => [],
            ];

            foreach ($group['rules'] as $rule) {
                $rulePlan = [
                    'field' => $rule->field ?? $rule['field'],
                    'operator' => $rule->operator ?? $rule['operator'],
                    'value' => $rule->value ?? $rule['value'],
                    'weight' => $rule->weight ?? $rule['weight'] ?? 1,
                    'dependencies' => $rule->meta['dependencies'] ?? $rule['meta']['dependencies'] ?? [],
                ];

                $groupPlan['rules'][] = $rulePlan;
            }

            $plan[] = $groupPlan;
        }

        return [
            'criteria' => $criteria->name,
            'group_combination_logic' => $criteria->meta['group_combination_logic'] ?? 'AND',
            'decision_thresholds' => $criteria->meta['decision_thresholds'] ?? [],
            'execution_plan' => $plan,
        ];
    }
}
