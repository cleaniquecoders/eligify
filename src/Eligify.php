<?php

declare(strict_types=1);

namespace CleaniqueCoders\Eligify;

use CleaniqueCoders\Eligify\Audit\AuditLogger;
use CleaniqueCoders\Eligify\Builder\CriteriaBuilder;
use CleaniqueCoders\Eligify\Data\Snapshot;
use CleaniqueCoders\Eligify\Engine\AdvancedRuleEngine;
use CleaniqueCoders\Eligify\Engine\RuleEngine;
use CleaniqueCoders\Eligify\Models\Criteria;
use CleaniqueCoders\Eligify\Models\Evaluation;
use CleaniqueCoders\Eligify\Models\Rule;
use CleaniqueCoders\Eligify\Support\Cache;

class Eligify
{
    protected RuleEngine $ruleEngine;

    protected ?AdvancedRuleEngine $advancedEngine = null;

    protected Cache $cache;

    public function __construct()
    {
        $this->ruleEngine = new RuleEngine;
        $this->cache = new Cache;
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
     *
     * @param  string|Criteria  $criteria  The criteria name or model
     * @param  array|Snapshot  $data  The data to evaluate (accepts both formats)
     * @param  bool  $saveEvaluation  Whether to save the evaluation result
     * @param  bool  $useCache  Whether to use cache (defaults to config setting)
     * @return array Evaluation result with passed status, score, and details
     *
     * @throws \InvalidArgumentException When criteria is not found or data is invalid
     * @throws \RuntimeException When evaluation fails
     */
    public function evaluate(string|Criteria $criteria, array|Snapshot $data, bool $saveEvaluation = true, ?bool $useCache = null): array
    {
        try {
            // Get criteria model if string provided
            if (is_string($criteria)) {
                $criteriaModel = $this->getCriteriaWithRules($criteria);

                if (! $criteriaModel) {
                    throw new \InvalidArgumentException("Criteria '{$criteria}' not found");
                }
            } else {
                $criteriaModel = $criteria;
            }

            // Convert Snapshot to array for storage operations
            $dataArray = $data instanceof Snapshot ? $data->toArray() : $data;

            // Basic input validation for security
            $this->validateInputData($dataArray, $criteriaModel);

            // Determine if we should use cache
            $shouldUseCache = $useCache ?? $this->cache->isEvaluationCacheEnabled();

            // Use cache if enabled
            if ($shouldUseCache) {
                $result = $this->cache->rememberEvaluation($criteriaModel, $data, function () use ($criteriaModel, $data, $dataArray, $saveEvaluation) {
                    return $this->performEvaluation($criteriaModel, $data, $dataArray, $saveEvaluation);
                });
            } else {
                $result = $this->performEvaluation($criteriaModel, $data, $dataArray, $saveEvaluation);
            }

            return $result;
        } catch (\InvalidArgumentException $e) {
            // Re-throw validation errors as-is
            throw $e;
        } catch (\Throwable $e) {
            // Log unexpected errors
            logger()->error('Eligify evaluation failed', [
                'criteria' => is_string($criteria) ? $criteria : $criteria->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Wrap in runtime exception
            throw new \RuntimeException('Evaluation failed: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Get criteria with rules using optimized query
     */
    protected function getCriteriaWithRules(string $criteriaName): ?Criteria
    {
        return Criteria::with(['rules' => function ($query) {
            $query->where('is_active', true)
                ->orderBy('order', 'asc');
        }])
            ->where('slug', str($criteriaName)->slug())
            ->where('is_active', true)
            ->first();
    }

    /**
     * Perform the actual evaluation (extracted for caching)
     */
    protected function performEvaluation(Criteria $criteriaModel, array|Snapshot $data, array $dataArray, bool $saveEvaluation): array
    {
        // Run the evaluation using appropriate engine
        $engine = $this->getEngineForCriteria($criteriaModel);
        $result = $engine->evaluate($criteriaModel, $data);

        // Save evaluation record if requested
        if ($saveEvaluation) {
            $this->saveEvaluation($criteriaModel, $dataArray, $result);
        }

        // Log the evaluation for audit
        $this->logAudit($criteriaModel, $dataArray, $result);

        return $result;
    }

    /**
     * Evaluate with callback execution
     *
     * @param  CriteriaBuilder  $builder  The criteria builder with rules and callbacks
     * @param  array|Snapshot  $data  The data to evaluate
     * @return array Evaluation result
     */
    public function evaluateWithCallbacks(CriteriaBuilder $builder, array|Snapshot $data): array
    {
        // Save pending rules first
        $builder->save();

        $criteria = $builder->getCriteria();
        $workflowManager = $builder->getWorkflowManager();

        // Convert Snapshot to array for callbacks
        $dataArray = $data instanceof Snapshot ? $data->toArray() : $data;

        // Run evaluation
        $result = $this->evaluate($criteria, $data, false); // Don't save evaluation twice

        // Execute basic callbacks (backward compatibility)
        try {
            if ($result['passed'] && $builder->getOnPassCallback()) {
                call_user_func($builder->getOnPassCallback(), $dataArray, $result);
            } elseif (! $result['passed'] && $builder->getOnFailCallback()) {
                call_user_func($builder->getOnFailCallback(), $dataArray, $result);
            }
        } catch (\Throwable $e) {
            // Log callback execution errors
            if (config('eligify.workflow.log_callback_errors', true)) {
                logger()->error('Eligify basic callback execution failed', [
                    'error' => $e->getMessage(),
                    'criteria' => $criteria->name,
                ]);
            }

            // Re-throw if configured to fail on callback errors
            if (config('eligify.workflow.fail_on_callback_error', false)) {
                throw $e;
            }
        }

        // Execute workflow callbacks
        $workflowManager->executeEvaluationWorkflow($criteria, $dataArray, $result);

        // Save evaluation record
        $this->saveEvaluation($criteria, $dataArray, $result);

        return $result;
    }

    /**
     * Evaluate multiple data sets against a criteria (batch evaluation)
     */
    public function evaluateBatch(string|Criteria $criteria, array $dataCollection, bool $saveEvaluations = true): array
    {
        // Get criteria model if string provided with eager loading
        if (is_string($criteria)) {
            $criteriaModel = $this->getCriteriaWithRules($criteria);

            if (! $criteriaModel) {
                throw new \InvalidArgumentException("Criteria '{$criteria}' not found");
            }
        } else {
            $criteriaModel = $criteria;
        }

        $results = [];
        $batchSize = config('eligify.performance.batch_size', 100);

        // Process in chunks to avoid memory issues
        $chunks = array_chunk($dataCollection, $batchSize);

        foreach ($chunks as $chunk) {
            foreach ($chunk as $index => $data) {
                try {
                    $result = $this->ruleEngine->evaluate($criteriaModel, $data);

                    // Save evaluation if requested
                    if ($saveEvaluations) {
                        $this->saveEvaluation($criteriaModel, $data, $result);
                    }

                    // Log for audit
                    $this->logAudit($criteriaModel, $data, $result);

                    $results[] = array_merge($result, [
                        'index' => $index,
                        'data_hash' => md5(serialize($data)),
                    ]);
                } catch (\Throwable $e) {
                    $results[] = [
                        'index' => $index,
                        'error' => $e->getMessage(),
                        'passed' => false,
                        'score' => 0,
                        'data_hash' => md5(serialize($data)),
                    ];
                }
            }
        }

        return [
            'total_evaluated' => count($results),
            'total_passed' => count(array_filter($results, fn ($r) => $r['passed'] ?? false)),
            'total_failed' => count(array_filter($results, fn ($r) => ! ($r['passed'] ?? false))),
            'results' => $results,
            'criteria' => $criteriaModel->toArray(),
        ];
    }

    /**
     * Evaluate multiple data sets with callbacks (batch with workflow)
     */
    public function evaluateBatchWithCallbacks(CriteriaBuilder $builder, array $dataCollection): array
    {
        // Save pending rules first
        $builder->save();

        $criteria = $builder->getCriteria();
        $workflowManager = $builder->getWorkflowManager();
        $results = [];

        foreach ($dataCollection as $index => $data) {
            try {
                // Run evaluation
                $result = $this->ruleEngine->evaluate($criteria, $data);

                // Execute basic callbacks (backward compatibility)
                if ($result['passed'] && $builder->getOnPassCallback()) {
                    call_user_func($builder->getOnPassCallback(), $data, $result);
                } elseif (! $result['passed'] && $builder->getOnFailCallback()) {
                    call_user_func($builder->getOnFailCallback(), $data, $result);
                }

                // Execute workflow callbacks
                $workflowManager->executeEvaluationWorkflow($criteria, $data, $result);

                // Save evaluation record
                $this->saveEvaluation($criteria, $data, $result);

                $results[] = array_merge($result, [
                    'index' => $index,
                    'data_hash' => md5(serialize($data)),
                ]);
            } catch (\Throwable $e) {
                $results[] = [
                    'index' => $index,
                    'error' => $e->getMessage(),
                    'passed' => false,
                    'score' => 0,
                    'data_hash' => md5(serialize($data)),
                ];
            }
        }

        return [
            'total_evaluated' => count($results),
            'total_passed' => count(array_filter($results, fn ($r) => $r['passed'] ?? false)),
            'total_failed' => count(array_filter($results, fn ($r) => ! ($r['passed'] ?? false))),
            'results' => $results,
            'criteria' => $criteria->toArray(),
        ];
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
            'uuid' => (string) str()->uuid(),
            'criteria_id' => $criteria->id,
            'passed' => $result['passed'],
            'score' => $result['score'],
            'decision' => $result['decision'] ?? null,
            'context' => $data,
            'failed_rules' => $result['failed_rules'] ?? [],
            'rule_results' => $result['rule_results'] ?? [],
            'evaluated_at' => now(),
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

        $auditLogger = app(AuditLogger::class);
        $auditLogger->logEvaluation($criteria, $data, $result);
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

    /**
     * Get advanced rule engine instance
     */
    public function getAdvancedRuleEngine(): AdvancedRuleEngine
    {
        if ($this->advancedEngine === null) {
            $this->advancedEngine = new AdvancedRuleEngine($this->ruleEngine);
        }

        return $this->advancedEngine;
    }

    /**
     * Get appropriate engine for criteria
     */
    protected function getEngineForCriteria(Criteria $criteria)
    {
        $useAdvanced = $criteria->meta['use_advanced_engine'] ?? false;

        // Also check if criteria has complex features that require advanced engine
        $hasComplexFeatures =
            isset($criteria->meta['group_combination_logic']) ||
            isset($criteria->meta['decision_thresholds']) ||
            $criteria->rules()->whereNotNull('meta')->exists();

        return ($useAdvanced || $hasComplexFeatures)
            ? $this->getAdvancedRuleEngine()
            : $this->ruleEngine;
    }

    /**
     * Get the cache instance
     */
    public function getCache(): Cache
    {
        return $this->cache;
    }

    /**
     * Invalidate cache for a specific criteria
     */
    public function invalidateCache(string|Criteria $criteria): bool
    {
        $criteriaModel = is_string($criteria)
            ? Criteria::where('slug', str($criteria)->slug())->first()
            : $criteria;

        if (! $criteriaModel) {
            return false;
        }

        return $this->cache->invalidateCriteriaEvaluations($criteriaModel);
    }

    /**
     * Warm up cache for a criteria with sample data
     */
    public function warmupCache(string|Criteria $criteria, array $sampleDataSets): int
    {
        $criteriaModel = is_string($criteria)
            ? Criteria::where('slug', str($criteria)->slug())->first()
            : $criteria;

        if (! $criteriaModel) {
            throw new \InvalidArgumentException('Criteria not found');
        }

        $warmedUp = 0;

        foreach ($sampleDataSets as $data) {
            try {
                // Evaluate and cache the result
                $this->evaluate($criteriaModel, $data, false, true);
                $warmedUp++;
            } catch (\Throwable $e) {
                logger()->warning('Failed to warm up cache', [
                    'criteria' => $criteriaModel->name,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $warmedUp;
    }

    /**
     * Check if an evaluation is cached
     */
    public function isCached(string|Criteria $criteria, array|Snapshot $data): bool
    {
        $criteriaModel = is_string($criteria)
            ? Criteria::where('slug', str($criteria)->slug())->first()
            : $criteria;

        if (! $criteriaModel) {
            return false;
        }

        return $this->cache->hasEvaluation($criteriaModel, $data);
    }

    /**
     * Validate input data for security
     */
    protected function validateInputData(array $data, Criteria $criteria): void
    {
        if (! config('eligify.security.validate_input', true)) {
            return;
        }

        $maxFieldLength = config('eligify.security.max_field_length', 255);
        $maxValueLength = config('eligify.security.max_value_length', 1000);

        foreach ($data as $key => $value) {
            // Check field name length
            if (strlen((string) $key) > $maxFieldLength) {
                throw new \InvalidArgumentException("Field name '{$key}' exceeds maximum length of {$maxFieldLength}");
            }

            // Check value length for strings
            if (is_string($value) && strlen($value) > $maxValueLength) {
                throw new \InvalidArgumentException("Value for field '{$key}' exceeds maximum length of {$maxValueLength}");
            }

            // Check for suspicious patterns
            if (is_string($value) && $this->containsSuspiciousContent($value)) {
                logger()->warning('Suspicious content detected in evaluation data', [
                    'field' => $key,
                    'value_preview' => substr($value, 0, 50),
                ]);
            }
        }
    }

    /**
     * Check for suspicious content in input values
     */
    protected function containsSuspiciousContent(string $value): bool
    {
        $suspiciousPatterns = [
            '/\b(SELECT|INSERT|UPDATE|DELETE|DROP|UNION)\b/i', // SQL injection
            '/<script[^>]*>.*?<\/script>/si', // XSS
            '/javascript:/i', // JavaScript protocols
            '/vbscript:/i', // VBScript protocols
            '/on\w+\s*=/i', // Event handlers
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Flush all evaluation caches
     */
    public function flushCache(): bool
    {
        return $this->cache->flushEvaluationCache();
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        return $this->cache->getStatistics();
    }

    /**
     * Evaluate against a specific historical version of criteria
     *
     * @param  string|Criteria  $criteria  The criteria name or model
     * @param  int  $version  The version number to evaluate against
     * @param  array|Snapshot  $data  The data to evaluate
     * @param  bool  $saveEvaluation  Whether to save the evaluation result
     * @return array Evaluation result with passed status and score
     *
     * @throws \InvalidArgumentException When criteria or version is not found
     */
    public function evaluateVersion(string|Criteria $criteria, int $version, array|Snapshot $data, bool $saveEvaluation = true): array
    {
        try {
            // Get criteria model if string provided
            if (is_string($criteria)) {
                $criteriaModel = $this->getCriteriaWithRules($criteria);

                if (! $criteriaModel) {
                    throw new \InvalidArgumentException("Criteria '{$criteria}' not found");
                }
            } else {
                $criteriaModel = $criteria;
            }

            // Retrieve the version snapshot
            $versionRecord = $criteriaModel->version($version);

            if (! $versionRecord) {
                throw new \InvalidArgumentException(
                    "Version {$version} not found for criteria '{$criteriaModel->name}'"
                );
            }

            // Convert Snapshot to array for storage operations
            $dataArray = $data instanceof Snapshot ? $data->toArray() : $data;

            // Validate input data
            $this->validateInputData($dataArray, $criteriaModel);

            // Create temporary rules collection from version snapshot
            $rulesSnapshot = $versionRecord->getRulesSnapshot();

            // Evaluate against version snapshot
            $result = $this->evaluateRulesSnapshot($criteriaModel, $rulesSnapshot, $dataArray);

            // Enhance result with version information
            $result['version'] = $version;
            $result['version_description'] = $versionRecord->description;

            // Save evaluation record if requested with version info
            if ($saveEvaluation) {
                $this->saveEvaluation($criteriaModel, $dataArray, $result);
            }

            // Log the evaluation for audit
            $this->logAudit($criteriaModel, $dataArray, $result);

            return $result;
        } catch (\InvalidArgumentException $e) {
            throw $e;
        } catch (\Throwable $e) {
            logger()->error('Eligify version evaluation failed', [
                'criteria' => is_string($criteria) ? $criteria : $criteria->name,
                'version' => $version,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Version evaluation failed: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Evaluate rules snapshot (from a version)
     *
     * @param  Criteria  $criteria  The criteria model
     * @param  array  $rulesSnapshot  Array of rule data from snapshot
     * @param  array  $dataArray  The data to evaluate
     * @return array Evaluation result
     */
    protected function evaluateRulesSnapshot(Criteria $criteria, array $rulesSnapshot, array $dataArray): array
    {
        // Temporarily override criteria rules with snapshot
        $originalRules = $criteria->rules()->get();

        // Create collection from snapshot by building temporary Rule instances
        $snapshotRules = collect($rulesSnapshot)->map(function (array $ruleData) {
            return new Rule($ruleData);
        });

        // Determine which engine to use
        $engine = $this->getEngine($criteria);

        // Evaluate using the engine - since Rule instances have the data needed
        // We evaluate by constructing a temporary criteria-like structure
        $temporaryCriteria = clone $criteria;
        $temporaryCriteria->setRelation('rules', $snapshotRules);

        $result = $engine->evaluate($temporaryCriteria, $dataArray);

        return $result;
    }

    /**
     * Get all versions available for a criteria
     *
     * @param  string|Criteria  $criteria  The criteria name or model
     * @return array Array of version information
     */
    public function getVersionHistory(string|Criteria $criteria): array
    {
        // Get criteria model if string provided
        if (is_string($criteria)) {
            $criteriaModel = $this->getCriteriaWithRules($criteria);

            if (! $criteriaModel) {
                throw new \InvalidArgumentException("Criteria '{$criteria}' not found");
            }
        } else {
            $criteriaModel = $criteria;
        }

        return $criteriaModel->versions()
            ->orderBy('version')
            ->get()
            ->map(fn ($version) => [
                'version' => $version->version,
                'description' => $version->description,
                'created_at' => $version->created_at,
                'rules_count' => count($version->getRulesSnapshot()),
                'meta' => $version->meta,
            ])
            ->toArray();
    }

    /**
     * Compare two versions of criteria
     *
     * @param  string|Criteria  $criteria  The criteria name or model
     * @param  int  $version1  First version number
     * @param  int  $version2  Second version number
     * @return array Differences between versions
     */
    public function compareVersions(string|Criteria $criteria, int $version1, int $version2): array
    {
        // Get criteria model if string provided
        if (is_string($criteria)) {
            $criteriaModel = $this->getCriteriaWithRules($criteria);

            if (! $criteriaModel) {
                throw new \InvalidArgumentException("Criteria '{$criteria}' not found");
            }
        } else {
            $criteriaModel = $criteria;
        }

        return $criteriaModel->compareVersions($version1, $version2);
    }

    /**
     * Get the appropriate engine for criteria evaluation
     *
     * @param  Criteria  $criteria  The criteria model
     * @return RuleEngine The evaluation engine to use
     */
    protected function getEngine(Criteria $criteria): RuleEngine
    {
        // For versioning, always use base rule engine
        // Advanced engine can be integrated separately if needed
        return $this->ruleEngine;
    }
}
