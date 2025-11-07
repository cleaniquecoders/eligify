<?php

declare(strict_types=1);

namespace CleaniqueCoders\Eligify\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Eligify Facade
 *
 * @method static \CleaniqueCoders\Eligify\Builder\CriteriaBuilder criteria(string $name) Create or get a criteria builder for the given name
 * @method static array evaluate(string|\CleaniqueCoders\Eligify\Models\Criteria $criteria, array|\CleaniqueCoders\Eligify\Data\Snapshot $data, bool $saveEvaluation = true, ?bool $useCache = null) Evaluate a criteria against provided data
 * @method static array evaluateWithCallbacks(\CleaniqueCoders\Eligify\Builder\CriteriaBuilder $builder, array|\CleaniqueCoders\Eligify\Data\Snapshot $data) Evaluate with callback execution
 * @method static array evaluateBatch(string|\CleaniqueCoders\Eligify\Models\Criteria $criteria, array $dataCollection, bool $saveEvaluations = true) Evaluate multiple data sets against a criteria (batch evaluation)
 * @method static void validateSchema(\CleaniqueCoders\Eligify\Models\Criteria $criteria) Validate criteria schema
 * @method static \CleaniqueCoders\Eligify\Models\Criteria getCriteria(string $name) Get criteria by name
 * @method static array getAvailableCriteria() Get all available criteria
 * @method static void clearCache(string $criteriaName = null) Clear evaluation cache
 * @method static array getEvaluationHistory(\CleaniqueCoders\Eligify\Models\Criteria $criteria, int $limit = 50) Get evaluation history
 * @method static bool criteriaExists(string $name) Check if criteria exists
 * @method static \CleaniqueCoders\Eligify\Engine\RuleEngine getRuleEngine() Get the rule engine instance
 * @method static \CleaniqueCoders\Eligify\Support\Cache getCache() Get the cache instance
 *
 * @see \CleaniqueCoders\Eligify\Eligify
 */
class Eligify extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CleaniqueCoders\Eligify\Eligify::class;
    }
}
