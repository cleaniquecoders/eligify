<?php

namespace CleaniqueCoders\Eligify;

use CleaniqueCoders\Eligify\Builder\CriteriaBuilder;
use CleaniqueCoders\Eligify\Engine\RuleEngine;
use CleaniqueCoders\Eligify\Models\AuditLog;
use CleaniqueCoders\Eligify\Models\Criteria;
use CleaniqueCoders\Eligify\Models\Evaluation;

class Eligify
{
    protected RuleEngine $ruleEngine;

    public function __construct()
    {
        $this->ruleEngine = new RuleEngine;
    }

    /**
     * Create or get a criteria builder for the given name
     */
    public static function criteria(string $name): CriteriaBuilder
    {
        return new CriteriaBuilder($name);
    }

    /**
     * Evaluate a criteria against provided data
     */
    public function evaluate(string|Criteria $criteria, array $data, bool $saveEvaluation = true): array
    {
        // Get criteria model if string provided
        if (is_string($criteria)) {
            $criteriaModel = Criteria::where('slug', str($criteria)->slug())->first();

            if (! $criteriaModel) {
                throw new \InvalidArgumentException("Criteria '{$criteria}' not found");
            }
        } else {
            $criteriaModel = $criteria;
        }

        // Run the evaluation
        $result = $this->ruleEngine->evaluate($criteriaModel, $data);

        // Save evaluation record if requested
        if ($saveEvaluation) {
            $this->saveEvaluation($criteriaModel, $data, $result);
        }

        // Log the evaluation for audit
        $this->logAudit($criteriaModel, $data, $result);

        return $result;
    }

    /**
     * Evaluate with callback execution
     */
    public function evaluateWithCallbacks(CriteriaBuilder $builder, array $data): array
    {
        // Save pending rules first
        $builder->save();

        // Run evaluation
        $result = $this->evaluate($builder->getCriteria(), $data);

        // Execute callbacks based on result
        if ($result['passed'] && $builder->getOnPassCallback()) {
            call_user_func($builder->getOnPassCallback(), $data, $result);
        } elseif (! $result['passed'] && $builder->getOnFailCallback()) {
            call_user_func($builder->getOnFailCallback(), $data, $result);
        }

        return $result;
    }

    /**
     * Get all available criteria
     */
    public static function getAllCriteria(): \Illuminate\Database\Eloquent\Collection
    {
        return Criteria::where('is_active', true)->get();
    }

    /**
     * Get a specific criteria by name or slug
     */
    public static function getCriteria(string $identifier): ?Criteria
    {
        return Criteria::where('name', $identifier)
            ->orWhere('slug', $identifier)
            ->first();
    }

    /**
     * Delete a criteria and all its rules
     */
    public static function deleteCriteria(string $identifier): bool
    {
        $criteria = static::getCriteria($identifier);

        if (! $criteria) {
            return false;
        }

        // Delete associated rules and evaluations
        $criteria->rules()->delete();
        $criteria->evaluations()->delete();

        return $criteria->delete();
    }

    /**
     * Get recent evaluations for a criteria
     */
    public static function getRecentEvaluations(string|Criteria $criteria, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        if (is_string($criteria)) {
            $criteria = static::getCriteria($criteria);
        }

        if (! $criteria) {
            return new \Illuminate\Database\Eloquent\Collection;
        }

        return $criteria->evaluations()
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get evaluation statistics for a criteria
     */
    public static function getEvaluationStats(string|Criteria $criteria): array
    {
        if (is_string($criteria)) {
            $criteria = static::getCriteria($criteria);
        }

        if (! $criteria) {
            return [];
        }

        $evaluations = $criteria->evaluations();

        return [
            'total_evaluations' => $evaluations->count(),
            'passed_evaluations' => $evaluations->where('passed', true)->count(),
            'failed_evaluations' => $evaluations->where('passed', false)->count(),
            'average_score' => $evaluations->avg('score'),
            'highest_score' => $evaluations->max('score'),
            'lowest_score' => $evaluations->min('score'),
            'pass_rate' => $evaluations->count() > 0 ?
                ($evaluations->where('passed', true)->count() / $evaluations->count()) * 100 : 0,
        ];
    }

    /**
     * Create a preset criteria from configuration
     */
    public static function createFromPreset(string $presetName): ?CriteriaBuilder
    {
        $presets = config('eligify.presets', []);

        if (! isset($presets[$presetName])) {
            return null;
        }

        $preset = $presets[$presetName];
        $builder = static::criteria($preset['name'])
            ->description($preset['description'])
            ->passThreshold($preset['pass_threshold']);

        // Add rules from preset
        foreach ($preset['rules'] as $rule) {
            $builder->addRule(
                $rule['field'],
                $rule['operator'],
                $rule['value'],
                $rule['weight']
            );
        }

        return $builder;
    }

    /**
     * Save evaluation record to database
     */
    protected function saveEvaluation(Criteria $criteria, array $data, array $result): void
    {
        Evaluation::create([
            'criteria_id' => $criteria->id,
            'data' => $data,
            'result' => $result,
            'passed' => $result['passed'],
            'score' => $result['score'],
            'decision' => $result['decision'],
            'execution_log' => $result['execution_log'],
        ]);
    }

    /**
     * Log audit trail
     */
    protected function logAudit(Criteria $criteria, array $data, array $result): void
    {
        if (! config('eligify.audit.enabled', true)) {
            return;
        }

        AuditLog::create([
            'event' => 'evaluation_completed',
            'auditable_type' => Criteria::class,
            'auditable_id' => $criteria->id,
            'data' => [
                'criteria' => $criteria->getAttribute('name'),
                'input_data' => config('eligify.audit.include_sensitive_data', false) ? $data : [],
                'result' => $result,
                'user_id' => null, // Will be set by model observers if needed
                'ip_address' => app('request')->ip(),
                'user_agent' => app('request')->userAgent(),
            ],
        ]);
    }

    /**
     * Get the rule engine instance
     */
    public function getRuleEngine(): RuleEngine
    {
        return $this->ruleEngine;
    }

    /**
     * Set a custom rule engine
     */
    public function setRuleEngine(RuleEngine $engine): self
    {
        $this->ruleEngine = $engine;

        return $this;
    }
}
