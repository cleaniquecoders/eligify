# Model Mapping Generation Guide

## Overview

Eligify provides powerful commands to automatically generate model mappings from your Eloquent models. These mappings define how model data is extracted and used in eligibility evaluations.

## Quick Start

### Generate a Single Mapping

```bash
php artisan eligify:make-mapping "App\Models\User"
```

### Generate Mappings for All Models

```bash
php artisan eligify:make-all-mappings
```

## Features

### 1. Automatic Prefix Generation

Each mapping automatically generates a **prefix** based on the model name. This prefix namespaces your fields for clear identification.

**Examples:**

- `User` model → `user` prefix → Fields: `user.name`, `user.email`
- `Applicant` model → `applicant` prefix → Fields: `applicant.income`, `applicant.credit_score`
- `LoanApplication` model → `loan.application` prefix → Fields: `loan.application.amount`, `loan.application.status`

#### Setting a Custom Prefix

You can override the automatic prefix in your mapping class:

```php
class ApplicantMapping extends AbstractModelMapping
{
    protected ?string $prefix = 'applicant'; // Custom prefix

    // ... rest of your mapping
}
```

### 2. Relationship Detection with Mapping Awareness

When generating a mapping, the command automatically detects:

- All Eloquent relationships in your model
- Whether related models have existing mappings
- Suggests using the related model's mapping prefix

**Example:**

If you have:

```php
class Application extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

And `UserMapping` exists, the generator will:

- Detect that `user` relationship points to a model with a mapping
- Add helpful comments in the generated mapping
- Suggest using `user.user.field_name` pattern for accessing user fields

### 3. Bulk Generation from Directory

The `eligify:make-all-mappings` command scans a directory and generates mappings for all Eloquent models.

**Command Options:**

```bash
php artisan eligify:make-all-mappings [options]
```

**Options:**

- `--path=app/Models` - Directory to scan (relative to base_path)
- `--namespace=App\\Models` - Base namespace for models
- `--force` - Overwrite existing mappings
- `--dry-run` - Preview what would be generated without creating files

**Examples:**

```bash
# Scan default app/Models directory
php artisan eligify:make-all-mappings

# Scan custom directory
php artisan eligify:make-all-mappings --path=modules/User/Models --namespace=Modules\\User\\Models

# Preview without creating files
php artisan eligify:make-all-mappings --dry-run

# Force overwrite existing mappings
php artisan eligify:make-all-mappings --force
```

## Generated Mapping Structure

Here's what a generated mapping looks like:

```php
<?php

namespace App\Eligify\Mappings;

use CleaniqueCoders\Eligify\Data\Mappings\AbstractModelMapping;

class ApplicantMapping extends AbstractModelMapping
{
    public function getModelClass(): string
    {
        return 'App\Models\Applicant';
    }

    protected array $fieldMappings = [
        'created_at' => 'created_date',
        'updated_at' => 'updated_date',
        'email_verified_at' => 'email_verified_timestamp',
    ];

    protected array $relationshipMappings = [
        'applications.count' => 'applications_count',
        // applications uses Application which has ApplicationMapping
        // You can reference fields like: applications.application.field_name
    ];

    protected array $computedFields = [
        'is_verified' => null,
        'is_active' => null,
    ];

    // Auto-generated prefix based on model name
    protected ?string $prefix = 'applicant';

    public function __construct()
    {
        $this->computedFields = [
            'is_verified' => fn ($model) => !is_null($model->email_verified_at ?? null),
            'is_active' => fn ($model) => ($model->status ?? null) === 'active',
        ];
    }

    public function getName(): string
    {
        return 'Applicant';
    }

    public function getDescription(): string
    {
        return 'Model mapping for App\Models\Applicant with field extraction and computed values';
    }
}
```

## Using Prefixes in Rules

Once you have mappings with prefixes, use them in your eligibility rules:

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

Eligify::criteria('loan_approval')
    ->addRule('applicant.income', '>=', 3000)
    ->addRule('applicant.credit_score', '>=', 650)
    ->addRule('applicant.is_verified', '=', true)
    ->evaluate($applicant);
```

## Accessing Related Model Fields

When a relationship has a mapping, you can access nested fields:

```php
// If Application has a 'user' relationship with UserMapping
Eligify::criteria('application_review')
    ->addRule('application.amount', '<=', 50000)
    ->addRule('application.user.is_verified', '=', true)  // Access user fields via mapping
    ->addRule('application.user.credit_score', '>=', 700)
    ->evaluate($application);
```

## Advanced Usage

### Custom Mapping Generation

You can customize the generated mapping:

```bash
# Use custom name
php artisan eligify:make-mapping "App\Models\User" --name=premium-user

# Use custom namespace
php artisan eligify:make-mapping "App\Models\User" --namespace=App\\Custom\\Mappings

# Force overwrite
php artisan eligify:make-mapping "App\Models\User" --force
```

### Workbench Models

The command works with Workbench models for package development:

```bash
php artisan eligify:make-all-mappings --path=workbench/app/Models --namespace=Workbench\\App\\Models
```

## Best Practices

1. **Consistent Prefixes**: Keep prefixes simple and model-based (e.g., `user`, `applicant`, `loan`)
2. **Relationship Mappings**: When a related model has a mapping, reference it using the relationship name + model prefix pattern
3. **Regeneration**: Use `--force` carefully when regenerating mappings as it will overwrite custom logic
4. **Version Control**: Commit generated mappings to version control for team consistency

## Troubleshooting

### Mapping Not Found

If the generator can't find a mapping for a related model:

- Check that the mapping class exists in `App\Eligify\Mappings` or `CleaniqueCoders\Eligify\Data\Mappings`
- Ensure the mapping class follows the naming convention: `{ModelName}Mapping`

### Database Connection Issues

If table schema detection fails:

- Ensure your database is configured and accessible
- The generator will fall back to using `$fillable` properties if table doesn't exist
- Run migrations before generating mappings for accurate field detection

### Relationship Detection

If relationships aren't detected:

- Ensure relationship methods have proper return type hints
- Methods should be public and take no required parameters
- Return types must be Eloquent relationship classes

## Next Steps

- Learn about [Dynamic Field Selection](dynamic-field-selection.md)
- Explore [Model Data Extraction](model-data-extraction.md)
- Read about [Policy Integration](policy-integration.md)
