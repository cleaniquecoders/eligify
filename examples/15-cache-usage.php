<?php

/**
 * Example 15: Cache Implementation Usage
 *
 * This example demonstrates how to use Eligify's built-in caching system
 * to improve performance for repeated evaluations.
 */

require_once __DIR__.'/bootstrap.php';

use CleaniqueCoders\Eligify\Facades\Eligify;
use Illuminate\Support\Facades\Cache;

echo "=== Eligify Cache Implementation Example ===\n\n";

// Clear cache to start fresh
Cache::flush();

// Create a test criteria
$criteria = Eligify::criteria('performance-test')
    ->description('Test criteria for cache demonstration')
    ->addRule('age', '>=', 21)
    ->addRule('income', '>=', 30000)
    ->addRule('credit_score', '>=', 650)
    ->addRule('employment_years', '>=', 2)
    ->save();

$testData = [
    'age' => 28,
    'income' => 45000,
    'credit_score' => 720,
    'employment_years' => 5,
];

echo "1. Cache Statistics\n";
echo "-------------------\n";
$stats = Eligify::getCacheStats();
echo "Cache Driver: {$stats['driver']}\n";
echo 'Supports Tags: '.($stats['supports_tags'] ? 'Yes' : 'No')."\n";
echo 'Evaluation Cache: '.($stats['evaluation_cache_enabled'] ? 'Enabled' : 'Disabled')."\n";
echo 'Compilation Cache: '.($stats['compilation_cache_enabled'] ? 'Enabled' : 'Disabled')."\n";
echo 'Evaluation TTL: '.($stats['evaluation_ttl_seconds'] / 60)." minutes\n";
echo 'Compilation TTL: '.($stats['compilation_ttl_seconds'] / 60)." minutes\n\n";

echo "2. First Evaluation (No Cache)\n";
echo "--------------------------------\n";
$isCached = Eligify::isCached('performance-test', $testData);
echo 'Is Cached: '.($isCached ? 'Yes' : 'No')."\n";

$startTime = microtime(true);
$result1 = Eligify::evaluate('performance-test', $testData, false);
$time1 = round((microtime(true) - $startTime) * 1000, 2);

echo 'Result: '.($result1['passed'] ? 'PASSED' : 'FAILED')."\n";
echo "Score: {$result1['score']}\n";
echo "Execution Time: {$time1}ms\n\n";

echo "3. Second Evaluation (With Cache)\n";
echo "----------------------------------\n";
$isCached = Eligify::isCached('performance-test', $testData);
echo 'Is Cached: '.($isCached ? 'Yes' : 'No')."\n";

$startTime = microtime(true);
$result2 = Eligify::evaluate('performance-test', $testData, false);
$time2 = round((microtime(true) - $startTime) * 1000, 2);

echo 'Result: '.($result2['passed'] ? 'PASSED' : 'FAILED')."\n";
echo "Score: {$result2['score']}\n";
echo "Execution Time: {$time2}ms\n";
echo 'Performance Improvement: '.round(($time1 - $time2) / $time1 * 100, 1)."%\n\n";

echo "4. Bypass Cache\n";
echo "---------------\n";
$startTime = microtime(true);
$result3 = Eligify::evaluate('performance-test', $testData, false, false); // useCache = false
$time3 = round((microtime(true) - $startTime) * 1000, 2);

echo "Execution Time (No Cache): {$time3}ms\n\n";

echo "5. Cache Warming\n";
echo "----------------\n";
$sampleDataSets = [
    ['age' => 25, 'income' => 35000, 'credit_score' => 680, 'employment_years' => 3],
    ['age' => 30, 'income' => 50000, 'credit_score' => 750, 'employment_years' => 6],
    ['age' => 35, 'income' => 65000, 'credit_score' => 800, 'employment_years' => 10],
];

$warmedUp = Eligify::warmupCache('performance-test', $sampleDataSets);
echo "Warmed up {$warmedUp} evaluations\n\n";

// Verify warmed up cache
foreach ($sampleDataSets as $index => $data) {
    $isCached = Eligify::isCached('performance-test', $data);
    echo 'Sample '.($index + 1).' is cached: '.($isCached ? 'Yes' : 'No')."\n";
}

echo "\n6. Cache Invalidation\n";
echo "---------------------\n";
echo 'Before invalidation - Is cached: '.(Eligify::isCached('performance-test', $testData) ? 'Yes' : 'No')."\n";

Eligify::invalidateCache('performance-test');

echo 'After invalidation - Is cached: '.(Eligify::isCached('performance-test', $testData) ? 'Yes' : 'No')."\n\n";

echo "7. Performance Comparison\n";
echo "-------------------------\n";
echo "Running 100 evaluations...\n";

// Clear cache first
Eligify::flushCache();

// Without cache
$startTime = microtime(true);
for ($i = 0; $i < 100; $i++) {
    Eligify::evaluate('performance-test', $testData, false, false);
}
$timeWithoutCache = round((microtime(true) - $startTime) * 1000, 2);

// With cache
$startTime = microtime(true);
for ($i = 0; $i < 100; $i++) {
    Eligify::evaluate('performance-test', $testData, false, true);
}
$timeWithCache = round((microtime(true) - $startTime) * 1000, 2);

echo "100 evaluations WITHOUT cache: {$timeWithoutCache}ms\n";
echo "100 evaluations WITH cache: {$timeWithCache}ms\n";
echo 'Performance improvement: '.round(($timeWithoutCache - $timeWithCache) / $timeWithoutCache * 100, 1)."%\n";
echo 'Time saved: '.round($timeWithoutCache - $timeWithCache, 2)."ms\n\n";

echo "8. Flush All Cache\n";
echo "------------------\n";
$flushed = Eligify::flushCache();
echo 'Cache flushed: '.($flushed ? 'Success' : 'Failed')."\n\n";

echo "=== Cache Demo Complete ===\n";
