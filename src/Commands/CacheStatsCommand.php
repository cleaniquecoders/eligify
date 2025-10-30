<?php

namespace CleaniqueCoders\Eligify\Commands;

use CleaniqueCoders\Eligify\Support\EligifyCache;
use Illuminate\Console\Command;

class CacheStatsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eligify:cache:stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display Eligify cache statistics and configuration';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $cache = new EligifyCache;

        $this->info('Eligify Cache Statistics');
        $this->newLine();

        // Get statistics
        $stats = $cache->getStatistics();

        // Display configuration
        $this->components->info('Configuration');
        $this->table(
            ['Setting', 'Value'],
            [
                ['Cache Driver', $stats['driver']],
                ['Supports Tags', $stats['supports_tags'] ? '✓ Yes' : '✗ No'],
                ['Evaluation Cache', $stats['evaluation_cache_enabled'] ? '✓ Enabled' : '✗ Disabled'],
                ['Compilation Cache', $stats['compilation_cache_enabled'] ? '✓ Enabled' : '✗ Disabled'],
                ['Evaluation TTL', $this->formatTtl($stats['evaluation_ttl_seconds'])],
                ['Compilation TTL', $this->formatTtl($stats['compilation_ttl_seconds'])],
                ['Cache Prefix', $stats['cache_prefix']],
            ]
        );

        $this->newLine();

        // Display recommendations
        $this->displayRecommendations($stats);

        return self::SUCCESS;
    }

    /**
     * Format TTL in human-readable format
     */
    protected function formatTtl(int $seconds): string
    {
        $minutes = $seconds / 60;
        $hours = $minutes / 60;

        if ($hours >= 1) {
            return round($hours, 2).' hours';
        }

        if ($minutes >= 1) {
            return round($minutes, 2).' minutes';
        }

        return $seconds.' seconds';
    }

    /**
     * Display recommendations based on current configuration
     */
    protected function displayRecommendations(array $stats): void
    {
        $this->components->info('Recommendations');

        $recommendations = [];

        // Check cache driver
        if (! in_array($stats['driver'], ['redis', 'memcached'])) {
            $recommendations[] = [
                'warning',
                'Consider using Redis or Memcached for better performance',
            ];
        }

        // Check if tags are supported
        if (! $stats['supports_tags']) {
            $recommendations[] = [
                'warning',
                'Cache driver does not support tags. Cache invalidation will be less efficient.',
            ];
        }

        // Check if caching is disabled
        if (! $stats['evaluation_cache_enabled'] && ! $stats['compilation_cache_enabled']) {
            $recommendations[] = [
                'error',
                'Both evaluation and compilation caching are disabled. Enable caching for better performance.',
            ];
        }

        // Check TTL values
        if ($stats['evaluation_ttl_seconds'] < 300) {
            $recommendations[] = [
                'warning',
                'Evaluation cache TTL is very short (< 5 minutes). Consider increasing it.',
            ];
        }

        if (empty($recommendations)) {
            $this->components->success('Configuration looks good! No recommendations at this time.');
        } else {
            foreach ($recommendations as [$level, $message]) {
                if ($level === 'warning') {
                    $this->components->warn($message);
                } elseif ($level === 'error') {
                    $this->components->error($message);
                } else {
                    $this->components->info($message);
                }
            }
        }

        $this->newLine();

        // Display available commands
        $this->components->info('Available Commands');
        $this->line('  • eligify:cache:clear      - Clear evaluation and compilation caches');
        $this->line('  • eligify:cache:warmup     - Warm up cache with sample data');
        $this->line('  • eligify:cache:stats      - Display cache statistics (current command)');
    }
}
