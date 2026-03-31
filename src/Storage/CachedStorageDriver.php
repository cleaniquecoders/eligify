<?php

declare(strict_types=1);

namespace CleaniqueCoders\Eligify\Storage;

use CleaniqueCoders\Eligify\Models\Criteria;
use CleaniqueCoders\Eligify\Storage\Contracts\StorageDriver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CachedStorageDriver implements StorageDriver
{
    public function __construct(
        protected StorageDriver $inner,
        protected string $prefix = 'eligify_storage',
        protected int $ttlSeconds = 86400
    ) {}

    public function findCriteriaBySlug(string $slug): ?Criteria
    {
        $slug = str($slug)->slug()->toString();

        return Cache::remember(
            "{$this->prefix}:criteria:{$slug}",
            $this->ttlSeconds,
            fn () => $this->inner->findCriteriaBySlug($slug)
        );
    }

    public function findCriteria(string $identifier): ?Criteria
    {
        return Cache::remember(
            "{$this->prefix}:find:{$identifier}",
            $this->ttlSeconds,
            fn () => $this->inner->findCriteria($identifier)
        );
    }

    public function getAllActiveCriteria(): Collection
    {
        return Cache::remember(
            "{$this->prefix}:all_active",
            $this->ttlSeconds,
            fn () => $this->inner->getAllActiveCriteria()
        );
    }

    public function storeCriteria(array $data): Criteria
    {
        $result = $this->inner->storeCriteria($data);

        $slug = $data['slug'] ?? str($data['name'])->slug()->toString();
        $this->bustCriteriaCache($slug);

        return $result;
    }

    public function storeRule(Criteria $criteria, array $ruleData): void
    {
        $this->inner->storeRule($criteria, $ruleData);

        $this->bustCriteriaCache($criteria->getAttribute('slug'));
    }

    public function deleteCriteria(string $identifier): bool
    {
        $result = $this->inner->deleteCriteria($identifier);

        $slug = str($identifier)->slug()->toString();
        $this->bustCriteriaCache($slug);

        return $result;
    }

    public function storeGroup(Criteria $criteria, array $groupData, array $rules = []): mixed
    {
        $result = $this->inner->storeGroup($criteria, $groupData, $rules);

        $this->bustCriteriaCache($criteria->getAttribute('slug'));

        return $result;
    }

    public function exportCriteria(string $slug): ?array
    {
        return $this->inner->exportCriteria($slug);
    }

    public function importCriteria(array $data): Criteria
    {
        $result = $this->inner->importCriteria($data);

        $slug = $data['slug'] ?? str($data['name'])->slug()->toString();
        $this->bustCriteriaCache($slug);

        return $result;
    }

    /**
     * Bust all cache entries related to a criteria
     */
    protected function bustCriteriaCache(string $slug): void
    {
        Cache::forget("{$this->prefix}:criteria:{$slug}");
        Cache::forget("{$this->prefix}:find:{$slug}");
        Cache::forget("{$this->prefix}:all_active");
    }

    /**
     * Flush all storage cache
     */
    public function flush(): bool
    {
        // Best-effort flush using pattern - not all drivers support this
        $driver = config('cache.default');

        if (in_array($driver, ['redis', 'memcached', 'dynamodb', 'octane'])) {
            try {
                return Cache::tags([$this->prefix])->flush();
            } catch (\Throwable) {
                // Tags not supported by this driver
            }
        }

        return true;
    }
}
