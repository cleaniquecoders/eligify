# Production Deployment Guide

This guide covers best practices for deploying Eligify in production environments.

## Table of Contents

- [System Requirements](#system-requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Database Setup](#database-setup)
- [Performance Optimization](#performance-optimization)
- [Security Considerations](#security-considerations)
- [Monitoring](#monitoring)
- [Backup and Recovery](#backup-and-recovery)
- [Scaling Strategies](#scaling-strategies)
- [Troubleshooting](#troubleshooting)

## System Requirements

### Minimum Requirements

- **PHP:** 8.4 or higher
- **Laravel:** 11.x or 12.x
- **Database:** MySQL 8.0+, PostgreSQL 13+, SQLite 3.35+, or SQL Server 2019+
- **Memory:** 512MB minimum, 2GB recommended
- **CPU:** 2 cores minimum, 4 cores recommended

### Recommended PHP Extensions

```bash
# Required extensions
php -m | grep -E 'pdo|mbstring|openssl|tokenizer|xml|ctype|json|bcmath'

# Optional but recommended
php -m | grep -E 'redis|igbinary|msgpack'
```

## Installation

### Via Composer

```bash
composer require cleaniquecoders/eligify
```

### Publish Assets

```bash
# Publish migrations
php artisan vendor:publish --tag="eligify-migrations"
php artisan migrate

# Publish configuration
php artisan vendor:publish --tag="eligify-config"

# (Optional) Publish views
php artisan vendor:publish --tag="eligify-views"
```

### Verify Installation

```bash
# Check package status
php artisan eligify

# Run package tests
composer test
```

## Configuration

### Environment Variables

Add these to your `.env` file:

```env
# Eligify Configuration
ELIGIFY_CACHE_ENABLED=true
ELIGIFY_CACHE_TTL=3600
ELIGIFY_CACHE_PREFIX=eligify

# Audit Configuration
ELIGIFY_AUDIT_ENABLED=true
ELIGIFY_AUDIT_RETENTION_DAYS=90
ELIGIFY_AUDIT_TRACK_IP=true
ELIGIFY_AUDIT_TRACK_USER_AGENT=true

# Performance
ELIGIFY_QUERY_OPTIMIZATION=true
ELIGIFY_EAGER_LOADING=true

# Background Processing
ELIGIFY_QUEUE_ENABLED=false
ELIGIFY_QUEUE_CONNECTION=redis
ELIGIFY_QUEUE_NAME=eligify
```

### Configuration File

Review and customize `config/eligify.php`:

```php
return [
    // Enable caching for improved performance
    'cache' => [
        'enabled' => env('ELIGIFY_CACHE_ENABLED', true),
        'ttl' => env('ELIGIFY_CACHE_TTL', 3600),
        'prefix' => env('ELIGIFY_CACHE_PREFIX', 'eligify'),
    ],

    // Audit logging configuration
    'audit' => [
        'enabled' => env('ELIGIFY_AUDIT_ENABLED', true),
        'retention_days' => env('ELIGIFY_AUDIT_RETENTION_DAYS', 90),
        'track_ip' => env('ELIGIFY_AUDIT_TRACK_IP', true),
        'track_user_agent' => env('ELIGIFY_AUDIT_TRACK_USER_AGENT', true),
    ],

    // Performance optimization
    'optimization' => [
        'query_optimization' => env('ELIGIFY_QUERY_OPTIMIZATION', true),
        'eager_loading' => env('ELIGIFY_EAGER_LOADING', true),
    ],

    // Background processing
    'queue' => [
        'enabled' => env('ELIGIFY_QUEUE_ENABLED', false),
        'connection' => env('ELIGIFY_QUEUE_CONNECTION', 'redis'),
        'queue' => env('ELIGIFY_QUEUE_NAME', 'eligify'),
    ],
];
```

## Database Setup

### Migration Strategy

```bash
# Review migrations before running
php artisan migrate:status

# Run migrations
php artisan migrate

# Rollback if needed (NOT recommended in production)
php artisan migrate:rollback
```

### Database Indexing

Ensure proper indexes are created for optimal performance:

```sql
-- Check existing indexes
SHOW INDEX FROM eligify_criteria;
SHOW INDEX FROM eligify_rules;
SHOW INDEX FROM eligify_evaluations;
SHOW INDEX FROM eligify_audit_logs;

-- The migrations should already include these indexes:
-- - criteria: id (primary), name (unique), created_at
-- - rules: id (primary), criteria_id (foreign), priority
-- - evaluations: id (primary), criteria_id (foreign), created_at
-- - audit_logs: id (primary), event (index), user_id, created_at
```

### Database Optimization

```sql
-- MySQL optimization
OPTIMIZE TABLE eligify_criteria;
OPTIMIZE TABLE eligify_rules;
OPTIMIZE TABLE eligify_evaluations;
OPTIMIZE TABLE eligify_audit_logs;

-- PostgreSQL optimization
VACUUM ANALYZE eligify_criteria;
VACUUM ANALYZE eligify_rules;
VACUUM ANALYZE eligify_evaluations;
VACUUM ANALYZE eligify_audit_logs;
```

## Performance Optimization

### 1. Enable Caching

```php
// config/eligify.php
'cache' => [
    'enabled' => true,
    'ttl' => 3600, // 1 hour
    'driver' => 'redis', // Use Redis for better performance
],
```

### 2. Use Redis for Cache

```bash
# Install Redis
composer require predis/predis

# Configure in .env
CACHE_DRIVER=redis
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

### 3. Enable Query Optimization

```php
// config/eligify.php
'optimization' => [
    'query_optimization' => true,
    'eager_loading' => true,
    'chunk_size' => 1000, // For batch operations
],
```

### 4. Use Queue for Heavy Operations

```php
// For large batch evaluations
use CleaniqueCoders\Eligify\Facades\Eligify;

$criteria = Eligify::criteria('Loan Approval');

// Queue the evaluation
dispatch(function () use ($criteria, $applicants) {
    $criteria->evaluateBatch($applicants);
})->onQueue('eligify');
```

### 5. Optimize PHP Configuration

```ini
; php.ini recommendations
memory_limit = 512M
max_execution_time = 300
opcache.enable = 1
opcache.memory_consumption = 256
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 20000
```

## Security Considerations

### 1. Database Security

```php
// Use prepared statements (already handled by Eloquent)
// Never concatenate user input directly

// Good (automatic with Eligify)
$criteria->addRule('income', '>=', $userInput);

// Never do this
// DB::raw("SELECT * FROM users WHERE income >= " . $userInput);
```

### 2. Audit Log Sanitization

```php
// config/eligify.php
'audit' => [
    'sensitive_fields' => [
        'password',
        'credit_card',
        'ssn',
        'api_key',
    ],
    'mask_sensitive_data' => true,
],
```

### 3. Rate Limiting

```php
// routes/web.php or routes/api.php
Route::middleware(['throttle:60,1'])->group(function () {
    Route::post('/evaluate', [EligibilityController::class, 'evaluate']);
});
```

### 4. Input Validation

```php
// Always validate input before evaluation
$validated = $request->validate([
    'income' => 'required|numeric|min:0',
    'age' => 'required|integer|min:18|max:120',
    'credit_score' => 'required|integer|min:300|max:850',
]);

$result = $criteria->evaluate($validated);
```

### 5. Authorization

```php
// Use policies to control access
class LoanPolicy
{
    use HasEligibility;

    public function evaluate(User $user, Loan $loan)
    {
        return $this->checkEligibility('loan_approval', [
            'income' => $loan->income,
            'credit_score' => $user->credit_score,
        ]);
    }
}
```

## Monitoring

### 1. Application Monitoring

```php
// Monitor evaluation performance
use Illuminate\Support\Facades\Log;

$start = microtime(true);
$result = $criteria->evaluate($data);
$duration = microtime(true) - $start;

if ($duration > 1.0) {
    Log::warning('Slow evaluation detected', [
        'criteria' => $criteria->name,
        'duration' => $duration,
        'rules_count' => count($criteria->rules),
    ]);
}
```

### 2. Health Checks

```php
// Create a health check endpoint
Route::get('/health/eligify', function () {
    try {
        // Check database connection
        DB::connection()->getPdo();

        // Check cache
        Cache::put('eligify_health_check', true, 10);
        $cacheWorking = Cache::get('eligify_health_check');

        return response()->json([
            'status' => 'healthy',
            'database' => 'connected',
            'cache' => $cacheWorking ? 'working' : 'failed',
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'unhealthy',
            'error' => $e->getMessage(),
        ], 503);
    }
});
```

### 3. Audit Log Monitoring

```bash
# Query recent failures
php artisan eligify:audit-query --event=evaluation_completed --failed

# Monitor audit log growth
php artisan eligify:audit-query --stats

# Set up automated cleanup
php artisan schedule:run
```

### 4. Performance Metrics

Track these key metrics:

- **Evaluation Time**: Average time to evaluate criteria
- **Success Rate**: Percentage of successful evaluations
- **Rule Execution**: Number of rules executed per evaluation
- **Cache Hit Rate**: Percentage of cache hits
- **Database Queries**: Number of queries per evaluation

## Backup and Recovery

### 1. Database Backups

```bash
# MySQL backup
mysqldump -u username -p database_name \
    eligify_criteria \
    eligify_rules \
    eligify_evaluations \
    eligify_audit_logs \
    > eligify_backup_$(date +%Y%m%d).sql

# PostgreSQL backup
pg_dump -U username -d database_name \
    -t eligify_criteria \
    -t eligify_rules \
    -t eligify_evaluations \
    -t eligify_audit_logs \
    > eligify_backup_$(date +%Y%m%d).sql
```

### 2. Configuration Backup

```bash
# Backup configuration files
tar -czf eligify_config_$(date +%Y%m%d).tar.gz \
    config/eligify.php \
    .env
```

### 3. Automated Backups

```bash
# Add to crontab
0 2 * * * /path/to/backup_script.sh
```

### 4. Recovery Procedure

```bash
# 1. Restore database
mysql -u username -p database_name < eligify_backup_20251028.sql

# 2. Restore configuration
tar -xzf eligify_config_20251028.tar.gz

# 3. Clear cache
php artisan cache:clear
php artisan config:clear

# 4. Verify
php artisan eligify
```

## Scaling Strategies

### 1. Horizontal Scaling

```
Load Balancer
    |
    +-- App Server 1 (Eligify)
    +-- App Server 2 (Eligify)
    +-- App Server 3 (Eligify)
            |
    Central Database (MySQL/PostgreSQL)
            |
    Central Cache (Redis)
```

### 2. Database Replication

```php
// config/database.php
'mysql' => [
    'read' => [
        'host' => [
            'replica1.example.com',
            'replica2.example.com',
        ],
    ],
    'write' => [
        'host' => ['master.example.com'],
    ],
    // ... other config
],
```

### 3. Queue Workers

```bash
# Run multiple queue workers
php artisan queue:work --queue=eligify --tries=3 &
php artisan queue:work --queue=eligify --tries=3 &
php artisan queue:work --queue=eligify --tries=3 &

# Using Supervisor for process management
# /etc/supervisor/conf.d/eligify-worker.conf
[program:eligify-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --queue=eligify --tries=3
autostart=true
autorestart=true
numprocs=3
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/eligify-worker.log
```

### 4. Caching Strategy

```php
// Multi-layer caching
'cache' => [
    'stores' => [
        'eligify_l1' => [ // Local cache
            'driver' => 'array',
        ],
        'eligify_l2' => [ // Redis cache
            'driver' => 'redis',
            'connection' => 'cache',
        ],
    ],
],
```

### 5. Database Sharding

For very large deployments, consider sharding audit logs:

```php
// Partition audit logs by month
CREATE TABLE eligify_audit_logs_2025_10 PARTITION OF eligify_audit_logs
    FOR VALUES FROM ('2025-10-01') TO ('2025-11-01');
```

## Troubleshooting

### Common Issues

#### 1. Slow Evaluations

**Symptoms:** Evaluations taking > 1 second

**Solutions:**

```bash
# Check indexes
php artisan eligify:audit-query --stats

# Enable query optimization
# In config/eligify.php
'optimization' => ['query_optimization' => true]

# Clear and rebuild cache
php artisan cache:clear
php artisan config:cache
```

#### 2. Memory Exhaustion

**Symptoms:** "Allowed memory size exhausted" errors

**Solutions:**

```php
// Use chunking for batch operations
$criteria->evaluateBatch($largeDataset, chunkSize: 100);

// Increase memory limit for specific operations
ini_set('memory_limit', '1G');
```

#### 3. Database Connection Issues

**Symptoms:** "Too many connections" errors

**Solutions:**

```bash
# Check active connections
SHOW PROCESSLIST; # MySQL
SELECT * FROM pg_stat_activity; # PostgreSQL

# Optimize connection pooling
# In config/database.php
'mysql' => [
    'pool_size' => 20,
    'max_connections' => 100,
],
```

#### 4. Audit Log Growth

**Symptoms:** Audit logs table growing rapidly

**Solutions:**

```bash
# Run cleanup manually
php artisan eligify:cleanup-audit --days=30

# Schedule automatic cleanup
# In app/Console/Kernel.php
$schedule->command('eligify:cleanup-audit --days=90')->daily();
```

### Debug Mode

Enable debug logging:

```php
// config/eligify.php
'debug' => [
    'enabled' => env('ELIGIFY_DEBUG', false),
    'log_channel' => 'eligify',
    'log_queries' => true,
    'log_evaluations' => true,
],
```

### Support Resources

- **Documentation:** `/docs` directory
- **Examples:** `/examples` directory
- **GitHub Issues:** <https://github.com/cleaniquecoders/eligify/issues>
- **Discussions:** <https://github.com/cleaniquecoders/eligify/discussions>

## Deployment Checklist

Before deploying to production:

- [ ] Configuration reviewed and optimized
- [ ] Migrations tested in staging
- [ ] Cache driver configured (Redis recommended)
- [ ] Queue workers configured (if using queues)
- [ ] Database indexes verified
- [ ] Backup strategy implemented
- [ ] Monitoring and alerts configured
- [ ] Security review completed
- [ ] Load testing performed
- [ ] Rollback plan documented
- [ ] Team trained on troubleshooting procedures

---

**Questions?** Open an issue or discussion on GitHub.
