<?php

namespace CleaniqueCoders\Eligify\Commands;

use CleaniqueCoders\Eligify\Support\Cache;
use Illuminate\Console\Command;

class CacheClearCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eligify:cache:clear
                            {--type=all : Type of cache to clear (all|evaluation|compilation)}
                            {--force : Force cache clearing without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear Eligify evaluation and compilation caches';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $cache = new Cache;
        $type = $this->option('type');
        $force = $this->option('force');

        // Check if caching is enabled
        if (! $cache->isEvaluationCacheEnabled() && ! $cache->isCompilationCacheEnabled()) {
            $this->warn('Eligify caching is disabled in configuration.');

            return self::FAILURE;
        }

        // Confirmation unless force flag is used
        if (! $force && ! $this->confirm('Are you sure you want to clear Eligify cache?')) {
            $this->info('Cache clear operation cancelled.');

            return self::SUCCESS;
        }

        $this->info('Clearing Eligify cache...');

        try {
            $result = match ($type) {
                'evaluation' => $this->clearEvaluationCache($cache),
                'compilation' => $this->clearCompilationCache($cache),
                'all' => $this->clearAllCache($cache),
                default => $this->clearAllCache($cache),
            };

            if ($result) {
                $this->components->success("Successfully cleared {$type} cache.");

                return self::SUCCESS;
            }

            $this->components->error("Failed to clear {$type} cache.");

            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->components->error('Error clearing cache: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Clear evaluation cache
     */
    protected function clearEvaluationCache(Cache $cache): bool
    {
        $this->info('Clearing evaluation cache...');

        return $cache->flushEvaluationCache();
    }

    /**
     * Clear compilation cache
     */
    protected function clearCompilationCache(Cache $cache): bool
    {
        $this->info('Clearing compilation cache...');

        return $cache->flushCompilationCache();
    }

    /**
     * Clear all caches
     */
    protected function clearAllCache(Cache $cache): bool
    {
        $this->info('Clearing all Eligify caches...');

        return $cache->flushAllEligifyCache();
    }
}
