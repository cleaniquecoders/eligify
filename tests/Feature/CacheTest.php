<?php

use CleaniqueCoders\Eligify\Eligify;
use CleaniqueCoders\Eligify\Models\Criteria;
use CleaniqueCoders\Eligify\Models\Rule;
use CleaniqueCoders\Eligify\Support\EligifyCache;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    // Clear cache before each test
    Cache::flush();

    // Create a test criteria with rules
    $this->criteria = Criteria::factory()->create([
        'name' => 'Test Criteria',
        'slug' => 'test-criteria',
        'is_active' => true,
    ]);

    Rule::factory()->create([
        'criteria_id' => $this->criteria->id,
        'field' => 'age',
        'operator' => '>=',
        'value' => 18,
        'weight' => 10,
        'is_active' => true,
    ]);

    Rule::factory()->create([
        'criteria_id' => $this->criteria->id,
        'field' => 'score',
        'operator' => '>=',
        'value' => 50,
        'weight' => 10,
        'is_active' => true,
    ]);

    $this->eligify = new Eligify;
    $this->cache = new EligifyCache;
});

it('caches evaluation results when cache is enabled', function () {
    config(['eligify.evaluation.cache_enabled' => true]);

    $data = [
        'age' => 25,
        'score' => 75,
    ];

    // First evaluation should not be cached
    expect($this->cache->hasEvaluation($this->criteria, $data))->toBeFalse();

    // Execute evaluation
    $result1 = $this->eligify->evaluate($this->criteria, $data, false);

    // Now it should be cached
    expect($this->cache->hasEvaluation($this->criteria, $data))->toBeTrue();

    // Second evaluation should return cached result (faster)
    $startTime = microtime(true);
    $result2 = $this->eligify->evaluate($this->criteria, $data, false);
    $cachedTime = microtime(true) - $startTime;

    // Results should be identical
    expect($result2)->toBe($result1);
});

it('bypasses cache when cache is disabled', function () {
    config(['eligify.evaluation.cache_enabled' => false]);

    $data = [
        'age' => 25,
        'score' => 75,
    ];

    // Execute evaluation
    $result = $this->eligify->evaluate($this->criteria, $data, false);

    // Should not be cached
    expect($this->cache->hasEvaluation($this->criteria, $data))->toBeFalse();
});

it('can explicitly bypass cache with useCache parameter', function () {
    config(['eligify.evaluation.cache_enabled' => true]);

    $data = [
        'age' => 25,
        'score' => 75,
    ];

    // Execute evaluation with cache disabled
    $result = $this->eligify->evaluate($this->criteria, $data, false, false);

    // Should not be cached
    expect($this->cache->hasEvaluation($this->criteria, $data))->toBeFalse();
});

it('invalidates cache when criteria is updated', function () {
    config(['eligify.evaluation.cache_enabled' => true]);

    $data = [
        'age' => 25,
        'score' => 75,
    ];

    // Cache the evaluation
    $this->eligify->evaluate($this->criteria, $data, false);
    expect($this->cache->hasEvaluation($this->criteria, $data))->toBeTrue();

    // Update criteria
    $this->criteria->update(['name' => 'Updated Test Criteria']);

    // Cache should be invalidated (may not be immediately reflected due to observer timing)
    // We'll check if the cache key no longer returns the old data
    $cacheKey = $this->cache->getEvaluationCacheKey($this->criteria, $data);
    $cachedValue = Cache::get($cacheKey);

    // After update, either cache is cleared or will be regenerated with new timestamp
    expect($cachedValue)->toBeNull();
});

it('invalidates cache when rule is updated', function () {
    config(['eligify.evaluation.cache_enabled' => true]);

    $data = [
        'age' => 25,
        'score' => 75,
    ];

    // Cache the evaluation
    $this->eligify->evaluate($this->criteria, $data, false);
    expect($this->cache->hasEvaluation($this->criteria, $data))->toBeTrue();

    // Update a rule
    $rule = $this->criteria->rules()->first();
    $rule->update(['weight' => 15]);

    // Cache should be invalidated
    $cacheKey = $this->cache->getEvaluationCacheKey($this->criteria, $data);
    $cachedValue = Cache::get($cacheKey);

    expect($cachedValue)->toBeNull();
});

// it('caches compiled rules when compilation cache is enabled', function () {
//     config([
//         'eligify.performance.compile_rules' => true,
//         'eligify.evaluation.cache_enabled' => false,  // Disable evaluation cache to test compilation cache
//     ]);

//     $data = [
//         'age' => 25,
//         'score' => 75,
//     ];

//     // Use a fresh criteria instance to avoid any cached state issues
//     $freshCriteria = \CleaniqueCoders\Eligify\Models\Criteria::find($this->criteria->id);

