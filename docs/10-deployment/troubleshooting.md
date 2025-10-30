# Troubleshooting

Common issues and solutions for Eligify deployments.

## Overview

This guide helps you diagnose and resolve common problems with Eligify in production environments.

## Installation Issues

### Package Not Found

**Problem:** `Package cleaniquecoders/eligify not found`

**Solution:**
```bash
# Clear composer cache
composer clear-cache

# Update composer
composer self-update

# Install package
composer require cleaniquecoders/eligify

# If using private repository
composer config repositories.eligify vcs https://github.com/cleaniquecoders/eligify
```

### Migration Failures

**Problem:** Migration fails with "Table already exists"

**Solution:**
```bash
# Check existing tables
php artisan db:show

# Rollback if needed
php artisan migrate:rollback --step=1

# Fresh migration
php artisan migrate:fresh

# Or drop specific tables
php artisan migrate:reset --path=vendor/cleaniquecoders/eligify/database/migrations
```

### Service Provider Not Loaded

**Problem:** `Class 'Eligify' not found`

**Solution:**
```php
// Clear application cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear

// Verify service provider is registered
// config/app.php (Laravel < 11)
'providers' => [
    CleaniqueCoders\Eligify\EligifyServiceProvider::class,
],

// For Laravel 11+, check bootstrap/providers.php
```

## Evaluation Issues

### Rules Not Evaluating Correctly

**Problem:** Rules pass/fail unexpectedly

**Solution:**
```php
use CleaniqueCoders\Eligify\Facades\Eligify;

// Enable debug mode
$result = Eligify::criteria('test')
    ->addRule('income', '>=', 3000)
    ->debug()
    ->evaluate($applicant);

// Check detailed output
dd($result->toArray());

// Verify data extraction
$extractor = new \CleaniqueCoders\Eligify\Support\Extractor();
$value = $extractor->extract($applicant, 'income');
dd($value); // Should be the actual income value
```

### Field Not Found

**Problem:** `Field 'xyz' not found on entity`

**Solution:**
```php
// Option 1: Use correct field name
$result = Eligify::criteria('test')
    ->addRule('correct_field_name', '>=', 3000)
    ->evaluate($applicant);

// Option 2: Use relationship dot notation
$result = Eligify::criteria('test')
    ->addRule('profile.income', '>=', 3000)
    ->evaluate($applicant);

// Option 3: Add custom extractor
class Applicant extends Model
{
    use HasExtractor;

    protected function extractIncome()
    {
        return $this->monthly_income * 12;
    }
}
```

### Operator Not Working

**Problem:** Custom operator doesn't work

**Solution:**
```php
// Register operator before use
use CleaniqueCoders\Eligify\Facades\Eligify;

Eligify::registerOperator('custom_op', function ($value, $threshold) {
    return $value > $threshold;
});

// Or in service provider
public function boot()
{
    Eligify::registerOperator('custom_op', function ($value, $threshold) {
        return $value > $threshold;
    });
}
```

## Performance Issues

### Slow Evaluations

**Problem:** Evaluations taking too long

**Solution:**
```php
// Enable caching
Eligify::criteria('slow_criteria')
    ->addRule('complex_field', '>=', 1000)
    ->cacheFor(3600)
    ->evaluate($applicant);

// Check for N+1 queries
DB::enableQueryLog();

$result = Eligify::criteria('test')
    ->addRule('profile.verified', '==', true)
    ->evaluate($applicant);

dd(DB::getQueryLog());

// Eager load relationships
$applicant = User::with('profile', 'employment')->find($id);
```

### High Memory Usage

**Problem:** Memory exhausted when processing many evaluations

**Solution:**
```php
// Use chunking instead of loading all at once
User::where('status', 'pending')
    ->chunk(100, function ($applicants) {
        foreach ($applicants as $applicant) {
            $result = Eligify::criteria('test')
                ->addRule('income', '>=', 3000)
                ->evaluate($applicant);

            // Process result
        }
    });

// Use lazy() for memory-efficient iteration
User::lazy(100)->each(function ($applicant) {
    // Evaluate
});

// Clear memory after each batch
unset($applicants);
gc_collect_cycles();
```

