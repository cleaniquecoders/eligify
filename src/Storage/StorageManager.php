<?php

declare(strict_types=1);

namespace CleaniqueCoders\Eligify\Storage;

use CleaniqueCoders\Eligify\Storage\Contracts\StorageDriver;

class StorageManager
{
    protected ?StorageDriver $resolvedDriver = null;

    /**
     * Get the configured storage driver
     */
    public function driver(?string $name = null): StorageDriver
    {
        if ($name === null && $this->resolvedDriver !== null) {
            return $this->resolvedDriver;
        }

        $name = $name ?? config('eligify.storage.driver', 'database');

        $driver = match ($name) {
            'database' => new DatabaseStorageDriver,
            'file' => new FilesystemStorageDriver(
                config('eligify.storage.file.disk', 'local'),
                config('eligify.storage.file.path', 'eligify'),
            ),
            's3' => new FilesystemStorageDriver(
                config('eligify.storage.s3.disk', 's3'),
                config('eligify.storage.s3.path', 'eligify'),
            ),
            default => throw new \InvalidArgumentException("Unknown Eligify storage driver: {$name}"),
        };

        // Wrap with cache decorator if enabled
        if (config('eligify.storage.cache.enabled', true) && $name !== 'database') {
            $driver = new CachedStorageDriver(
                $driver,
                config('eligify.storage.cache.prefix', 'eligify_storage'),
                (int) config('eligify.storage.cache.ttl', 1440) * 60,
            );
        }

        if ($name === null || $name === config('eligify.storage.driver', 'database')) {
            $this->resolvedDriver = $driver;
        }

        return $driver;
    }
}
