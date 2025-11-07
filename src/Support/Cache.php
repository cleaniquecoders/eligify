<?php

declare(strict_types=1);

namespace CleaniqueCoders\Eligify\Support;

use CleaniqueCoders\Eligify\Data\Snapshot;
use CleaniqueCoders\Eligify\Models\Criteria;
use Illuminate\Support\Facades\Cache as CacheFacade;

class Cache
{
    protected array $config;

    public function __construct()
    {
        $this->config = config('eligify');
    }

    /**
     * Check if evaluation caching is enabled
     */
    public function isEvaluationCacheEnabled(): bool
    {
        // Read config dynamically to allow runtime changes
        return (bool) config('eligify.evaluation.cache_enabled', true);
    }

    /**
     * Check if rule compilation caching is enabled
     */
    public function isCompilationCacheEnabled(): bool
    {
        // Read config dynamically to allow runtime changes
        return (bool) config('eligify.performance.compile_rules', true);
    }

    /**
     * Get cache TTL for evaluations in seconds
     */
    public function getEvaluationCacheTtl(): int
    {
        // Read config dynamically
        return (int) config('eligify.evaluation.cache_ttl', 60) * 60;
    }

    /**
     * Get cache TTL for rule compilation in seconds
     */
    public function getCompilationCacheTtl(): int
    {
        // Read config dynamically
        return (int) config('eligify.performance.compilation_cache_ttl', 1440) * 60;
    }

    /**
     * Generate cache key for evaluation
     */
    public function getEvaluationCacheKey(Criteria $criteria, array|Snapshot $data): string
    {
        $prefix = config('eligify.evaluation.cache_prefix', 'eligify_eval');
        $dataArray = $data instanceof Snapshot ? $data->toArray() : $data;

        // Create deterministic hash based on criteria ID and data
        $dataHash = md5(json_encode($dataArray, JSON_THROW_ON_ERROR));

        return "{$prefix}:{$criteria->uuid}:{$dataHash}";
    }

    /**
     * Generate cache key for rule compilation
     */
    public function getCompilationCacheKey(Criteria $criteria): string
    {
        $prefix = config('eligify.evaluation.cache_prefix', 'eligify_eval');

        return "{$prefix}:{$criteria->uuid}";
    }

    /**
     * Generate cache tag for criteria
     */
    public function getCriteriaTag(Criteria $criteria): string
    {
        return "eligify_criteria_{$criteria->id}";
    }

    /**
     * Get cached evaluation result or execute callback
     */
    public function rememberEvaluation(Criteria $criteria, array|Snapshot $data, callable $callback): array
    {
        if (! $this->isEvaluationCacheEnabled()) {
            return $callback();
        }

        $key = $this->getEvaluationCacheKey($criteria, $data);
        $ttl = $this->getEvaluationCacheTtl();

        // Use cache tags if supported (Redis, Memcached)
        if ($this->supportsTags()) {
            return CacheFacade::tags(['eligify_evaluations', $this->getCriteriaTag($criteria)])
                ->remember($key, $ttl, $callback);
        }

        return CacheFacade::remember($key, $ttl, $callback);
    }

    /**
     * Get cached compiled rules or execute callback
     */
    public function rememberCompilation(Criteria $criteria, callable $callback): mixed
    {
        if (! $this->isCompilationCacheEnabled()) {
            return $callback();
        }

        $key = $this->getCompilationCacheKey($criteria);
        $ttl = $this->getCompilationCacheTtl();

        // Use cache tags if supported
        if ($this->supportsTags()) {
            return CacheFacade::tags(['eligify_compilations', $this->getCriteriaTag($criteria)])
                ->remember($key, $ttl, $callback);
        }

        // Use remember for array driver (no tags)
        $result = CacheFacade::remember($key, $ttl, $callback);

        // Workaround: Laravel's array cache driver sometimes doesn't properly store
        // Eloquent Collections with Cache::remember(). Verify and manually store if needed.
        // This ensures compilation caching works reliably across all cache drivers.
        if (! CacheFacade::has($key)) {
            CacheFacade::put($key, $result, $ttl);
        }

        return $result;
    }

    /**
     * Invalidate all evaluation cache for a criteria
     */
    public function invalidateCriteriaEvaluations(Criteria $criteria): bool
    {
        if ($this->supportsTags()) {
            return CacheFacade::tags([$this->getCriteriaTag($criteria)])->flush();
        }

        // Fallback: Clear all eligify caches if tags not supported
        return $this->flushAllEligifyCache();
    }

    /**
     * Invalidate specific evaluation cache
     */
    public function invalidateEvaluation(Criteria $criteria, array|Snapshot $data): bool
    {
        $key = $this->getEvaluationCacheKey($criteria, $data);

        return CacheFacade::forget($key);
    }

