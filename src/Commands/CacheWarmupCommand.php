<?php

namespace CleaniqueCoders\Eligify\Commands;

use CleaniqueCoders\Eligify\Eligify;
use CleaniqueCoders\Eligify\Models\Criteria;
use CleaniqueCoders\Eligify\Support\Cache;
use Illuminate\Console\Command;

class CacheWarmupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eligify:cache:warmup
                            {criteria? : Specific criteria slug to warm up}
                            {--all : Warm up all active criteria}
                            {--samples=10 : Number of sample data sets to generate per criteria}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Warm up Eligify evaluation cache with sample data';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $cache = new Cache;

        // Check if caching is enabled
        if (! $cache->isEvaluationCacheEnabled()) {
            $this->warn('Eligify evaluation caching is disabled in configuration.');

            return self::FAILURE;
        }

        $criteriaSlug = $this->argument('criteria');
        $warmupAll = $this->option('all');
        $samplesCount = (int) $this->option('samples');

        try {
            if ($warmupAll) {
                return $this->warmupAllCriteria($samplesCount);
            }

            if ($criteriaSlug) {
                return $this->warmupSpecificCriteria($criteriaSlug, $samplesCount);
            }

            $this->error('Please specify a criteria slug or use --all flag.');

            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->components->error('Error warming up cache: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Warm up cache for all active criteria
     */
    protected function warmupAllCriteria(int $samplesCount): int
    {
        $criteria = Criteria::where('is_active', true)->get();

        if ($criteria->isEmpty()) {
            $this->warn('No active criteria found.');

            return self::SUCCESS;
        }

        $this->info("Warming up cache for {$criteria->count()} active criteria...");

        $bar = $this->output->createProgressBar($criteria->count());
        $bar->start();

        $totalWarmedUp = 0;

        foreach ($criteria as $criteriaModel) {
            $warmedUp = $this->warmupCriteria($criteriaModel, $samplesCount);
            $totalWarmedUp += $warmedUp;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->components->success("Successfully warmed up cache with {$totalWarmedUp} evaluations.");

        return self::SUCCESS;
    }

    /**
     * Warm up cache for a specific criteria
     */
    protected function warmupSpecificCriteria(string $slug, int $samplesCount): int
    {
        $criteria = Criteria::where('slug', $slug)->first();

        if (! $criteria) {
            $this->error("Criteria '{$slug}' not found.");

            return self::FAILURE;
        }

        $this->info("Warming up cache for criteria: {$criteria->name}");

        $warmedUp = $this->warmupCriteria($criteria, $samplesCount);

        $this->components->success("Successfully warmed up cache with {$warmedUp} evaluations.");

        return self::SUCCESS;
    }

    /**
     * Warm up cache for a criteria with sample data
     */
    protected function warmupCriteria(Criteria $criteria, int $samplesCount): int
    {
        $eligify = app(Eligify::class);

        // Generate sample data based on criteria rules
        $sampleDataSets = $this->generateSampleData($criteria, $samplesCount);

        // Warm up cache
        return $eligify->warmupCache($criteria, $sampleDataSets);
    }

    /**
     * Generate sample data for a criteria
     */
    protected function generateSampleData(Criteria $criteria, int $count): array
    {
        $rules = $criteria->rules()->where('is_active', true)->get();
        $sampleDataSets = [];

        for ($i = 0; $i < $count; $i++) {
            $data = [];

            foreach ($rules as $rule) {
                $field = $rule->getAttribute('field');
                $value = $rule->getAttribute('value');

                // Generate sample value based on rule type
                $data[$field] = $this->generateSampleValue($value, $i);
            }

            $sampleDataSets[] = $data;
        }

        return $sampleDataSets;
    }

    /**
     * Generate a sample value for a field
     */
    protected function generateSampleValue(mixed $expectedValue, int $index): mixed
    {
        // If expected value is numeric, generate variations
        if (is_numeric($expectedValue)) {
            $base = (float) $expectedValue;
            $variation = ($index % 2 === 0) ? 1.1 : 0.9; // 10% variation

            return $base * $variation;
        }

        // If expected value is boolean
        if (is_bool($expectedValue)) {
            return $index % 2 === 0;
        }

        // If expected value is string
        if (is_string($expectedValue)) {
            return $expectedValue.'_'.$index;
        }

        // If expected value is array
        if (is_array($expectedValue)) {
            return $expectedValue;
        }

        return $expectedValue;
    }
}
