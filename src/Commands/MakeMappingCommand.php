<?php

namespace CleaniqueCoders\Eligify\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MakeMappingCommand extends Command
{
    public $signature = 'eligify:make-mapping
                        {model : The fully qualified model class name}
                        {--name= : Custom name for the mapping class (in kebab-case)}
                        {--force : Overwrite existing mapping class}
                        {--namespace= : Custom namespace for the mapping class}';

    public $description = 'Generate a model mapping class for Eligify data extraction';

    protected array $commonTimestampFields = [
        'created_at',
        'updated_at',
        'deleted_at',
        'email_verified_at',
        'published_at',
        'scheduled_at',
        'verified_at',
        'approved_at',
        'rejected_at',
        'completed_at',
        'started_at',
        'ended_at',
    ];

    protected array $commonComputedFields = [
        'is_verified' => 'email_verified_at',
        'is_active' => 'is_active',
        'is_approved' => 'approved_at',
        'is_published' => 'published_at',
        'is_deleted' => 'deleted_at',
        'is_completed' => 'completed_at',
        'is_rejected' => 'rejected_at',
    ];

    public function handle(): int
    {
        $modelClass = $this->argument('model');

        // Validate model class
        if (! class_exists($modelClass)) {
            $this->error("Model class [{$modelClass}] does not exist.");

            return self::FAILURE;
        }

        if (! is_subclass_of($modelClass, Model::class)) {
            $this->error("Class [{$modelClass}] is not an Eloquent model.");

            return self::FAILURE;
        }

        try {
            $model = new $modelClass;
        } catch (\Exception $e) {
            $this->error("Could not instantiate model [{$modelClass}]: {$e->getMessage()}");

            return self::FAILURE;
        }

        // Generate mapping class details
        $className = $this->generateClassName($modelClass);
        $namespace = $this->option('namespace') ?: $this->getDefaultNamespace($modelClass);
        $path = $this->getPath($namespace, $className);

        // Check if file exists
        if (File::exists($path) && ! $this->option('force')) {
            $this->error("Mapping class already exists at [{$path}]. Use --force to overwrite.");

            return self::FAILURE;
        }

        // Extract model information
        $this->info("Analyzing model [{$modelClass}]...");
        $fields = $this->getModelFields($model);
        $relationships = $this->getModelRelationships($model);

        // Generate mappings
        $fieldMappings = $this->generateFieldMappings($fields);
        $relationshipMappings = $this->generateRelationshipMappings($relationships);
        $computedFields = $this->generateComputedFields($fields);

        // Generate the mapping class
        $content = $this->generateMappingClass(
            $namespace,
            $className,
            $modelClass,
            $fieldMappings,
            $relationshipMappings,
            $computedFields
        );

        // Ensure directory exists
        File::ensureDirectoryExists(dirname($path));

        // Write file
        File::put($path, $content);

        $this->info("Mapping class created successfully at [{$path}]");
        $this->newLine();
        $this->line('Summary:');
        $this->line('  - Fields detected: '.count($fields));
        $this->line('  - Relationships detected: '.count($relationships));
        $this->line('  - Computed fields: '.count($computedFields));

        return self::SUCCESS;
    }

    protected function generateClassName(string $modelClass): string
    {
        if ($customName = $this->option('name')) {
            return Str::studly($customName).'Mapping';
        }

        $modelName = class_basename($modelClass);

        return $modelName.'Mapping';
    }

    protected function getDefaultNamespace(string $modelClass): string
    {
        // Extract the base namespace from the model class
        $modelNamespace = substr($modelClass, 0, strrpos($modelClass, '\\Models\\'));

        // Default to App if no namespace is found
        if (empty($modelNamespace)) {
            return 'App\\Eligify\\Mappings';
        }

        return ltrim($modelNamespace.'\\Eligify\\Mappings', '\\');
    }

    protected function getPath(string $namespace, string $className): string
    {
        // Handle both App\ and Workbench\App\ prefixes
        $namespacePath = $namespace;
        $namespacePath = str_replace('Workbench\\App\\', '', $namespacePath);
        $namespacePath = str_replace('App\\', '', $namespacePath);
        $namespacePath = str_replace('\\', '/', $namespacePath);

        return app_path($namespacePath.'/'.$className.'.php');
    }

    protected function getModelFields(Model $model): array
    {
        try {
            $table = $model->getTable();
            $connection = $model->getConnectionName() ?: config('database.default');

            if (! Schema::connection($connection)->hasTable($table)) {
                $this->warn("Table [{$table}] does not exist. Using fillable/guarded attributes.");

                return $this->getFieldsFromFillable($model);
            }

            $columns = Schema::connection($connection)->getColumnListing($table);

            return array_diff($columns, ['id', 'uuid', 'password', 'remember_token']);
        } catch (\Exception $e) {
            $this->warn("Could not read table schema: {$e->getMessage()}. Using fillable attributes.");

            return $this->getFieldsFromFillable($model);
        }
    }

