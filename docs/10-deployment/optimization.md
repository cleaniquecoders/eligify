# Optimization

Performance optimization techniques for Eligify in production.

## Overview

Optimize your Eligify implementation for maximum performance, scalability, and efficiency in production environments.

## Caching Strategies

### Enable Result Caching

```php
// config/eligify.php
return [
    'cache' => [
        'enabled' => true,
        'driver' => env('ELIGIFY_CACHE_DRIVER', 'redis'),
        'ttl' => env('ELIGIFY_CACHE_TTL', 3600),
        'prefix' => 'eligify',
    ],
];
```

### Per-Criteria Cache Configuration

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

// Cache for 1 hour
$result = Eligify::criteria('Loan Approval')
    ->addRule('income', '>=', 3000)
    ->cacheFor(3600)
    ->evaluate($applicant);

// Cache until manually cleared
$result = Eligify::criteria('static_rules')
    ->addRule('age', '>=', 18)
    ->cacheForever()
    ->evaluate($applicant);

// Skip cache for specific evaluation
$result = Eligify::criteria('dynamic')
    ->addRule('current_balance', '>=', 1000)
    ->withoutCache()
    ->evaluate($applicant);
```

### Cache Warming

```php
use CleaniqueCoders\Eligify\Facades\Eligify;
use App\Models\User;

// Warm cache for common scenarios
Artisan::command('eligify:warm-cache', function () {
    $commonApplicants = User::where('status', 'pending')->limit(100)->get();

    foreach ($commonApplicants as $applicant) {
        Eligify::criteria('Loan Approval')
            ->addRule('income', '>=', 3000)
            ->addRule('credit_score', '>=', 650)
            ->cacheFor(7200)
            ->evaluate($applicant);
    }

    $this->info('Cache warmed for 100 applicants');
});
```

### Smart Cache Invalidation

```php
use CleaniqueCoders\Eligify\Facades\Eligify;
use App\Models\User;

// Invalidate when user data changes
User::updated(function (User $user) {
    Eligify::clearCache('loan_approval', $user->id);
});

// Invalidate all criteria cache
Eligify::clearAllCache();

// Invalidate specific criteria
Eligify::clearCache('loan_approval');
```

## Database Optimization

### Index Strategy

```php
// database/migrations/xxxx_optimize_eligify_tables.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Optimize audits table
        Schema::table('eligify_audits', function (Blueprint $table) {
            $table->index(['criteria_name', 'created_at']);
            $table->index(['entity_type', 'entity_id']);
            $table->index(['passed', 'created_at']);
            $table->index('user_id');
        });

        // Optimize criteria table
        Schema::table('eligify_criteria', function (Blueprint $table) {
            $table->index(['name', 'is_active']);
            $table->index('is_active');
        });

        // Optimize snapshots table
        Schema::table('eligify_snapshots', function (Blueprint $table) {
            $table->index(['entity_type', 'entity_id', 'context']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('eligify_audits', function (Blueprint $table) {
            $table->dropIndex(['criteria_name', 'created_at']);
            $table->dropIndex(['entity_type', 'entity_id']);
            $table->dropIndex(['passed', 'created_at']);
            $table->dropIndex(['user_id']);
        });

        Schema::table('eligify_criteria', function (Blueprint $table) {
            $table->dropIndex(['name', 'is_active']);
            $table->dropIndex(['is_active']);
        });

        Schema::table('eligify_snapshots', function (Blueprint $table) {
            $table->dropIndex(['entity_type', 'entity_id', 'context']);
            $table->dropIndex(['created_at']);
        });
    }
};
```

### Query Optimization

```php
use CleaniqueCoders\Eligify\Models\Audit;

// Eager load relationships
$audits = Audit::with(['user', 'entity'])
    ->where('criteria_name', 'loan_approval')
    ->get();

// Use chunking for large datasets
Audit::where('created_at', '<', now()->subDays(90))
    ->chunk(100, function ($audits) {
        foreach ($audits as $audit) {
            // Process old audits
        }
    });

// Select only needed columns
$audits = Audit::select('id', 'criteria_name', 'passed', 'score')
    ->where('criteria_name', 'loan_approval')
    ->get();
```

### Audit Retention Policy

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    // Archive old audits daily
    $schedule->command('eligify:archive-audits --days=90')
        ->daily()
        ->at('02:00');

    // Delete very old audits
    $schedule->command('eligify:prune-audits --days=365')
        ->weekly()
        ->sundays()
        ->at('03:00');
}
```

```php
// app/Console/Commands/ArchiveAuditsCommand.php
use CleaniqueCoders\Eligify\Models\Audit;

class ArchiveAuditsCommand extends Command
{
    public function handle(): int
    {
        $days = $this->option('days', 90);

        $audits = Audit::where('created_at', '<', now()->subDays($days))
            ->where('archived', false)
            ->chunk(1000, function ($audits) {
                foreach ($audits as $audit) {
                    // Archive to cold storage
                    Storage::disk('s3')->put(
                        "audits/{$audit->id}.json",
                        $audit->toJson()
                    );

                    $audit->update(['archived' => true]);
                }
            });

        return self::SUCCESS;
    }
}
```

## Evaluation Optimization

### Lazy Loading Rules

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

// Load criteria from database only when needed
$criteria = Eligify::criteria('Loan Approval')
    ->lazy()
    ->evaluate($applicant);

// Preload commonly used criteria
Eligify::preload(['loan_approval', 'credit_check', 'income_verification']);
```

### Rule Short-Circuiting

```php
// config/eligify.php
return [
    'evaluation' => [
        'short_circuit' => true, // Stop on first failure
        'parallel' => false,      // Evaluate rules in parallel (requires additional setup)
    ],
];
```

### Batch Evaluations

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

// Evaluate multiple entities efficiently
$applicants = User::where('status', 'pending')->get();

$results = Eligify::criteria('Loan Approval')
    ->addRule('income', '>=', 3000)
    ->addRule('credit_score', '>=', 650)
    ->evaluateBatch($applicants);

// Process results
foreach ($results as $applicantId => $result) {
    if ($result->passed()) {
        // Approve
    }
}
```

## Queue Optimization

### Async Evaluation

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

// Queue evaluation for background processing
Eligify::criteria('heavy_computation')
    ->addRule('complex_calculation', '>=', 100)
    ->evaluateAsync($applicant);

// Configure queue
// config/queue.php
'connections' => [
    'eligify' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'eligibility',
        'retry_after' => 90,
        'block_for' => null,
    ],
],
```

### Job Batching

```php
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use CleaniqueCoders\Eligify\Jobs\EvaluateCriteriaJob;

