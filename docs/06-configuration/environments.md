# Environment-Specific Configuration

Configure Eligify differently for development, staging, and production environments.

## Overview

Use environment-specific settings to optimize Eligify for each deployment stage.

## Development Environment

### Configuration

```php
// config/eligify.php
if (app()->environment('local')) {
    return [
        'debug' => true,
        'cache' => [
            'enabled' => false,
        ],
        'audit' => [
            'enabled' => true,
            'retention_days' => 7,
        ],
        'ui' => [
            'enabled' => true,
            'middleware' => ['web'],
        ],
    ];
}
```

### Features

- Debug mode enabled
- Caching disabled for fresh data
- Short audit retention
- Open UI access (no auth)
- Detailed error messages

## Staging Environment

### Configuration

```php
if (app()->environment('staging')) {
    return [
        'debug' => false,
        'cache' => [
            'enabled' => true,
            'ttl' => 300,
        ],
        'audit' => [
            'enabled' => true,
            'retention_days' => 30,
        ],
        'ui' => [
            'enabled' => true,
            'middleware' => ['web', 'auth'],
        ],
    ];
}
```

### Features

- Caching enabled with short TTL
- Moderate audit retention
- Authenticated UI access
- Production-like but not final

## Production Environment

### Configuration

```php
if (app()->environment('production')) {
    return [
        'debug' => false,
        'cache' => [
            'enabled' => true,
            'ttl' => 3600,
            'driver' => 'redis',
        ],
        'audit' => [
            'enabled' => true,
            'retention_days' => 2555, // 7 years
        ],
        'ui' => [
            'enabled' => env('ELIGIFY_UI_ENABLED', false),
            'middleware' => ['web', 'auth', 'can:manage-eligibility'],
        ],
        'rate_limiting' => [
            'enabled' => true,
            'max_attempts' => 60,
            'decay_minutes' => 1,
        ],
    ];
}
```

### Features

- Debug disabled
- Redis caching with longer TTL
- Long audit retention
- Restricted UI access
- Rate limiting enabled
- Optimized for performance

## Environment Variables

### Core Settings

```env
ELIGIFY_DEBUG=false
ELIGIFY_CACHE_ENABLED=true
ELIGIFY_CACHE_DRIVER=redis
ELIGIFY_CACHE_TTL=3600
```

### Audit Settings

```env
ELIGIFY_AUDIT_ENABLED=true
ELIGIFY_AUDIT_RETENTION_DAYS=90
ELIGIFY_AUDIT_DRIVER=database
```

### UI Settings

```env
ELIGIFY_UI_ENABLED=true
ELIGIFY_UI_ROUTE_PREFIX=eligify
ELIGIFY_UI_THEME=light
```

### Performance

```env
ELIGIFY_RATE_LIMIT_ENABLED=true
ELIGIFY_RATE_LIMIT_MAX=60
ELIGIFY_PARALLEL_EXECUTION=false
```

## Best Practices

### 1. Use Environment Detection

```php
$config = [
    'debug' => app()->environment('local'),
    'cache' => ['enabled' => !app()->environment('local')],
];
```

### 2. Separate Sensitive Config

```php
'api_key' => env('ELIGIFY_API_KEY'),
'encryption_key' => env('ELIGIFY_ENCRYPTION_KEY'),
```

### 3. Feature Flags

```php
'features' => [
    'playground' => env('ELIGIFY_PLAYGROUND_ENABLED', app()->environment('local')),
    'bulk_operations' => env('ELIGIFY_BULK_ENABLED', true),
],
```

## Related Documentation

- [Configuration Reference](reference.md) - All options
- [Environment Variables](environment-variables.md) - ENV vars
- [Production Guide](../10-deployment/production.md) - Production setup
