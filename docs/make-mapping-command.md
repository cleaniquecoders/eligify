# Model Mapping Generator Command

## Overview

The `eligify:make-mapping` command generates model mapping classes for the Eligify data extraction system. It automatically analyzes your Eloquent models and creates mapping classes with field mappings, relationship mappings, and computed fields.

## Command Syntax

```bash
php artisan eligify:make-mapping {model} [options]
```

### Arguments

- `model` (required): The fully qualified model class name (e.g., `App\Models\User`)

### Options

- `--name`: Custom name for the mapping class (in kebab-case)
- `--force`: Overwrite existing mapping class if it exists
- `--namespace`: Custom namespace for the mapping class (default: `App\Eligify\Mappings`)

## Usage Examples

### Basic Usage

Generate a mapping class for the User model:

```bash
php artisan eligify:make-mapping "App\Models\User"
```

This creates `app/Eligify/Mappings/UserMapping.php` with:

- Automatic field mappings for common timestamp fields
- Relationship detection and mapping suggestions
- Computed fields for common patterns (is_verified, is_active, etc.)

### Custom Name

Create a mapping with a custom name:

```bash
php artisan eligify:make-mapping "App\Models\User" --name=premium-user
```

This creates `PremiumUserMapping.php` instead of the default `UserMapping.php`.

### Custom Namespace

Generate mapping in a custom namespace:

```bash
php artisan eligify:make-mapping "App\Models\Order" --namespace="App\Mappings\Eligify"
```

This creates the mapping at `app/Mappings/Eligify/OrderMapping.php`.

### Force Overwrite

Regenerate an existing mapping:

```bash
php artisan eligify:make-mapping "App\Models\User" --force
```

## What Gets Generated?

### 1. Field Mappings

The command automatically maps common timestamp fields to more readable names:

```php
protected array $fieldMappings = [
    'created_at' => 'created_date',
    'updated_at' => 'updated_date',
    'email_verified_at' => 'email_verified_timestamp',
    'approved_at' => 'approved_timestamp',
];
```

### 2. Relationship Mappings

Detected relationships get common aggregation mappings:

```php
protected array $relationshipMappings = [
    'orders.count' => 'orders_count',
    'orders.sum:amount' => 'total_order_amount',
    'orders.avg:rating' => 'avg_order_rating',
    'posts.count' => 'posts_count',
];
```

### 3. Computed Fields

Common patterns are automatically detected and configured:

```php
protected array $computedFields = [
    'is_verified' => null,
    'is_active' => null,
    'is_approved' => null,
];

public function __construct()
{
    $this->computedFields = [
        'is_verified' => fn ($model) => !is_null($model->email_verified_at ?? null),
        'is_active' => fn ($model) => !is_null($model->is_active ?? null),
        'is_approved' => fn ($model) => !is_null($model->approved_at ?? null),
    ];
}
```

## Generated File Structure

```php
<?php

namespace App\Eligify\Mappings;

use CleaniqueCoders\Eligify\Mappings\AbstractModelMapping;

/**
 * Model mapping for App\Models\User
 *
 * Generated on 2025-10-29 10:30:00
 */
class UserMapping extends AbstractModelMapping
{
    public function getModelClass(): string
    {
        return 'App\Models\User';
    }

    protected array $fieldMappings = [
        // Auto-generated mappings
    ];

    protected array $relationshipMappings = [
        // Auto-generated relationship mappings
    ];

    protected array $computedFields = [
        // Auto-generated computed fields
    ];

    public function __construct()
    {
        $this->computedFields = [
            // Closures for computed fields
        ];
    }
}
```

## Model Analysis Features

### Database Schema Detection

The command attempts to read the model's database table schema:

- Retrieves all columns from the table
- Excludes sensitive fields (password, remember_token)
- Falls back to fillable/guarded attributes if table doesn't exist

### Relationship Detection

Automatically detects model relationships by:

- Scanning public methods on the model
- Checking return type hints for Eloquent relation types
- Generating common aggregation patterns

### Common Field Patterns

Recognizes and maps these common patterns:

**Timestamp Fields:**

- `created_at` → `created_date`
- `updated_at` → `updated_date`
- `deleted_at` → `deleted_date`
- `email_verified_at` → `email_verified_timestamp`
- `published_at` → `published_timestamp`
- `approved_at` → `approved_timestamp`

**Computed Boolean Fields:**

- `email_verified_at` → `is_verified`
- `approved_at` → `is_approved`
- `published_at` → `is_published`
- `deleted_at` → `is_deleted`

## Post-Generation Steps

After generating a mapping class:

1. **Review the generated code** - The command creates a starting point, but you should review and customize it.

2. **Add custom computed fields** - Add any domain-specific computed fields:

   ```php
   'credit_score_tier' => fn ($model) => $this->calculateCreditTier($model->credit_score),
   ```

3. **Refine relationship mappings** - Adjust relationship mappings for your specific needs:

   ```php
   'orders.sum:total' => 'lifetime_value',
   'orders.where:status,completed.count' => 'completed_orders_count',
   ```

4. **Register the mapping** - Add to your configuration:

   ```php
   // config/eligify.php
   'model_mappings' => [
       'App\Models\User' => App\Eligify\Mappings\UserMapping::class,
   ],
   ```

## Common Use Cases

### E-commerce Order Model

```bash
php artisan eligify:make-mapping "App\Models\Order"
```

Generates mappings for:

- Order timestamps (ordered_at, shipped_at, delivered_at)
- Order totals and amounts
- Relationships to items, customer, payments

### Multi-tenant User Model

```bash
php artisan eligify:make-mapping "App\Models\User" --namespace="App\Tenancy\Mappings"
```

Creates tenant-specific user mappings with:

- User verification status
- Subscription relationships
- Team memberships

### Content Management Post Model

```bash
php artisan eligify:make-mapping "App\Models\Post"
```

Maps:

- Publication status
- Author relationships
- Comment counts and engagement metrics

## Troubleshooting

### "Table does not exist" Warning

If the model's table hasn't been migrated yet:

- The command falls back to fillable/guarded attributes
- You can still generate the mapping, then update it after migration

### "Model class does not exist" Error

Ensure:

- The model class path is correct
- The model file exists and is autoloaded
- You're using the fully qualified class name

### Missing Relationships

If relationships aren't detected:

- Add return type hints to relationship methods
- Manually add relationship mappings after generation

## Best Practices

1. **Generate early** - Create mappings when you create models
2. **Version control** - Commit generated mappings to track changes
3. **Customize** - Treat generated code as a starting point
4. **Document** - Add comments explaining complex computed fields
5. **Test** - Verify mappings work with your actual data

## Related Documentation

- [Model Data Extraction Guide](model-data-extraction.md)
- [Model Mappings Overview](model-mappings.md)
- [Configuration Guide](configuration.md)
- [Usage Guide](usage-guide.md)
