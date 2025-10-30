# Model Mapping

Model mapping allows you to transform your Eloquent models into a structure suitable for eligibility evaluation.

## Overview

The model mapping system provides a flexible way to:

- Extract data from complex models
- Transform nested relationships
- Compute derived values
- Cache mapped data for performance

## Documentation

- **[Getting Started](getting-started.md)** - Quick start guide
- **[Patterns](patterns.md)** - All 4 mapping patterns with examples
- **[Relationship Mapping](relationship-mapping.md)** - Deep dive into relationship patterns
- **[Generator](generator.md)** - Automated mapper generation

## Quick Example

```php
use CleaniqueCoders\Eligify\Support\Mapper;

class UserMapper extends Mapper
{
    protected function map(): array
    {
        return [
            'user_id' => $this->model->id,
            'email' => $this->model->email,
            'credit_score' => $this->model->profile->credit_score,
            'active_loans' => $this->model->loans()->active()->count(),
            'total_income' => $this->model->calculateTotalIncome(),
        ];
    }
}

// Usage
$mapper = new UserMapper($user);
$data = $mapper->toArray();
```

## The Four Patterns

1. **Direct Attribute Mapping** - Simple 1:1 field mapping
2. **Nested Object Mapping** - Access related model properties
3. **Relationship Mapping** - Count, sum, or aggregate relationships
4. **Computed Values** - Derived calculations and methods

## Next Steps

Start with the [Getting Started](getting-started.md) guide to learn the basics.
