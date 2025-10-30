<?php

namespace CleaniqueCoders\Eligify\Data\Mappings;

use CleaniqueCoders\Eligify\Data\Contracts\ModelMapping;
use CleaniqueCoders\Eligify\Data\Extractor;

/**
 * Abstract base class for model mappings
 *
 * Provides common functionality and helper methods for mapping classes
 */
abstract class AbstractModelMapping implements ModelMapping
{
    /**
     * Field mappings: original_field => mapped_field
     */
    protected array $fieldMappings = [];

    /**
     * Relationship mappings
     */
    protected array $relationshipMappings = [];

    /**
     * Computed fields as closures
     */
    protected array $computedFields = [];

    /**
     * Field descriptions for UI display
     */
    protected array $fieldDescriptions = [];

    /**
     * Field types for UI and validation
     */
    protected array $fieldTypes = [];

    /**
     * Prefix for this mapping (e.g., "applicant", "user")
     */
    protected ?string $prefix = null;

    /**
     * Configure the extractor with all mappings
     */
    public function configure(Extractor $extractor): Extractor
    {
        if (! empty($this->fieldMappings)) {
            $extractor->setFieldMappings($this->fieldMappings);
        }

        if (! empty($this->relationshipMappings)) {
            $extractor->setRelationshipMappings($this->relationshipMappings);
        }

        if (! empty($this->computedFields)) {
            $extractor->setComputedFields($this->computedFields);
        }

        return $extractor;
    }

    /**
     * Helper: Safely check relationship with scope
     */
    protected function safeRelationshipCheck($model, string $relationship, string $scope): bool
    {
        try {
            if (! method_exists($model, $relationship)) {
                return false;
            }

            $relation = $model->{$relationship}();

            if (method_exists($relation, $scope)) {
                return $relation->{$scope}()->exists();
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Helper: Safely count relationship
     */
    protected function safeRelationshipCount($model, string $relationship): int
    {
        try {
            if (! method_exists($model, $relationship)) {
                return 0;
            }

            return $model->{$relationship}()->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Helper: Safely sum relationship field
     */
    protected function safeRelationshipSum($model, string $relationship, string $field): float
    {
        try {
            if (! method_exists($model, $relationship)) {
                return 0.0;
            }

            return (float) $model->{$relationship}()->sum($field);
        } catch (\Exception $e) {
            return 0.0;
        }
    }

    /**
     * Helper: Safely get relationship average
     */
    protected function safeRelationshipAvg($model, string $relationship, string $field): float
    {
        try {
            if (! method_exists($model, $relationship)) {
                return 0.0;
            }

            return (float) $model->{$relationship}()->avg($field);
        } catch (\Exception $e) {
            return 0.0;
        }
    }

    /**
     * Helper: Safely get relationship max
     */
    protected function safeRelationshipMax($model, string $relationship, string $field)
    {
        try {
            if (! method_exists($model, $relationship)) {
                return null;
            }

            return $model->{$relationship}()->max($field);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Helper: Safely get relationship min
     */
    protected function safeRelationshipMin($model, string $relationship, string $field)
    {
        try {
            if (! method_exists($model, $relationship)) {
                return null;
            }

            return $model->{$relationship}()->min($field);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Helper: Check if relationship exists
     */
    protected function hasRelationship($model, string $relationship): bool
    {
        return method_exists($model, $relationship);
    }

    /**
     * Helper: Get relationship data safely
     */
    protected function getRelationshipData($model, string $relationship, $default = null)
    {
        try {
            if (! method_exists($model, $relationship)) {
                return $default;
            }

            return $model->{$relationship};
        } catch (\Exception $e) {
            return $default;
        }
    }

    /**
     * Get human-readable name for this mapping
     */
    abstract public function getName(): string;

    /**
     * Get description of what this mapping does
     */
    abstract public function getDescription(): string;

    /**
     * Get all available fields with their metadata
     *
     * @return array Format: ['field_name' => ['type' => 'string', 'description' => '...', 'category' => 'attribute|relationship|computed']]
     */
    public function getAvailableFields(): array
    {
        $fields = [];

        // Add model attributes (from field mappings)
        foreach ($this->fieldMappings as $original => $mapped) {
            $fields[$mapped] = [
                'original' => $original,
                'type' => $this->fieldTypes[$mapped] ?? $this->fieldTypes[$original] ?? 'string',
                'description' => $this->fieldDescriptions[$mapped] ?? $this->fieldDescriptions[$original] ?? "Field: {$mapped}",
                'category' => 'attribute',
            ];
        }

        // Add computed fields
        foreach ($this->computedFields as $field => $closure) {
            if (! isset($fields[$field])) {
                $fields[$field] = [
                    'type' => $this->fieldTypes[$field] ?? 'mixed',
                    'description' => $this->fieldDescriptions[$field] ?? "Computed: {$field}",
                    'category' => 'computed',
                ];
            }
        }

        // Add relationship fields
        foreach ($this->relationshipMappings as $relationship => $config) {
            if (is_array($config)) {
                foreach ($config as $key => $value) {
                    $fieldName = "{$relationship}_{$key}";
                    if (! isset($fields[$fieldName])) {
                        $fields[$fieldName] = [
                            'type' => $this->fieldTypes[$fieldName] ?? 'mixed',
                            'description' => $this->fieldDescriptions[$fieldName] ?? "Relationship: {$fieldName}",
                            'category' => 'relationship',
                        ];
                    }
                }
            }
        }

        // Sort by category then name
        uasort($fields, function ($a, $b) {
            $categoryOrder = ['attribute' => 1, 'computed' => 2, 'relationship' => 3];
            $catCompare = ($categoryOrder[$a['category']] ?? 4) <=> ($categoryOrder[$b['category']] ?? 4);

            return $catCompare !== 0 ? $catCompare : 0;
        });

        return $fields;
    }

    /**
     * Get field type for a specific field
     */
    public function getFieldType(string $field): ?string
    {
        return $this->fieldTypes[$field] ?? null;
    }

    /**
     * Get field description for a specific field
     */
    public function getFieldDescription(string $field): ?string
    {
        return $this->fieldDescriptions[$field] ?? null;
    }

    /**
     * Get the prefix for this mapping
     *
     * Auto-generates from model class name if not explicitly set
     */
    public function getPrefix(): string
    {
        if ($this->prefix !== null) {
            return $this->prefix;
        }

        // Auto-generate from model class name
        $modelClass = $this->getModelClass();
        $modelName = class_basename($modelClass);

        return \Illuminate\Support\Str::snake($modelName, '.');
    }

    /**
     * Get field mappings array
     */
    public function getFieldMappings(): array
    {
        return $this->fieldMappings;
    }

    /**
     * Get relationship mappings array
     */
    public function getRelationshipMappings(): array
    {
        return $this->relationshipMappings;
    }

    /**
     * Get computed fields array
     */
    public function getComputedFields(): array
    {
        return $this->computedFields;
    }
}
