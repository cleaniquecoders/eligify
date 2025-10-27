<?php

namespace CleaniqueCoders\Eligify\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Advanced model data extractor for eligibility evaluations
 */
class ModelDataExtractor
{
    protected array $config;

    protected array $fieldMappings = [];

    protected array $relationshipMappings = [];

    protected array $computedFields = [];

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'include_timestamps' => true,
            'include_relationships' => true,
            'include_computed_fields' => true,
            'max_relationship_depth' => 2,
            'exclude_sensitive_fields' => true,
            'sensitive_fields' => ['password', 'remember_token', 'api_token'],
            'date_format' => 'Y-m-d H:i:s',
        ], $config);
    }

    /**
     * Extract data from a model for eligibility evaluation
     */
    public function extract(Model $model): array
    {
        $data = [];

        // Extract basic attributes
        $data = array_merge($data, $this->extractAttributes($model));

        // Extract computed fields
        if ($this->config['include_computed_fields']) {
            $data = array_merge($data, $this->extractComputedFields($model));
        }

        // Extract relationship data
        if ($this->config['include_relationships']) {
            $data = array_merge($data, $this->extractRelationships($model));
        }

        // Apply field mappings
        $data = $this->applyFieldMappings($data);

        // Apply relationship mappings
        $data = $this->applyRelationshipMappings($model, $data);

        // Apply custom computed fields
        $data = $this->applyComputedFields($model, $data);

        return $data;
    }

    /**
     * Set custom field mappings
     */
    public function setFieldMappings(array $mappings): self
    {
        $this->fieldMappings = $mappings;

        return $this;
    }

    /**
     * Set custom relationship mappings
     */
    public function setRelationshipMappings(array $mappings): self
    {
        $this->relationshipMappings = $mappings;

        return $this;
    }

    /**
     * Set custom computed fields
     */
    public function setComputedFields(array $fields): self
    {
        $this->computedFields = $fields;

        return $this;
    }

    /**
     * Extract basic model attributes
     */
    protected function extractAttributes(Model $model): array
    {
        $attributes = $model->toArray();

        // Remove sensitive fields
        if ($this->config['exclude_sensitive_fields']) {
            foreach ($this->config['sensitive_fields'] as $field) {
                unset($attributes[$field]);
            }
        }

        // Format dates
        foreach ($model->getDates() as $dateField) {
            if (isset($attributes[$dateField]) && $attributes[$dateField]) {
                $attributes[$dateField] = $model->{$dateField}->format($this->config['date_format']);
            }
        }

        return $attributes;
    }

    /**
     * Extract computed fields like age, duration, etc.
     */
    protected function extractComputedFields(Model $model): array
    {
        $computed = [];

        // Add timestamp-based computed fields
        if ($this->config['include_timestamps']) {
            if ($model->created_at) {
                $computed['created_days_ago'] = abs((int) round($model->created_at->diffInDays(now())));
                $computed['created_months_ago'] = abs((int) round($model->created_at->diffInMonths(now())));
                $computed['created_years_ago'] = abs((int) round($model->created_at->diffInYears(now())));
                $computed['account_age_days'] = $computed['created_days_ago']; // Alias
            }

            if ($model->updated_at) {
                $computed['updated_days_ago'] = abs((int) round($model->updated_at->diffInDays(now())));
                $computed['last_activity_days'] = $computed['updated_days_ago']; // Alias
            }
        }

        // Add model-specific computed fields
        $computed = array_merge($computed, $this->getModelSpecificFields($model));

        return $computed;
    }

    /**
     * Extract relationship data with configurable depth
     */
    protected function extractRelationships(Model $model): array
    {
        $relationships = [];

        foreach ($model->getRelations() as $relationName => $relationData) {
            $relationships = array_merge(
                $relationships,
                $this->processRelationship($relationName, $relationData)
            );
        }

        return $relationships;
    }

    /**
     * Process individual relationship
     */
    protected function processRelationship(string $name, $relationData): array
    {
        $processed = [];

        if ($relationData instanceof Model) {
            // Single model relationship
            $processed[$name] = $this->extractRelationshipData($relationData);
            $processed[$name.'_exists'] = true;
        } elseif (is_iterable($relationData)) {
            // Collection/array relationship
            $count = count($relationData);
            $processed[$name.'_count'] = $count;
            $processed[$name.'_exists'] = $count > 0;

            // Add summary data for collections
            if ($count > 0) {
                $processed = array_merge($processed, $this->getCollectionSummary($name, $relationData));
            }
        }

        return $processed;
    }

    /**
     * Extract data from related model
     */
    protected function extractRelationshipData(Model $model): array
    {
        // Simplified extraction for related models to avoid infinite recursion
        $data = $model->toArray();

        // Remove sensitive fields
        if ($this->config['exclude_sensitive_fields']) {
            foreach ($this->config['sensitive_fields'] as $field) {
                unset($data[$field]);
            }
        }

        return $data;
    }

    /**
     * Get summary data for relationship collections
     */
    protected function getCollectionSummary(string $relationName, $collection): array
    {
        $summary = [];

        if (empty($collection)) {
            return $summary;
        }

        // Get numeric field summaries
        $numericFields = $this->getNumericFields($collection);
        foreach ($numericFields as $field) {
            $values = collect($collection)->pluck($field)->filter()->values();
            if ($values->isNotEmpty()) {
                $summary[$relationName.'_'.$field.'_sum'] = $values->sum();
                $summary[$relationName.'_'.$field.'_avg'] = $values->avg();
                $summary[$relationName.'_'.$field.'_max'] = $values->max();
                $summary[$relationName.'_'.$field.'_min'] = $values->min();
            }
        }

        // Get date field summaries
        $dateFields = $this->getDateFields($collection);
        foreach ($dateFields as $field) {
            $dates = collect($collection)->pluck($field)->filter()->map(function ($date) {
                return $date instanceof \Carbon\Carbon ? $date : \Carbon\Carbon::parse($date);
            });

            if ($dates->isNotEmpty()) {
                $summary[$relationName.'_'.$field.'_latest'] = $dates->max()->format($this->config['date_format']);
                $summary[$relationName.'_'.$field.'_earliest'] = $dates->min()->format($this->config['date_format']);
                $summary[$relationName.'_'.$field.'_latest_days_ago'] = now()->diffInDays($dates->max());
            }
        }

        return $summary;
    }

    /**
     * Apply custom field mappings
     */
    protected function applyFieldMappings(array $data): array
    {
        foreach ($this->fieldMappings as $originalField => $mappedField) {
            if (isset($data[$originalField])) {
                $data[$mappedField] = $data[$originalField];
                unset($data[$originalField]);
            }
        }

        return $data;
    }

    /**
     * Apply custom relationship mappings
     */
    protected function applyRelationshipMappings(Model $model, array $data): array
    {
        foreach ($this->relationshipMappings as $relationName => $mapping) {
            if (isset($data[$relationName])) {
                foreach ($mapping as $originalKey => $mappedKey) {
                    if (isset($data[$relationName][$originalKey])) {
                        $data[$mappedKey] = $data[$relationName][$originalKey];
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Apply custom computed fields
     */
    protected function applyComputedFields(Model $model, array $data): array
    {
        foreach ($this->computedFields as $fieldName => $closure) {
            if (is_callable($closure)) {
                $data[$fieldName] = $closure($model, $data);
            }
        }

        return $data;
    }

    /**
     * Get model-specific computed fields
     */
    protected function getModelSpecificFields(Model $model): array
    {
        $fields = [];

        // User-specific fields
        if (method_exists($model, 'getAuthIdentifier')) {
            // This is likely a User model
            if (isset($model->email_verified_at)) {
                $fields['email_verified'] = ! is_null($model->email_verified_at);
                if ($model->email_verified_at) {
                    $fields['email_verified_days_ago'] = now()->diffInDays($model->email_verified_at);
                }
            }
        }

        // Add more model-specific logic as needed
        return $fields;
    }

    /**
     * Get numeric fields from collection
     */
    protected function getNumericFields($collection): array
    {
        if (empty($collection)) {
            return [];
        }

        $first = collect($collection)->first();
        if (! is_array($first)) {
            return [];
        }

        return collect($first)
            ->filter(function ($value) {
                return is_numeric($value);
            })
            ->keys()
            ->toArray();
    }

    /**
     * Get date fields from collection
     */
    protected function getDateFields($collection): array
    {
        if (empty($collection)) {
            return [];
        }

        $first = collect($collection)->first();
        if (! is_array($first)) {
            return [];
        }

        $commonDateFields = ['created_at', 'updated_at', 'deleted_at', 'published_at', 'expires_at'];

        return collect($first)
            ->filter(function ($value, $key) use ($commonDateFields) {
                return in_array($key, $commonDateFields) ||
                       str_ends_with($key, '_at') ||
                       str_ends_with($key, '_date');
            })
            ->keys()
            ->toArray();
    }

    /**
     * Create a preconfigured extractor for specific model types
     */
    public static function forModel(string $modelClass): self
    {
        $extractor = new static;

        // Configure based on model type
        switch ($modelClass) {
            case 'App\Models\User':
                $extractor->setFieldMappings([
                    'email_verified_at' => 'email_verified_timestamp',
                    'created_at' => 'registration_date',
                ])
                    ->setComputedFields([
                        'is_premium_user' => fn ($model) => $extractor->safeRelationshipCheck($model, 'subscriptions', 'active'),
                        'total_orders' => fn ($model) => $extractor->safeRelationshipCount($model, 'orders'),
                        'lifetime_value' => fn ($model) => $extractor->safeRelationshipSum($model, 'orders', 'total'),
                    ]);
                break;

            case 'App\Models\Order':
                $extractor->setComputedFields([
                    'days_since_order' => fn ($model) => now()->diffInDays($model->created_at),
                    'order_value_category' => fn ($model) => match (true) {
                        $model->total >= 1000 => 'high',
                        $model->total >= 500 => 'medium',
                        default => 'low'
                    },
                ]);
                break;
        }

        return $extractor;
    }

    /**
     * Safely check relationship with scope
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
     * Safely count relationship
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
     * Safely sum relationship field
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
}
