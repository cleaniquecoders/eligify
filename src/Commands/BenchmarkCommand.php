<?php

namespace CleaniqueCoders\Eligify\Commands;

use CleaniqueCoders\Eligify\Support\EligifyBenchmark;
use Illuminate\Console\Command;

class BenchmarkCommand extends Command
{
    public $signature = 'eligify:benchmark
                        {--iterations=100 : Number of iterations to run}
                        {--type=all : Benchmark type (simple|complex|batch|cache|all)}
                        {--format=table : Output format (table|json)}';

    public $description = 'Run performance benchmarks for Eligify';

    public function handle(): int
    {
        // Prevent running in production
        if (app()->environment('production')) {
            $this->error('❌ Benchmark command should not be run in production!');
            $this->line('💡 Use staging or local environment for benchmarking.');
            $this->newLine();
            $this->line('Tip: Set APP_ENV=staging or APP_ENV=local in your .env file');

            return self::FAILURE;
        }

        $benchmark = new EligifyBenchmark;
        $benchmark->iterations = (int) $this->option('iterations');
        $type = $this->option('type');
        $format = $this->option('format');

        $this->displayHeader($benchmark->iterations);

        $results = [];

        try {
            if ($type === 'all' || $type === 'simple') {
                $results['simple'] = $this->runTest(
                    'Simple Evaluation',
                    '3 basic rules',
                    fn () => $benchmark->benchmarkSimpleEvaluation(),
                    $format
                );
            }

            if ($type === 'all' || $type === 'complex') {
                $results['complex'] = $this->runTest(
                    'Complex Evaluation',
                    '8 rules with multiple conditions',
                    fn () => $benchmark->benchmarkComplexEvaluation(),
                    $format
                );
            }

            if ($type === 'all' || $type === 'batch') {
                $results['batch_100'] = $this->runTest(
                    'Batch Evaluation',
                    '100 items',
                    fn () => $benchmark->benchmarkBatchEvaluation(100),
                    $format
                );

                $results['batch_1000'] = $this->runTest(
                    'Batch Evaluation',
                    '1,000 items',
                    fn () => $benchmark->benchmarkBatchEvaluation(1000),
                    $format
                );
            }

            if ($type === 'all' || $type === 'cache') {
                $results['cache'] = $this->runCacheTest(
                    fn () => $benchmark->benchmarkWithCache(),
                    $format
                );
            }

            if ($format === 'json') {
                $this->line(json_encode($results, JSON_PRETTY_PRINT));
            }

            $this->displaySummary($results);

            // Cleanup benchmark data
            $this->cleanupBenchmarkData();

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Benchmark failed: '.$e->getMessage());

            // Still cleanup even on failure
            $this->cleanupBenchmarkData();

            return self::FAILURE;
        }
    }

    protected function displayHeader(int $iterations): void
    {
        $this->newLine();
        $this->info('🚀 Eligify Performance Benchmarks');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->line("📊 Iterations: <fg=cyan>{$iterations}</>");
        $this->line('⚡ Environment: '.app()->environment());
        $this->line('🐘 PHP Version: '.PHP_VERSION);
        $this->line('📦 Laravel Version: '.app()->version());
        $this->newLine();
    }

    protected function runTest(string $name, string $description, callable $test, string $format): array
    {
        $this->line("📈 <fg=yellow>Testing:</> <fg=cyan>{$name}</> - {$description}");

        $result = $test();

        if (isset($result['error']) && $result['error']) {
            $this->error("   ❌ Error: {$result['message']}");

            return $result;
        }

        if ($format === 'table') {
            $this->displayResultTable($result);
        }

        $this->displayResultSummary($result);
        $this->newLine();

        return $result;
    }

    protected function runCacheTest(callable $test, string $format): array
    {
        $this->line('📈 <fg=yellow>Testing:</> <fg=cyan>Cache Performance</> - with/without cache');

        $result = $test();

        if (isset($result['without_cache']['error']) || isset($result['with_cache']['error'])) {
            $this->error('   ❌ Cache test failed');

            return $result;
        }

        if ($format === 'table') {
            $this->table(
                ['Metric', 'Without Cache', 'With Cache', 'Improvement'],
                [
                    [
                        'Average Time',
                        $result['without_cache']['avg'].' ms',
                        $result['with_cache']['avg'].' ms',
                        $result['improvement'],
                    ],
                    [
                        'Min Time',
                        $result['without_cache']['min'].' ms',
                        $result['with_cache']['min'].' ms',
                        '-',
                    ],
                    [
                        'Max Time',
                        $result['without_cache']['max'].' ms',
                        $result['with_cache']['max'].' ms',
                        '-',
                    ],
                    [
                        'Throughput',
                        $result['without_cache']['throughput'].' req/s',
                        $result['with_cache']['throughput'].' req/s',
                        '-',
                    ],
                ]
            );
        }

        $this->line("   ⚡ Cache improvement: <fg=green>{$result['improvement']}</> faster");
        $this->newLine();

        return $result;
    }

    protected function displayResultTable(array $result): void
    {
        $this->table(
            ['Metric', 'Value'],
            [
                ['Average Time', $result['avg'].' ms'],
                ['Min Time', $result['min'].' ms'],
                ['Max Time', $result['max'].' ms'],
                ['Median Time', $result['median'].' ms'],
                ['Throughput', $result['throughput'].' req/s'],
                ['Avg Memory', $result['memory_avg'].' MB'],
                ['Peak Memory', $result['memory_peak'].' MB'],
                ['Iterations', $result['iterations']],
            ]
        );
    }

    protected function displayResultSummary(array $result): void
    {
        $avgColor = $result['avg'] < 50 ? 'green' : ($result['avg'] < 100 ? 'yellow' : 'red');
        $throughputColor = $result['throughput'] > 50 ? 'green' : ($result['throughput'] > 20 ? 'yellow' : 'red');

        $this->line("   ⏱️  Average: <fg={$avgColor}>{$result['avg']} ms</>");
        $this->line("   ⚡ Throughput: <fg={$throughputColor}>{$result['throughput']} req/s</>");
        $this->line("   💾 Memory: {$result['memory_avg']} MB (peak: {$result['memory_peak']} MB)");
    }

    protected function displaySummary(array $results): void
    {
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('✅ Benchmark Summary');
        $this->newLine();

        $totalTests = count($results);
        $this->line("   📊 Total tests run: <fg=cyan>{$totalTests}</>");

        // Calculate average performance
        $allAvg = array_filter(
            array_map(fn ($r) => is_array($r) && isset($r['avg']) ? $r['avg'] : null, $results)
        );

        if (! empty($allAvg)) {
            $overallAvg = round(array_sum($allAvg) / count($allAvg), 2);
            $this->line("   ⏱️  Overall average: <fg=cyan>{$overallAvg} ms</>");
        }

        $this->newLine();
        $this->line('💡 <fg=yellow>Tip:</> Run with --iterations=1000 for more accurate results');
        $this->line('📖 <fg=yellow>Docs:</> See docs/performance-benchmarking.md for optimization tips');
        $this->newLine();
    }

    protected function cleanupBenchmarkData(): void
    {
        try {
            $deleted = \CleaniqueCoders\Eligify\Models\Criteria::whereIn('slug', [
                'benchmark_simple',
                'benchmark_complex',
                'benchmark_batch',
                'benchmark_cache',
            ])->delete();

            if ($deleted > 0) {
                $this->line("🧹 Cleaned up {$deleted} benchmark criteria");
            }
        } catch (\Exception $e) {
            // Silent fail - cleanup is not critical
            $this->comment('⚠️  Note: Could not cleanup benchmark data');
        }
    }
}
