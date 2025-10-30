# Configuration

This section covers all configuration options available in Eligify.

## Overview

Eligify is highly configurable through the `config/eligify.php` file and environment variables.

## Documentation in this Section

- **[Configuration Reference](reference.md)** - Complete configuration options
- **[Environment Variables](environment-variables.md)** - ENV-based configuration
- **[Operators](operators.md)** - Custom operator configuration
- **[Presets](presets.md)** - Pre-defined configuration presets
- **[Environments](environments.md)** - Dev, staging, production configs

## Publishing Configuration

```bash
php artisan vendor:publish --tag="eligify-config"
```

This creates `config/eligify.php` in your application.

## Key Configuration Areas

### 1. Operators

Define available comparison operators:

```php
'operators' => [
    'equals' => '==',
    'not_equals' => '!=',
    'greater_than' => '>',
    'less_than' => '<',
    // Custom operators
],
```

### 2. Scoring Methods

Configure how scores are calculated:

```php
'scoring' => [
    'default_method' => 'weighted',
    'passing_threshold' => 70,
    'methods' => ['weighted', 'pass_fail', 'sum', 'average'],
],
```

### 3. Audit Logging

Control audit trail behavior:

```php
'audit' => [
    'enabled' => true,
    'log_level' => 'info',
    'retention_days' => 365,
],
```

### 4. Caching

Performance optimization settings:

```php
'cache' => [
    'enabled' => true,
    'ttl' => 3600,
    'driver' => 'redis',
],
```

### 5. UI Options

Control the web interface:

```php
'ui' => [
    'enabled' => true,
    'middleware' => ['web', 'auth'],
    'route_prefix' => 'eligify',
],
```

## Environment-Based Configuration

Use environment variables for sensitive or environment-specific settings:

```env
ELIGIFY_CACHE_ENABLED=true
ELIGIFY_CACHE_TTL=3600
ELIGIFY_AUDIT_RETENTION_DAYS=365
```

## Configuration Best Practices

1. **Use environment variables** for secrets and environment-specific values
2. **Keep defaults sensible** in the config file
3. **Document custom operators** and their behavior
4. **Version control** your config file (excluding secrets)
5. **Test configurations** in staging before production

## Related Sections

- [Advanced Features](../07-advanced-features/) - Cache configuration details
- [Deployment](../10-deployment/) - Production configuration
- [Security](../11-security/) - Security-related configuration
