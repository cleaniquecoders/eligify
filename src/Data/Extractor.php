<?php

namespace CleaniqueCoders\Eligify\Data;

use CleaniqueCoders\Eligify\Data\Contracts\ModelMapping;
use Illuminate\Database\Eloquent\Model;

/**
 * Model data extractor for eligibility evaluations
 *
 * This class transforms Laravel Eloquent models into flat arrays suitable for
 * eligibility rule evaluation. It handles attribute extraction, relationship
 * data, computed fields, and custom field mappings.
 *
 * ## Usage Patterns
 *
 * ### Pattern 1: Quick extraction with defaults
 * ```php
 * $extractor = new Extractor();
 * $snapshot = $extractor->extract($user);
 * ```
 * Best for: Simple cases where you don't need custom mappings
 *
 * ### Pattern 2: Custom configuration per instance
 * ```php
 * $extractor = new Extractor([
 *     'include_relationships' => true,
 *     'max_relationship_depth' => 3,
 * ]);
 * $extractor->setFieldMappings(['annual_income' => 'income'])
 *           ->setComputedFields(['risk_score' => fn($model) => $model->calculateRisk()]);
 * $snapshot = $extractor->extract($user);
 * ```
 * Best for: One-off extractions with specific requirements
 *
 * ### Pattern 3: Model-specific extractors (RECOMMENDED)
 * ```php
 * // Uses pre-configured mappings from config/eligify.php
 * $snapshot = Extractor::forModel(User::class)->extract($user);
 * ```
 * Best for: Production use with multiple model types that need consistent mappings
 *
 * ## Method Guide
 *
 * - `extract()` - Performs the actual data extraction from a model instance
 * - `forModel()` - Factory method that creates a pre-configured extractor for a specific model class
 * - `setFieldMappings()` - Defines custom field name transformations
 * - `setRelationshipMappings()` - Defines custom relationship data mappings
 * - `setComputedFields()` - Adds custom computed fields via closures
 *
 * @see \CleaniqueCoders\Eligify\Data\Contracts\ModelMapping For creating custom model mappings
 */