    /**
     * Invalidate compilation cache for a criteria
     */
    public function invalidateCompilation(Criteria $criteria): bool
    {
        $key = $this->getCompilationCacheKey($criteria);

        return CacheFacade::forget($key);
    }

    /**
     * Flush all evaluation caches
     */
    public function flushEvaluationCache(): bool
    {
        if ($this->supportsTags()) {
            return CacheFacade::tags(['eligify_evaluations'])->flush();
        }

        return $this->flushAllEligifyCache();
    }

    /**
     * Flush all compilation caches
     */
    public function flushCompilationCache(): bool
    {
        if ($this->supportsTags()) {
            return CacheFacade::tags(['eligify_compilations'])->flush();
        }

        return $this->flushAllEligifyCache();
    }

    /**
     * Flush all Eligify-related caches
     */
    public function flushAllEligifyCache(): bool
    {
        // If tags are supported, flush all tags
        if ($this->supportsTags()) {
            $flushed = true;
            $flushed = CacheFacade::tags(['eligify_evaluations'])->flush() && $flushed;
            $flushed = CacheFacade::tags(['eligify_compilations'])->flush() && $flushed;

            return $flushed;
        }

        // Fallback: Clear by prefix pattern (only works with some drivers)
        $driver = config('cache.default');

        if ($driver === 'redis') {
            return $this->flushRedisByPattern('eligify_*');
        }

        // Last resort: flush entire cache (not recommended for production)
        return CacheFacade::flush();
    }

    /**
     * Warm up cache for a criteria with sample data
     */
    public function warmupCriteria(Criteria $criteria, array $sampleDataSets = []): int
    {
        if (empty($sampleDataSets)) {
            return 0;
        }

        $warmedUp = 0;

        foreach ($sampleDataSets as $data) {
            try {
                // This will be called from Eligify facade/service
                // For now, just mark the intention
                $key = $this->getEvaluationCacheKey($criteria, $data);

                // The actual evaluation will be performed by the calling code
                $warmedUp++;
            } catch (\Throwable $e) {
                logger()->warning('Failed to warm up cache for criteria', [
                    'criteria_id' => $criteria->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $warmedUp;
    }

    /**
     * Get cache statistics
     */
    public function getStatistics(): array
    {
        $driver = config('cache.default');

        return [
            'driver' => $driver,
            'supports_tags' => $this->supportsTags(),
            'evaluation_cache_enabled' => $this->isEvaluationCacheEnabled(),
            'compilation_cache_enabled' => $this->isCompilationCacheEnabled(),
            'evaluation_ttl_seconds' => $this->getEvaluationCacheTtl(),
            'compilation_ttl_seconds' => $this->getCompilationCacheTtl(),
            'cache_prefix' => $this->config['evaluation']['cache_prefix'] ?? 'eligify_eval',
        ];
    }

    /**
     * Check if the current cache driver supports tags
     */
    protected function supportsTags(): bool
    {
        $driver = config('cache.default');

        return in_array($driver, ['redis', 'memcached', 'dynamodb', 'octane']);
    }

    /**
     * Flush Redis cache by pattern
     */
    protected function flushRedisByPattern(string $pattern): bool
    {
        try {
            /** @var \Illuminate\Cache\RedisStore $store */
            $store = CacheFacade::getStore();
            $redis = $store->connection();
            $keys = $redis->keys($pattern);

            if (! empty($keys)) {
                $redis->del($keys);
            }

            return true;
        } catch (\Throwable $e) {
            logger()->error('Failed to flush Redis cache by pattern', [
                'pattern' => $pattern,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Check if a specific evaluation is cached
     */
    public function hasEvaluation(Criteria $criteria, array|Snapshot $data): bool
    {
        $key = $this->getEvaluationCacheKey($criteria, $data);

        // Check with tags if supported
        if ($this->supportsTags()) {
            return CacheFacade::tags(['eligify_evaluations', $this->getCriteriaTag($criteria)])
                ->has($key);
        }

        return CacheFacade::has($key);
    }

    /**
     * Check if compilation for criteria is cached
     */
    public function hasCompilation(Criteria $criteria): bool
    {
        $key = $this->getCompilationCacheKey($criteria);

        // Check with tags if supported
        if ($this->supportsTags()) {
            return CacheFacade::tags(['eligify_compilations', $this->getCriteriaTag($criteria)])
                ->has($key);
        }

        return CacheFacade::has($key);
    }

    /**
     * Get remaining TTL for an evaluation cache
     */
    public function getEvaluationTtl(Criteria $criteria, array|Snapshot $data): ?int
    {
        $key = $this->getEvaluationCacheKey($criteria, $data);

        // Note: Not all cache drivers support retrieving TTL
        try {
            /** @var \Illuminate\Cache\RedisStore $store */
            $store = CacheFacade::getStore();
            $redis = $store->connection();
            $ttl = $redis->ttl($key);

            return $ttl > 0 ? $ttl : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