    protected function getFieldsFromFillable(Model $model): array
    {
        $fillable = $model->getFillable();
        if (! empty($fillable)) {
            return $fillable;
        }

        // Try to get all attributes except guarded
        $guarded = $model->getGuarded();
        if ($guarded === ['*']) {
            return [];
        }

        return [];
    }

    protected function getModelRelationships(Model $model): array
    {
        $relationships = [];
        $reflection = new \ReflectionClass($model);

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            // Skip if method is inherited from base Model class
            if ($method->getDeclaringClass()->getName() === Model::class) {
                continue;
            }

            // Skip magic methods and getters/setters
            if (Str::startsWith($method->getName(), ['get', 'set', 'scope', '__'])) {
                continue;
            }

            // Check if method has no required parameters
            if ($method->getNumberOfRequiredParameters() > 0) {
                continue;
            }

            $methodName = $method->getName();

            // Try to detect if it's a relationship
            try {
                $returnType = $method->getReturnType();
                if ($returnType && method_exists($returnType, 'getName')) {
                    $returnTypeName = $returnType->getName();
                    if (Str::contains($returnTypeName, 'Illuminate\Database\Eloquent\Relations')) {
                        // Get the related model class
                        $relatedModelClass = $this->getRelatedModelClass($model, $methodName);
                        $relationships[$methodName] = [
                            'name' => $methodName,
                            'relatedModel' => $relatedModelClass,
                            'hasMapping' => $this->checkIfMappingExists($relatedModelClass),
                        ];
                    }
                }
            } catch (\Exception $e) {
                // Skip if we can't determine the return type
            }
        }

