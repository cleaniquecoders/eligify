# Cache Implementation

Eligify includes a comprehensive caching system to optimize evaluation performance and reduce database queries. This guide covers how caching works and how to configure it for your needs.

## Overview

Eligify implements two types of caching:

1. **Evaluation Cache** - Caches the results of eligibility evaluations
2. **Rule Compilation Cache** - Caches compiled rules for faster repeated evaluations

## Configuration

All cache settings are in `config/eligify.php`:

```php
'evaluation' => [
    // Enable/disable evaluation caching
    'cache_enabled' => env('ELIGIFY_EVALUATION_CACHE_ENABLED', true),

    // Cache TTL in minutes
    'cache_ttl' => env('ELIGIFY_EVALUATION_CACHE_TTL', 60),

    // Cache key prefix
    'cache_prefix' => env('ELIGIFY_EVALUATION_CACHE_PREFIX', 'eligify_eval'),
],

'performance' => [
    // Enable rule compilation for better performance
    'compile_rules' => env('ELIGIFY_PERF_COMPILE_RULES', true),

    // Rule compilation cache TTL in minutes
    'compilation_cache_ttl' => env('ELIGIFY_PERF_COMPILATION_CACHE_TTL', 1440), // 24 hours
],
```

### Environment Variables

Add these to your `.env` file:

```env
# Evaluation Cache
ELIGIFY_EVALUATION_CACHE_ENABLED=true
ELIGIFY_EVALUATION_CACHE_TTL=60
ELIGIFY_EVALUATION_CACHE_PREFIX=eligify_eval

# Compilation Cache
ELIGIFY_PERF_COMPILE_RULES=true
ELIGIFY_PERF_COMPILATION_CACHE_TTL=1440
```

## How Caching Works

### Evaluation Caching

When you evaluate criteria, Eligify generates a unique cache key based on:

- Criteria ID and last update timestamp
- Rules count and last update timestamp
- Input data hash

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

$data = [
    'age' => 25,
    'income' => 50000,
    'credit_score' => 720,
];

// First call: Executes evaluation and caches result
$result1 = Eligify::evaluate('loan-approval', $data);

// Second call: Returns cached result (much faster)
$result2 = Eligify::evaluate('loan-approval', $data);
```

### Rule Compilation Caching

Rules are fetched from the database and compiled for evaluation. This compilation is cached to avoid repeated database queries:

```php
// Internally, RuleEngine caches the compiled rules
// This happens automatically when you evaluate criteria
```

## Cache Invalidation

Eligify automatically invalidates cache when:

- A criteria is updated
- A criteria is deleted
- Any rule is created, updated, or deleted
- A criteria is activated/deactivated

This is handled by model observers:

- `CriteriaObserver` - Invalidates cache on criteria changes
- `RuleObserver` - Invalidates cache on rule changes

### Manual Cache Invalidation

You can manually invalidate cache:

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

// Invalidate cache for specific criteria
Eligify::invalidateCache('loan-approval');

// Flush all evaluation cache
Eligify::flushCache();
```

## Artisan Commands

### View Cache Statistics

```bash
php artisan eligify:cache:stats
```

Displays:

- Current cache driver
- Cache configuration
- TTL settings
- Recommendations for optimization

### Clear Cache

```bash
# Clear all Eligify caches
php artisan eligify:cache:clear

# Clear only evaluation cache
php artisan eligify:cache:clear --type=evaluation

# Clear only compilation cache
php artisan eligify:cache:clear --type=compilation

# Force clear without confirmation
php artisan eligify:cache:clear --force
```

### Warm Up Cache

Pre-populate cache with sample data for better performance:

```bash
# Warm up all active criteria
php artisan eligify:cache:warmup --all

# Warm up specific criteria
php artisan eligify:cache:warmup loan-approval

# Specify number of samples
php artisan eligify:cache:warmup loan-approval --samples=50
```

## Programmatic Cache Management

### Check if Evaluation is Cached

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

$data = ['age' => 25, 'income' => 50000];

if (Eligify::isCached('loan-approval', $data)) {
    echo "This evaluation is already cached";
}
```

### Warm Up Cache Programmatically

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

$sampleDataSets = [
    ['age' => 20, 'income' => 30000],
    ['age' => 25, 'income' => 45000],
    ['age' => 30, 'income' => 60000],
];

$warmedUp = Eligify::warmupCache('loan-approval', $sampleDataSets);
echo "Warmed up {$warmedUp} evaluations";
```

### Get Cache Statistics

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