### Cache Not Working

**Problem:** Cache doesn't seem to work

**Solution:**
```bash
# Verify cache driver is configured
php artisan config:show cache

# Test cache connection
php artisan tinker
>>> Cache::put('test', 'value', 60);
>>> Cache::get('test');

# Clear and rebuild cache
php artisan cache:clear
php artisan config:cache

# For Redis
redis-cli FLUSHDB
```

```php
// Verify caching is enabled
// config/eligify.php
'cache' => [
    'enabled' => true,
    'driver' => 'redis',
    'ttl' => 3600,
],

// Force cache usage
$result = Eligify::criteria('test')
    ->addRule('income', '>=', 3000)
    ->cacheFor(3600)
    ->evaluate($applicant);

// Check if cached
$cacheKey = "eligify:test:{$applicant->id}";
dd(Cache::has($cacheKey));
```

## Database Issues

### Audit Log Not Created

**Problem:** Evaluations complete but no audit logs

**Solution:**
```php
// Verify audit is enabled
// config/eligify.php
'audit' => [
    'enabled' => true,
],

// Check database connection
php artisan db:show

// Verify table exists
Schema::hasTable('eligify_audits');

// Check for errors
try {
    $result = Eligify::criteria('test')
        ->addRule('income', '>=', 3000)
        ->evaluate($applicant);
} catch (\Exception $e) {
    Log::error('Audit failed', ['error' => $e->getMessage()]);
}

// Check audit logs
$audits = \CleaniqueCoders\Eligify\Models\Audit::all();
dd($audits);
```

### Migration Column Already Exists

**Problem:** Migration fails with "column already exists"

**Solution:**
```php
// Create migration to fix schema
php artisan make:migration fix_eligify_schema

// In migration
public function up()
{
    Schema::table('eligify_audits', function (Blueprint $table) {
        if (!Schema::hasColumn('eligify_audits', 'new_column')) {
            $table->string('new_column')->nullable();
        }
    });
}

// Or drop and recreate
php artisan migrate:fresh
```

## Queue Issues

### Jobs Not Processing

**Problem:** Queued evaluations don't execute

**Solution:**
```bash
# Start queue worker
php artisan queue:work eligibility

# Check queue status
php artisan queue:monitor eligibility

# Verify queue configuration
php artisan config:show queue

# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

### Job Timeout

**Problem:** Jobs timeout for long evaluations

**Solution:**
```php
// Increase timeout in config
// config/queue.php
'connections' => [
    'redis' => [
        'retry_after' => 300, // 5 minutes
        'block_for' => null,
    ],
],

// Or set timeout on job
class EvaluateCriteriaJob implements ShouldQueue
{
    public $timeout = 300; // 5 minutes

    public function handle()
    {
        // ...
    }
}
```

## API Issues

### 401 Unauthorized

**Problem:** API requests return 401

**Solution:**
```php
// Verify authentication middleware
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/api/eligify/evaluate', [EligibilityController::class, 'evaluate']);
});

// Check if user is authenticated
$user = auth('sanctum')->user();
dd($user);

// Verify token is valid
$token = $request->bearerToken();
$personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
dd($personalAccessToken);
```

### 403 Forbidden

**Problem:** User can't access eligibility features

**Solution:**
```php
// Check policy/gate
Gate::define('manage-eligibility', function ($user) {
    return $user->hasRole('admin');
});

// Or add to policy
class EligibilityPolicy
{
    public function evaluate(User $user)
    {
        return $user->hasPermission('evaluate-criteria');
    }
}

// Verify middleware
// config/eligify.php
'ui' => [
    'middleware' => ['web', 'auth', 'can:manage-eligibility'],
],
```

### Rate Limiting

**Problem:** Too many requests error

**Solution:**
```php
// Adjust rate limits
// app/Http/Kernel.php
protected $middlewareGroups = [
    'api' => [
        'throttle:eligibility', // Custom rate limit
    ],
];

