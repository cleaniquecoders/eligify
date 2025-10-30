<?php

namespace CleaniqueCoders\Eligify\Support;

use CleaniqueCoders\Eligify\Facades\Eligify;
use Illuminate\Support\Facades\Cache;

class Benchmark
{
    public int $iterations = 100;

    /**
     * Benchmark simple evaluation with basic rules
     */
    public function benchmarkSimpleEvaluation(): array
    {
        Eligify::criteria('benchmark_simple')
            ->addRule('age', '>=', 18)
            ->addRule('income', '>=', 3000)
            ->addRule('credit_score', '>=', 650)
            ->save();

        $data = [
            'age' => 25,
            'income' => 5000,
            'credit_score' => 720,
        ];

        return $this->measure(fn () => Eligify::evaluate('benchmark_simple', $data, false));
    }

    /**
     * Benchmark complex evaluation with multiple rules
     */
    public function benchmarkComplexEvaluation(): array
    {
        Eligify::criteria('benchmark_complex')
            ->addRule('age', '>=', 18)
            ->addRule('income', '>=', 3000)
            ->addRule('credit_score', '>=', 650)
            ->addRule('employment_years', '>=', 2)
            ->addRule('debt_ratio', '<=', 0.4)
            ->addRule('collateral_value', '>=', 10000)
            ->addRule('has_bankruptcy', '==', false)
            ->addRule('late_payments', '<=', 2)
            ->save();

        $data = [
            'age' => 25,
            'income' => 5000,
            'credit_score' => 720,
            'employment_years' => 3,
            'debt_ratio' => 0.3,
            'collateral_value' => 15000,
            'has_bankruptcy' => false,
            'late_payments' => 0,
        ];

        return $this->measure(fn () => Eligify::evaluate('benchmark_complex', $data, false));
    }

    /**
     * Benchmark batch evaluation
     */
    public function benchmarkBatchEvaluation(int $count = 100): array
    {
        Eligify::criteria('benchmark_batch')
            ->addRule('age', '>=', 18)
            ->addRule('income', '>=', 3000)
            ->save();

        $data = array_map(fn () => [
            'age' => rand(18, 65),
            'income' => rand(2000, 10000),
        ], range(1, $count));

        return $this->measure(fn () => Eligify::evaluateBatch('benchmark_batch', $data, false));
    }

    /**
     * Benchmark cache performance
     */
    public function benchmarkWithCache(): array
    {
        $cacheKey = 'eligify_benchmark_cache_test';
        Cache::forget($cacheKey);

        Eligify::criteria('benchmark_cache')
            ->addRule('age', '>=', 18)
            ->addRule('income', '>=', 3000)
            ->save();

        $data = ['age' => 25, 'income' => 5000];

        // Measure without cache
        $withoutCache = $this->measure(fn () => Eligify::evaluate('benchmark_cache', $data, false));

        // Warm up cache
        Eligify::evaluate('benchmark_cache', $data, false);

        // Measure with cache
        $withCache = $this->measure(fn () => Eligify::evaluate('benchmark_cache', $data, false));

        return [
            'without_cache' => $withoutCache,
            'with_cache' => $withCache,
            'improvement' => $withoutCache['avg'] > 0
                ? round($withoutCache['avg'] / $withCache['avg'], 2).'x'
                : 'N/A',
        ];
    }

    /**
     * Measure execution time and memory usage
     */
    protected function measure(callable $callback): array
    {
        $times = [];
        $memoryUsage = [];

        // Warm up
        try {
            $callback();
        } catch (\Exception $e) {
            return $this->errorResult($e->getMessage());
        }

        for ($i = 0; $i < $this->iterations; $i++) {
            $startMemory = memory_get_usage();
            $start = microtime(true);

            try {
                $callback();
            } catch (\Exception $e) {
                return $this->errorResult($e->getMessage());
            }

            $end = microtime(true);
            $endMemory = memory_get_usage();

            $times[] = ($end - $start) * 1000; // Convert to milliseconds
            $memoryUsage[] = ($endMemory - $startMemory) / 1024 / 1024; // Convert to MB
        }

        return [
            'iterations' => $this->iterations,
            'avg' => round(array_sum($times) / count($times), 2),
            'min' => round(min($times), 2),
            'max' => round(max($times), 2),
            'median' => round($this->median($times), 2),
            'memory_avg' => round(array_sum($memoryUsage) / count($memoryUsage), 2),
            'memory_peak' => round(max($memoryUsage), 2),
            'throughput' => round(1000 / (array_sum($times) / count($times)), 2),
        ];
    }

    /**
     * Calculate median value
     */
    protected function median(array $values): float
    {
        sort($values);
        $count = count($values);

        if ($count === 0) {
            return 0;
        }

        $middle = floor(($count - 1) / 2);

        if ($count % 2) {
            return $values[$middle];
        }

        return ($values[$middle] + $values[$middle + 1]) / 2;
    }

    /**
     * Return error result
     */
    protected function errorResult(string $message): array
    {
        return [
            'error' => true,
            'message' => $message,
            'iterations' => 0,
            'avg' => 0,
            'min' => 0,
            'max' => 0,
            'median' => 0,
            'memory_avg' => 0,
            'memory_peak' => 0,
            'throughput' => 0,
        ];
    }
}
