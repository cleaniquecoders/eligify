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
        $isDefaultDriver = $name === null;

        if ($isDefaultDriver && $this->resolvedDriver !== null) {
            return $this->resolvedDriver;
        }

        /** @var string $driverName */
        $driverName = $name ?? config('eligify.storage.driver', 'database');

        $driver = match ($driverName) {
            'database' => new DatabaseStorageDriver,
            'file' => new FilesystemStorageDriver(
                (string) config('eligify.storage.file.disk', 'local'),
                (string) config('eligify.storage.file.path', 'eligify'),
            ),
            's3' => new FilesystemStorageDriver(
                (string) config('eligify.storage.s3.disk', 's3'),
                (string) config('eligify.storage.s3.path', 'eligify'),
            ),
            default => throw new \InvalidArgumentException("Unknown Eligify storage driver: {$driverName}"),
        };

        // Wrap with cache decorator if enabled (non-database drivers only)
        if (config('eligify.storage.cache.enabled', true) && $driverName !== 'database') {
            $driver = new CachedStorageDriver(
                $driver,
                (string) config('eligify.storage.cache.prefix', 'eligify_storage'),
                (int) config('eligify.storage.cache.ttl', 1440) * 60,
            );
        }

        if ($isDefaultDriver) {
            $this->resolvedDriver = $driver;
        }

        return $driver;
    }
}