        return $relationships;
    }

    protected function getRelatedModelClass(Model $model, string $relationshipName): ?string
    {
        try {
            $relation = $model->{$relationshipName}();

            if (method_exists($relation, 'getRelated')) {
                return get_class($relation->getRelated());
            }
        } catch (\Exception $e) {
            // Silently fail if we can't get the related model
        }

        return null;
    }

    protected function checkIfMappingExists(?string $modelClass): bool
    {
        if (! $modelClass) {
            return false;
        }

        // Try to find mapping class in common locations
        $modelName = class_basename($modelClass);
        $mappingClassName = $modelName.'Mapping';

        // Check in package mappings
        $packageMappingClass = "CleaniqueCoders\\Eligify\\Mappings\\{$mappingClassName}";
        if (class_exists($packageMappingClass)) {
            return true;
        }

        // Check in app mappings
        $namespace = $this->getDefaultNamespace($modelClass);
        $appMappingClass = "{$namespace}\\{$mappingClassName}";
        if (class_exists($appMappingClass)) {
            return true;
        }

        return false;
    }

    protected function generateFieldMappings(array $fields): array
    {
        $mappings = [];

        foreach ($fields as $field) {
            // Map common timestamp fields to more readable names
            if (in_array($field, $this->commonTimestampFields)) {
                $mappedName = Str::snake(str_replace(['_at', '_on'], '', $field));
                if ($field === 'created_at') {
                    $mappedName = 'created_date';
                } elseif ($field === 'updated_at') {
                    $mappedName = 'updated_date';
                } elseif ($field === 'deleted_at') {
                    $mappedName = 'deleted_date';
                } elseif ($field === 'email_verified_at') {
                    $mappedName = 'email_verified_timestamp';
                } else {
                    $mappedName .= '_timestamp';
                }

                $mappings[$field] = $mappedName;
            }
        }

        return $mappings;
    }

    protected function generateRelationshipMappings(array $relationships): array
    {
        $mappings = [];

        foreach ($relationships as $relationshipData) {
            // Handle both old array format and new associative format
            if (is_string($relationshipData)) {
                $relationship = $relationshipData;
                $relatedModel = null;
                $hasMapping = false;
            } else {
                $relationship = $relationshipData['name'];
                $relatedModel = $relationshipData['relatedModel'] ?? null;
                $hasMapping = $relationshipData['hasMapping'] ?? false;
            }

            if ($hasMapping && $relatedModel) {
                // If the related model has a mapping, suggest using that mapping's prefix
                $modelName = class_basename($relatedModel);
                $prefix = Str::snake($modelName);

                // Add a comment in the generated mappings
                $this->info("  → Relationship '{$relationship}' uses {$modelName} which has a mapping with prefix '{$prefix}'");

                // Generate basic count, but user should manually configure field mappings from related mapper
                $mappings[$relationship.'.count'] = Str::snake($relationship).'_count';
                $mappings["// {$relationship} uses {$modelName}Mapping"] = "// You can reference fields like: {$relationship}.{$prefix}.field_name";
            } else {
                // Generate common relationship mappings
                $mappings[$relationship.'.count'] = Str::snake($relationship).'_count';

                // Add common aggregations for likely numeric relationships
                if (Str::plural($relationship) === $relationship) {
                    // It's likely a plural relationship (hasMany, belongsToMany)
                    $singular = Str::singular($relationship);
                    $mappings[$relationship.'.sum:amount'] = 'total_'.$singular.'_amount';
                    $mappings[$relationship.'.avg:rating'] = 'avg_'.$singular.'_rating';
                }
            }
        }

        return $mappings;
    }

    protected function generateComputedFields(array $fields): array
    {
        $computed = [];

        foreach ($fields as $field) {
            // Check for common computed field patterns
            foreach ($this->commonComputedFields as $computedName => $sourceField) {
                if ($field === $sourceField) {
                    $computed[$computedName] = $sourceField;
                }
            }

            // Generate is_* fields for boolean columns
            if (Str::startsWith($field, 'is_') || Str::startsWith($field, 'has_')) {
                // Already a boolean field, might want to keep it as-is
                continue;
            }

            // Generate count fields for *_count columns
            if (Str::endsWith($field, '_count')) {
                $baseName = str_replace('_count', '', $field);
                $computed['has_'.$baseName] = $field;
            }
        }

        return $computed;
    }

    protected function generateMappingClass(
        string $namespace,
        string $className,
        string $modelClass,
        array $fieldMappings,
        array $relationshipMappings,
        array $computedFields
    ): string {
        $stub = File::get(__DIR__.'/../../stubs/model-mapping.stub');

        // Generate prefix from model name
        $modelName = class_basename($modelClass);
        $prefix = Str::snake($modelName);

        $replacements = [
            '{{ namespace }}' => $namespace,
            '{{ class }}' => $className,
            '{{ modelClass }}' => $modelClass,
            '{{ modelName }}' => $modelName,
            '{{ prefix }}' => $prefix,
            '{{ date }}' => now()->format('Y-m-d H:i:s'),
            '{{ fieldMappings }}' => $this->formatArrayForStub($fieldMappings, 8),
            '{{ relationshipMappings }}' => $this->formatArrayForStub($relationshipMappings, 8),
            '{{ computedFieldsPlaceholder }}' => $this->formatComputedFieldsPlaceholder($computedFields, 8),
            '{{ computedFields }}' => $this->formatComputedFieldsClosures($computedFields, 12),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $stub);
    }

    protected function formatArrayForStub(array $data, int $indent = 8): string
    {
        if (empty($data)) {
            return '';
        }

        $spaces = str_repeat(' ', $indent);
        $lines = [];

        foreach ($data as $key => $value) {
            // Handle comment lines
            if (Str::startsWith($key, '//')) {
                $lines[] = "\n{$spaces}{$value}";
            } else {
                $lines[] = "\n{$spaces}'{$key}' => '{$value}',";
            }
        }

        return implode('', $lines)."\n".str_repeat(' ', $indent - 4);
    }

    protected function formatComputedFieldsPlaceholder(array $fields, int $indent = 8): string
    {
        if (empty($fields)) {
            return '';
        }

        $spaces = str_repeat(' ', $indent);
        $lines = [];

        foreach ($fields as $key => $value) {
            $lines[] = "\n{$spaces}'{$key}' => null,";
        }

        return implode('', $lines)."\n".str_repeat(' ', $indent - 4);
    }

    protected function formatComputedFieldsClosures(array $fields, int $indent = 12): string
    {
        if (empty($fields)) {
            return '';
        }

        $spaces = str_repeat(' ', $indent);
        $lines = [];

        foreach ($fields as $key => $sourceField) {
            if (Str::startsWith($key, 'is_')) {
                $lines[] = "\n{$spaces}// Check if {$sourceField} is set";
                $lines[] = "\n{$spaces}'{$key}' => fn (\$model) => !is_null(\$model->{$sourceField} ?? null),";
            } elseif (Str::startsWith($key, 'has_')) {
                $sourceField = str_replace('has_', '', $key).'_count';
                $lines[] = "\n{$spaces}// Check if has {$sourceField}";
                $lines[] = "\n{$spaces}'{$key}' => fn (\$model) => (\$model->{$sourceField} ?? 0) > 0,";
            } else {
                $lines[] = "\n{$spaces}'{$key}' => fn (\$model) => \$model->{$sourceField} ?? null,";
            }
            $lines[] = "\n";
        }

        return implode('', $lines).str_repeat(' ', $indent - 4);
    }
}
