# Caching

Performance optimization through intelligent caching of evaluation results and criteria definitions.

## Overview

Eligify's caching system can significantly improve performance by:

- Caching evaluation results
- Storing compiled criteria
- Memoizing expensive operations
- Warming caches proactively

## Documentation

- **[Implementation Guide](implementation.md)** - Complete caching implementation
- **[Strategies](strategies.md)** - Cache warming and invalidation patterns
- **[Redis Setup](redis-setup.md)** - Configuring Redis for optimal performance

## Quick Example

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

// Cache evaluation result for 1 hour
$result = Eligify::criteria('Loan Approval')
    ->cache(3600)
    ->evaluate($applicant);

// Cache with custom key
$result = Eligify::criteria('Loan Approval')
    ->cacheAs('loan:'.$applicant->id, 3600)
    ->evaluate($applicant);
```

## Cache Drivers

Supported cache drivers:

- **Redis** (recommended for production)
- **Memcached**
- **Database**
- **File** (development only)
- **Array** (testing)

## Cache Strategies

### 1. Result Caching

Cache the outcome of evaluations:

```php
// Automatically cached based on criteria + entity
$result = $criteria->cache()->evaluate($user);
```

### 2. Criteria Caching

Cache compiled criteria definitions:

```php
// Cache the criteria itself
$criteria = Eligify::criteria('Loan Approval')->remember(3600);
```

### 3. Model Mapping Cache

Cache expensive model transformations:

```php
class UserMapper extends Mapper
{
    protected $cacheTtl = 3600;
}
```

### 4. Cache Warming

Pre-populate caches before heavy load:

```php
php artisan eligify:cache:warm --criteria=loan_approval
```

## When to Use Caching

✅ **Cache when:**

- Evaluations are expensive
- Data doesn't change frequently
- High traffic/load
- Criteria are stable

❌ **Don't cache when:**

- Real-time data required
- Criteria change frequently
- Low traffic
- Development/debugging

## Next Steps

Read the [Implementation Guide](implementation.md) for detailed caching patterns.
