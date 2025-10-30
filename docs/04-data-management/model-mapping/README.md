# Model Mapping

Model mapping allows you to transform your Eloquent models into a structure suitable for eligibility evaluation.

## Overview

The model mapping system provides a flexible way to:

- Extract data from complex models
- Transform nested relationships
- Compute derived values
- Create reusable data transformation logic

## Documentation

- **[Getting Started](getting-started.md)** - Complete guide with all 4 patterns
- **[Patterns](patterns.md)** - Documentation structure overview
- **[Relationship Mapping](relationship-mapping.md)** - Quick reference cheatsheet
- **[Generator](generator.md)** - Automated mapper generation

## Quick Example

```php
use CleaniqueCoders\Eligify\Data\Mappings\AbstractModelMapping;
use CleaniqueCoders\Eligify\Data\Extractor;

class UserMapping extends AbstractModelMapping
{
    protected ?string $prefix = 'user';

    protected array $fieldMappings = [
        'email' => 'email_address',
        'annual_income' => 'income',
    ];

    protected array $computedFields = [
        'account_age_days' => fn($model) => $model->created_at->diffInDays(now()),
    ];

    public function getModelClass(): string
    {
        return User::class;
    }
}

// Usage
$data = Extractor::forModel(User::class)->extract($user);
```

## The Four Patterns

1. **Direct Attribute Mapping** - Simple 1:1 field mapping
2. **Nested Object Mapping** - Access related model properties
3. **Relationship Mapping** - Count, sum, or aggregate relationships
4. **Computed Values** - Derived calculations and methods

## Next Steps

Start with the [Getting Started](getting-started.md) guide to learn the basics.
