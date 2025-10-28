<?php

namespace CleaniqueCoders\Eligify\Mappings;

use CleaniqueCoders\Eligify\Contracts\ModelMapping;
use CleaniqueCoders\Eligify\Support\ModelDataExtractor;

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
     * Configure the extractor with all mappings
     */
    public function configure(ModelDataExtractor $extractor): ModelDataExtractor
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
}