$stats = Eligify::getCacheStats();
// Returns:
// [
//     'driver' => 'redis',
//     'supports_tags' => true,
//     'evaluation_cache_enabled' => true,
//     'compilation_cache_enabled' => true,
//     'evaluation_ttl_seconds' => 3600,
//     'compilation_ttl_seconds' => 86400,
//     'cache_prefix' => 'eligify_eval',
// ]
```

### Bypass Cache for Specific Evaluation

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

$data = ['age' => 25, 'income' => 50000];

// Force fresh evaluation (bypass cache)
$result = Eligify::evaluate('loan-approval', $data, true, false);
//                                                           ↑
//                                            useCache = false
```

## Cache Drivers

### Recommended Drivers

For production, use cache drivers that support **tags**:

- ✅ **Redis** (recommended)
- ✅ **Memcached**
- ✅ **DynamoDB**
- ❌ File cache (no tag support)
- ❌ Database cache (no tag support)

### Why Tags Matter

Cache tags allow efficient invalidation:

- With tags: Only related cache entries are cleared
- Without tags: Entire cache may need to be flushed

### Configure Redis Cache

```bash
composer require predis/predis
```

Update `.env`:

```env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

## Performance Optimization

### Best Practices

1. **Use Redis or Memcached** for production
2. **Set appropriate TTL** values:
   - Evaluation cache: 30-60 minutes
   - Compilation cache: 12-24 hours
3. **Warm up cache** during deployment
4. **Monitor cache hit rates**
5. **Clear cache** after bulk criteria updates

### Cache Warming Strategy

Add to your deployment script:

```bash
#!/bin/bash

# Clear old cache
php artisan eligify:cache:clear --force

# Warm up active criteria
php artisan eligify:cache:warmup --all --samples=100

# Verify cache status
php artisan eligify:cache:stats
```

### Scheduled Cache Maintenance

Add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Clear cache daily at midnight
    $schedule->command('eligify:cache:clear --force')
        ->daily()
        ->at('00:00');

    // Warm up cache daily at 1 AM
    $schedule->command('eligify:cache:warmup --all')
        ->daily()
        ->at('01:00');
}
```

## Advanced Usage

### Custom Cache Keys

The cache service generates deterministic keys:

```php
use CleaniqueCoders\Eligify\Support\EligifyCache;

$cache = new EligifyCache();

// Get cache key for evaluation
$key = $cache->getEvaluationCacheKey($criteria, $data);

// Get cache key for compilation
$key = $cache->getCompilationCacheKey($criteria);
```

### Direct Cache Access

Access the cache service directly:

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

$cache = Eligify::getCache();

// Check if evaluation cache is enabled
if ($cache->isEvaluationCacheEnabled()) {
    // ...
}

// Check if compilation cache is enabled
if ($cache->isCompilationCacheEnabled()) {
    // ...
}

// Get TTL values
$evalTtl = $cache->getEvaluationCacheTtl(); // seconds
$compileTtl = $cache->getCompilationCacheTtl(); // seconds
```

## Troubleshooting

### Cache Not Working

1. **Check if caching is enabled:**

   ```bash
   php artisan eligify:cache:stats
   ```

2. **Verify cache driver:**

   ```bash
   php artisan config:show cache.default
   ```

3. **Test cache manually:**

   ```php
   Cache::put('test', 'value', 60);
   $value = Cache::get('test'); // Should return 'value'
   ```

### Cache Not Invalidating

1. **Check if observers are registered:**

   ```php
   // In config/eligify.php
   'audit' => [
       'enabled' => true, // Must be true for observers
   ],
   ```

2. **Manually invalidate:**

   ```php
   Eligify::invalidateCache('criteria-slug');
   ```

3. **Clear all cache:**

   ```bash
   php artisan eligify:cache:clear --force
   ```

### Performance Issues

1. **Enable compilation cache:**

   ```env
   ELIGIFY_PERF_COMPILE_RULES=true
   ```

2. **Increase cache TTL:**

   ```env
   ELIGIFY_EVALUATION_CACHE_TTL=120
   ELIGIFY_PERF_COMPILATION_CACHE_TTL=2880
   ```

3. **Use Redis instead of file cache**

4. **Warm up cache proactively**

## Examples

### High-Traffic Application

```php
// config/eligify.php
return [
    'evaluation' => [
        'cache_enabled' => true,
        'cache_ttl' => 120, // 2 hours
    ],
    'performance' => [
        'compile_rules' => true,
        'compilation_cache_ttl' => 2880, // 48 hours
    ],
];
```

### Real-Time Criteria Updates

```php
// When criteria change frequently, use shorter TTL
return [
    'evaluation' => [
        'cache_enabled' => true,
        'cache_ttl' => 5, // 5 minutes
    ],
];
```

### Disable Caching (Development)

```php
// .env
ELIGIFY_EVALUATION_CACHE_ENABLED=false
ELIGIFY_PERF_COMPILE_RULES=false
```

## See Also

- [Performance Benchmarking](performance-benchmarking.md)
- [Production Deployment](production-deployment.md)
- [Configuration Guide](configuration.md)