class Extractor
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
     *
     * This is the main worker method that performs the actual data extraction.
     * It processes the model through several stages:
     *
     * 1. Extracts basic attributes (columns from database)
     * 2. Computes derived fields (age calculations, time-based metrics)
     * 3. Processes relationships (counts, aggregations, summaries)
     * 4. Applies custom field mappings (rename fields)
     * 5. Applies relationship mappings (flatten nested data)
     * 6. Applies custom computed fields (user-defined calculations)
     *
     * @param  Model  $model  The Eloquent model instance to extract data from
     * @return Snapshot Wrapped data object with enhanced functionality
     *
     * @example
     * ```php
     * $extractor = new Extractor();
     * $snapshot = $extractor->extract($user);
     *
     * // Access data directly
     * $income = $snapshot->income;
     * $score = $snapshot->get('credit_score', 650);
     *
     * // Convert to array for rule engine
     * $data = $snapshot->toArray();
     * ```
     */
    public function extract(Model $model): Snapshot
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

        ksort($data);

        // Wrap in Snapshot with metadata
        return new Snapshot($data, [
            'model_class' => get_class($model),
            'model_key' => $model->getKey(),
            'extractor_config' => $this->config,
        ]);
    }

    /**
     * Extract and return as a raw array (legacy compatibility)
     *
     * Use this method if you need the raw array format for backward compatibility
     * or integration with code that explicitly requires arrays.
     *
     * @param  Model  $model  The Eloquent model instance to extract data from
     * @return array Flat array of key-value pairs
     *
     * @example
     * ```php
     * $extractor = new ModelDataExtractor();
     * $data = $extractor->extractArray($user);
     * // Returns: ['id' => 1, 'email' => 'user@example.com', ...]
     * ```
     */
    public function extractArray(Model $model): array
    {
        return $this->extract($model)->toArray();
    }

    /**
     * Set custom field mappings to rename fields during extraction
     *
     * Use this to transform field names from your model structure to the names
     * expected by your eligibility rules. This is useful when rules are defined
     * with generic field names but different models use different column names.
     *
     * @param  array  $mappings  Array of ['original_field' => 'mapped_field'] pairs
     * @return self Fluent interface
     *
     * @example
     * ```php
     * $extractor->setFieldMappings([
     *     'annual_income' => 'income',
     *     'credit_rating' => 'credit_score',
     * ]);
     * // Model has 'annual_income', but rules expect 'income'
     * ```
     */
    public function setFieldMappings(array $mappings): self
    {
        $this->fieldMappings = $mappings;

        return $this;
    }

    /**
     * Set custom relationship mappings to flatten nested relationship data
     *
     * Use this to extract specific fields from relationships and map them to
     * top-level fields in the extracted data. This makes relationship data
     * accessible to rules without nested array access.
     *
     * @param  array  $mappings  Array of ['relationship_name' => ['original_key' => 'mapped_key']] pairs
     * @return self Fluent interface
     *
     * @example
     * ```php
     * $extractor->setRelationshipMappings([
     *     'profile' => [
     *         'employment_status' => 'is_employed',
     *         'employer_name' => 'employer',
     *     ],
     * ]);
     * // Extracts $user->profile->employment_status as 'is_employed' in the flat array
     * ```
     */
    public function setRelationshipMappings(array $mappings): self
    {
        $this->relationshipMappings = $mappings;

        return $this;
    }

    /**
     * Set custom computed fields to add dynamic calculations
     *
     * Use this to define fields that are calculated on-the-fly during extraction.
     * Each computed field is a closure that receives the model and already-extracted
     * data, allowing you to perform complex calculations or business logic.
     *
     * @param  array  $fields  Array of ['field_name' => callable] pairs
     * @return self Fluent interface
     *
     * @example
     * ```php
     * $extractor->setComputedFields([
     *     'risk_score' => function($model, $data) {
     *         return ($data['income'] / $data['debt']) * 100;
     *     },
     *     'approval_probability' => fn($model) => $model->calculateApprovalChance(),
     * ]);
     * ```
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
     * Create a preconfigured extractor for specific model types (RECOMMENDED)
     *
     * This factory method creates an extractor instance that's already configured
     * with model-specific mappings defined in config/eligify.php. This is the
     * recommended approach for production use as it centralizes your extraction
     * logic and ensures consistency across your application.
     *
     * The method looks up the model class in config('eligify.model_extraction.model_mappings')
     * and applies the corresponding mapping class if found. Falls back to a default
     * mapping if configured.
     *
     * @param  string  $modelClass  Fully qualified model class name (e.g., App\Models\User::class)
     * @return self Configured ModelDataExtractor instance ready to use
     *
     * @example
     * ```php
     * // Configure in config/eligify.php:
     * 'model_extraction' => [
     *     'model_mappings' => [
     *         \App\Models\User::class => \App\Eligify\Mappings\UserMapping::class,
     *         \App\Models\LoanApplication::class => \App\Eligify\Mappings\LoanMapping::class,
     *     ],
     * ],
     *
     * // Then use in your code:
     * $userData = ModelDataExtractor::forModel(User::class)->extract($user);
     * $loanData = ModelDataExtractor::forModel(LoanApplication::class)->extract($loan);
     * ```
     *
     * @see \CleaniqueCoders\Eligify\Data\Contracts\ModelMapping For creating custom mapping classes
     */
    public static function forModel(string $modelClass): self
    {
        $config = config('eligify.model_extraction', []);
        $extractor = new static($config);

        // Get model mappings from config
        $modelMappings = $config['model_mappings'] ?? [];

        // Check if there's a mapping class for this model
        if (isset($modelMappings[$modelClass])) {
            $mappingClass = $modelMappings[$modelClass];

            // Instantiate and apply the mapping
            if (class_exists($mappingClass)) {
                $mapping = new $mappingClass;

                if ($mapping instanceof ModelMapping) {
                    return $mapping->configure($extractor);
                }
            }
        }

        // Check for default mapping if configured
        $defaultMapping = $config['default_mapping'] ?? null;
        if ($defaultMapping && class_exists($defaultMapping)) {
            $mapping = new $defaultMapping;

            if ($mapping instanceof ModelMapping) {
                return $mapping->configure($extractor);
            }
        }

        // Return unconfigured extractor if no mapping found
        return $extractor;
    }
}