$applicants = User::where('status', 'pending')->get();

$batch = Bus::batch(
    $applicants->map(fn ($applicant) =>
        new EvaluateCriteriaJob('loan_approval', $applicant)
    )
)->then(function (Batch $batch) {
    // All evaluations completed
})->catch(function (Batch $batch, Throwable $e) {
    // Handle failures
})->dispatch();
```

## Memory Optimization

### Stream Large Datasets

```php
use CleaniqueCoders\Eligify\Facades\Eligify;
use App\Models\User;

// Process large datasets without loading all into memory
User::where('status', 'pending')
    ->lazy(100)
    ->each(function (User $applicant) {
        $result = Eligify::criteria('Loan Approval')
            ->addRule('income', '>=', 3000)
            ->evaluate($applicant);

        // Process result
    });
```

### Optimize Snapshot Storage

```php
// config/eligify.php
return [
    'snapshot' => [
        'compress' => true,        // Compress snapshot data
        'selective' => true,       // Only store relevant fields
        'ttl' => 2592000,         // 30 days
        'fields' => [             // Whitelist specific fields
            'User' => ['id', 'name', 'income', 'credit_score'],
        ],
    ],
];
```

## API Optimization

### Response Caching

```php
use Illuminate\Support\Facades\Cache;

Route::get('/api/criteria/{name}/evaluate/{user}', function ($name, $user) {
    $cacheKey = "evaluation:{$name}:{$user}";

    return Cache::remember($cacheKey, 3600, function () use ($name, $user) {
        $applicant = User::findOrFail($user);

        return Eligify::criteria($name)
            ->loadFromDatabase()
            ->evaluate($applicant);
    });
});
```

### Pagination

```php
use CleaniqueCoders\Eligify\Models\Audit;

// Paginate large result sets
Route::get('/api/audits', function () {
    return Audit::with('entity:id,name')
        ->select('id', 'criteria_name', 'passed', 'score', 'created_at')
        ->latest()
        ->paginate(50);
});
```

### Rate Limiting

```php
use Illuminate\Support\Facades\RateLimiter;

// Limit evaluations per user
RateLimiter::for('evaluations', function (Request $request) {
    return Limit::perMinute(10)
        ->by($request->user()?->id ?: $request->ip())
        ->response(function () {
            return response()->json([
                'message' => 'Too many evaluation requests',
            ], 429);
        });
});

Route::middleware('throttle:evaluations')->group(function () {
    Route::post('/api/evaluate', [EligibilityController::class, 'evaluate']);
});
```

## CDN & Asset Optimization

### Static Asset Caching

```php
// Publish UI assets with versioning
php artisan vendor:publish --tag=eligify-assets --force

// Use CDN for assets
// config/eligify.php
'ui' => [
    'assets' => [
        'cdn' => env('ELIGIFY_CDN_URL', null),
        'version' => '1.0.0',
    ],
],
```

## Monitoring & Profiling

### Query Monitoring

```php
use Illuminate\Support\Facades\DB;

if (app()->environment('local')) {
    DB::listen(function ($query) {
        if ($query->time > 1000) { // Slow queries > 1 second
            logger()->warning('Slow Eligify query', [
                'sql' => $query->sql,
                'time' => $query->time,
                'bindings' => $query->bindings,
            ]);
        }
    });
}
```

### Performance Metrics

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

// Track evaluation performance
$start = microtime(true);

$result = Eligify::criteria('Loan Approval')
    ->addRule('income', '>=', 3000)
    ->evaluate($applicant);

$duration = (microtime(true) - $start) * 1000;

if ($duration > 100) { // > 100ms
    logger()->warning('Slow evaluation', [
        'criteria' => 'loan_approval',
        'duration_ms' => $duration,
    ]);
}
```

## Production Checklist

### Configuration

- [ ] Enable Redis caching
- [ ] Set appropriate cache TTL values
- [ ] Configure audit retention policies
- [ ] Optimize database indexes
- [ ] Enable query caching

### Performance

- [ ] Warm caches before deployment
- [ ] Configure queue workers for async processing
- [ ] Set up CDN for static assets
- [ ] Enable response caching
- [ ] Implement rate limiting

### Monitoring

- [ ] Set up slow query logging
- [ ] Monitor cache hit rates
- [ ] Track evaluation performance
- [ ] Monitor queue depths
- [ ] Set up alerting for anomalies

## Benchmarking

```bash
# Run performance tests
php artisan test --filter=PerformanceTest

# Profile memory usage
php artisan eligify:profile --criteria=loan_approval --iterations=1000

# Benchmark evaluation speed
php artisan eligify:benchmark --scenario=high-traffic
```

## Related Documentation

- [Production Guide](production.md)
- [Monitoring](monitoring.md)
- [Troubleshooting](troubleshooting.md)
- [Performance Testing](../09-testing/performance-testing.md)
