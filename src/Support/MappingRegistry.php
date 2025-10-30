<?php

namespace CleaniqueCoders\Eligify\Support;

use CleaniqueCoders\Eligify\Contracts\ModelMapping;
use Illuminate\Support\Facades\File;

/**
 * Registry for discovering and managing model mappings
 *
 * Provides auto-discovery of mapping classes and convenient access to their metadata
 */
class MappingRegistry
{
    /**
     * Cache of discovered mappings
     */
    protected static ?array $mappings = null;

    /**
     * Get all available mapping classes with metadata
     *
     * @return array Format: ['class' => ['name' => '...', 'description' => '...', 'model' => '...']]
     */
    public static function all(): array
    {
        if (static::$mappings === null) {
            static::$mappings = static::discover();
        }

        return static::$mappings;
    }

    /**
     * Get a specific mapping instance
     */
    public static function get(string $class): ?ModelMapping
    {
        if (! class_exists($class)) {
            return null;
        }

        $instance = new $class;

        return $instance instanceof ModelMapping ? $instance : null;
    }

    /**
     * Get all fields for a specific mapping class
     *
     * @return array Format: ['field_name' => ['type' => '...', 'description' => '...', 'category' => '...']]
     */
    public static function getFields(string $class): array
    {
        $mapping = static::get($class);

        return $mapping ? $mapping->getAvailableFields() : [];
    }

    /**
     * Get mapping metadata
     */
    public static function getMeta(string $class): array
    {
        $mapping = static::get($class);

        if (! $mapping) {
            return [];
        }

        return [
            'class' => $class,
            'name' => $mapping->getName(),
            'description' => $mapping->getDescription(),
            'model' => $mapping->getModelClass(),
            'fields_count' => count($mapping->getAvailableFields()),
        ];
    }

    /**
     * Discover all available mappings
     */
    protected static function discover(): array
    {
        $mappings = [];

        // 1. Get mappings from config
        $configMappings = config('eligify.model_mappings', []);
        foreach ($configMappings as $model => $mappingClass) {
            if (class_exists($mappingClass)) {
                $mapping = new $mappingClass;
                if ($mapping instanceof ModelMapping) {
                    $mappings[$mappingClass] = [
                        'class' => $mappingClass,
                        'name' => $mapping->getName(),
                        'description' => $mapping->getDescription(),
                        'model' => $mapping->getModelClass(),
                    ];
                }
            }
        }

        // 2. Auto-discover from package Mappings directory
        $packageMappingsPath = __DIR__.'/../Mappings';
        if (File::exists($packageMappingsPath)) {
            $files = File::files($packageMappingsPath);
            foreach ($files as $file) {
                $filename = $file->getFilename();
                if ($filename === 'AbstractModelMapping.php') {
                    continue;
                }

                $class = 'CleaniqueCoders\\Eligify\\Mappings\\'.pathinfo($filename, PATHINFO_FILENAME);
                if (class_exists($class) && ! isset($mappings[$class])) {
                    try {
                        $mapping = new $class;
                        if ($mapping instanceof ModelMapping) {
                            $mappings[$class] = [
                                'class' => $class,
                                'name' => $mapping->getName(),
                                'description' => $mapping->getDescription(),
                                'model' => $mapping->getModelClass(),
                            ];
                        }
                    } catch (\Exception $e) {
                        // Skip mappings that can't be instantiated
                        continue;
                    }
                }
            }
        }

        // 3. Auto-discover from app Mappings directory
        $appMappingsPath = app_path('Mappings');
        if (File::exists($appMappingsPath)) {
            $files = File::files($appMappingsPath);
            foreach ($files as $file) {
                $filename = $file->getFilename();
                $class = 'App\\Mappings\\'.pathinfo($filename, PATHINFO_FILENAME);
                if (class_exists($class) && ! isset($mappings[$class])) {
                    try {
                        $mapping = new $class;
                        if ($mapping instanceof ModelMapping) {
                            $mappings[$class] = [
                                'class' => $class,
                                'name' => $mapping->getName(),
                                'description' => $mapping->getDescription(),
                                'model' => $mapping->getModelClass(),
                            ];
                        }
                    } catch (\Exception $e) {
                        // Skip mappings that can't be instantiated
                        continue;
                    }
                }
            }
        }

        // Sort by name
        uasort($mappings, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        return $mappings;
    }

    /**
     * Clear the mappings cache
     */
    public static function clearCache(): void
    {
        static::$mappings = null;
    }

    /**
     * Check if a mapping class is registered
     */
    public static function has(string $class): bool
    {
        return isset(static::all()[$class]);
    }

    /**
     * Get all mapping classes (just the class names)
     */
    public static function classes(): array
    {
        return array_keys(static::all());
    }

    /**
     * Get mappings grouped by model class
     *
     * @return array Format: ['App\Models\User' => [...mappings]]
     */
    public static function byModel(): array
    {
        $grouped = [];

        foreach (static::all() as $class => $meta) {
            $model = $meta['model'];
            if (! isset($grouped[$model])) {
                $grouped[$model] = [];
            }
            $grouped[$model][] = $meta;
        }

        return $grouped;
    }
}