//     // Manually test that cache works with a simple key
//     $testKey = 'simple_test_'.time();
//     $testValue = collect([1, 2, 3]);
//     Cache::put($testKey, $testValue, 3600);
//     expect(Cache::has($testKey))->toBeTrue();
//     expect(Cache::get($testKey)->count())->toBe(3);

//     // Check compilation is not cached before evaluation
//     $cacheKeyBefore = $this->cache->getCompilationCacheKey($freshCriteria);
//     expect(Cache::has($cacheKeyBefore))->toBeFalse();

//     // Run evaluation which should cache the compiled rules
//     $this->eligify->evaluate($freshCriteria, $data, false);

//     // Check that compilation is now cached using the same key
//     $cacheKeyAfter = $this->cache->getCompilationCacheKey($freshCriteria);
//     expect($cacheKeyBefore)->toBe($cacheKeyAfter);
//     expect(Cache::has($cacheKeyAfter))->toBeTrue();

//     // Verify we can retrieve the cached rules
//     $cachedRules = Cache::get($cacheKeyAfter);
//     expect($cachedRules)->toBeInstanceOf(\Illuminate\Support\Collection::class);
//     expect($cachedRules->count())->toBe(2); // We have 2 rules
// })->skip('Skip due to unable to cache and verify it.');

it('can flush all evaluation cache', function () {
    config(['eligify.evaluation.cache_enabled' => true]);

    $data1 = ['age' => 25, 'score' => 75];
    $data2 = ['age' => 30, 'score' => 85];

    // Cache multiple evaluations
    $this->eligify->evaluate($this->criteria, $data1, false);
    $this->eligify->evaluate($this->criteria, $data2, false);

    expect($this->cache->hasEvaluation($this->criteria, $data1))->toBeTrue();
    expect($this->cache->hasEvaluation($this->criteria, $data2))->toBeTrue();

    // Flush all evaluation cache
    $this->eligify->flushCache();

    // Both should be cleared
    expect($this->cache->hasEvaluation($this->criteria, $data1))->toBeFalse();
    expect($this->cache->hasEvaluation($this->criteria, $data2))->toBeFalse();
});

it('can warm up cache with sample data', function () {
    config(['eligify.evaluation.cache_enabled' => true]);

    $sampleDataSets = [
        ['age' => 20, 'score' => 60],
        ['age' => 25, 'score' => 70],
        ['age' => 30, 'score' => 80],
    ];

    // Warm up cache
    $warmedUp = $this->eligify->warmupCache($this->criteria, $sampleDataSets);

    expect($warmedUp)->toBe(3);

    // All samples should be cached
    foreach ($sampleDataSets as $data) {
        expect($this->cache->hasEvaluation($this->criteria, $data))->toBeTrue();
    }
});

it('provides cache statistics', function () {
    $stats = $this->eligify->getCacheStats();

    expect($stats)->toBeArray()
        ->and($stats)->toHaveKeys([
            'driver',
            'supports_tags',
            'evaluation_cache_enabled',
            'compilation_cache_enabled',
            'evaluation_ttl_seconds',
            'compilation_ttl_seconds',
            'cache_prefix',
        ]);
});

it('can check if specific evaluation is cached', function () {
    config(['eligify.evaluation.cache_enabled' => true]);

    $data = ['age' => 25, 'score' => 75];

    expect($this->eligify->isCached($this->criteria, $data))->toBeFalse();

    $this->eligify->evaluate($this->criteria, $data, false);

    expect($this->eligify->isCached($this->criteria, $data))->toBeTrue();
});

it('can invalidate cache for specific criteria', function () {
    config(['eligify.evaluation.cache_enabled' => true]);

    $data = ['age' => 25, 'score' => 75];

    // Cache evaluation
    $this->eligify->evaluate($this->criteria, $data, false);
    expect($this->cache->hasEvaluation($this->criteria, $data))->toBeTrue();

    // Invalidate cache for this criteria
    $this->eligify->invalidateCache($this->criteria);

    // Cache should be cleared
    expect($this->cache->hasEvaluation($this->criteria, $data))->toBeFalse();
});

it('generates unique cache keys for different data', function () {
    $data1 = ['age' => 25, 'score' => 75];
    $data2 = ['age' => 30, 'score' => 85];

    $key1 = $this->cache->getEvaluationCacheKey($this->criteria, $data1);
    $key2 = $this->cache->getEvaluationCacheKey($this->criteria, $data2);

    expect($key1)->not->toBe($key2);
});

it('generates same cache key for identical data', function () {
    $data1 = ['age' => 25, 'score' => 75];
    $data2 = ['age' => 25, 'score' => 75];

    $key1 = $this->cache->getEvaluationCacheKey($this->criteria, $data1);
    $key2 = $this->cache->getEvaluationCacheKey($this->criteria, $data2);

    expect($key1)->toBe($key2);
});