// config/eligify.php or RouteServiceProvider
RateLimiter::for('eligibility', function (Request $request) {
    return Limit::perMinute(100) // Increase from 60 to 100
        ->by($request->user()?->id ?: $request->ip());
});
```

## Snapshot Issues

### Snapshot Not Captured

**Problem:** Snapshots not being created

**Solution:**
```php
// Verify snapshot is called
$snapshot = Eligify::snapshot($applicant, 'loan_application');
dd($snapshot); // Should not be null

// Check if data is captured
dd($snapshot->data);

// Verify model is serializable
$data = $applicant->toArray();
dd($data); // Should contain all relevant fields

// Check snapshot configuration
// config/eligify.php
'snapshot' => [
    'enabled' => true,
],
```

## UI Issues

### UI Not Loading

**Problem:** Eligify UI shows 404 or blank page

**Solution:**
```bash
# Publish UI assets
php artisan vendor:publish --tag=eligify-views
php artisan vendor:publish --tag=eligify-assets

# Clear view cache
php artisan view:clear

# Verify routes are registered
php artisan route:list | grep eligify
```

### Livewire Errors

**Problem:** Livewire components not working

**Solution:**
```bash
# Update Livewire
composer update livewire/livewire

# Clear Livewire cache
php artisan livewire:delete-component
php artisan livewire:discover

# Verify Livewire is published
php artisan livewire:publish --config
```

## Error Messages

### "Undefined array key"

**Problem:** PHP warning about undefined array key

**Solution:**
```php
// Use null coalescing operator
$value = $data['field'] ?? null;

// Or check existence
if (array_key_exists('field', $data)) {
    $value = $data['field'];
}

// Update criteria rules to handle missing data
$result = Eligify::criteria('test')
    ->addRule('optional_field', '>=', 3000)
    ->allowNull('optional_field')
    ->evaluate($applicant);
```

### "Class not found"

**Problem:** Eligify class not found errors

**Solution:**
```bash
# Regenerate autoload files
composer dump-autoload

# Clear all caches
php artisan optimize:clear

# Verify namespace
use CleaniqueCoders\Eligify\Facades\Eligify;

# Check if package is installed
composer show cleaniquecoders/eligify
```

## Debugging Tools

### Enable Debug Mode

```php
// In .env
ELIGIFY_DEBUG=true
APP_DEBUG=true

// Or in config
// config/eligify.php
'debug' => env('ELIGIFY_DEBUG', false),
```

### Log All Evaluations

```php
use Illuminate\Support\Facades\Log;

DB::listen(function ($query) {
    Log::debug('Query executed', [
        'sql' => $query->sql,
        'bindings' => $query->bindings,
        'time' => $query->time,
    ]);
});
```

### Dump Evaluation State

```php
$result = Eligify::criteria('test')
    ->addRule('income', '>=', 3000)
    ->tap(function ($builder) {
        dd($builder->getRules());
    })
    ->evaluate($applicant);
```

## Getting Help

### Check Logs

```bash
# Laravel log
tail -f storage/logs/laravel.log

# Filter for Eligify
tail -f storage/logs/laravel.log | grep -i eligify
```

### Run Diagnostics

```bash
# Create diagnostic command
php artisan make:command EligifyDiagnostics

# Implement diagnostic checks
public function handle()
{
    $this->info('Running Eligify diagnostics...');

    // Check database
    $this->checkDatabase();

    // Check cache
    $this->checkCache();

    // Check configuration
    $this->checkConfiguration();

    // Run test evaluation
    $this->testEvaluation();
}
```

### Common Log Patterns

```bash
# Find errors
grep "ERROR" storage/logs/laravel.log | grep -i eligify

# Find slow queries
grep "Slow query" storage/logs/laravel.log

# Find cache issues
grep "cache" storage/logs/laravel.log -i
```

## Production Debugging

### Telescope

```bash
# Install Telescope for debugging
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate

# View at /telescope
```

### Debugging Without Stopping Production

```php
// Log instead of dd()
Log::debug('Debug info', compact('applicant', 'result'));

// Use ray() for non-blocking debugging
ray($result)->red();

// Conditional debugging
if (app()->environment('local')) {
    dd($result);
}
```

## Related Documentation

- [Monitoring](monitoring.md)
- [Optimization](optimization.md)
- [Production Guide](production.md)
- [Testing](../09-testing/README.md)
