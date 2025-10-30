# Monitoring

Monitor and observe your Eligify deployment in production.

## Overview

Comprehensive monitoring ensures your eligibility system runs smoothly, with insights into performance, errors, and usage patterns.

## Application Logging

### Basic Logging Configuration

```php
// config/eligify.php
return [
    'logging' => [
        'enabled' => true,
        'channel' => env('ELIGIFY_LOG_CHANNEL', 'stack'),
        'level' => env('ELIGIFY_LOG_LEVEL', 'info'),
        'include_context' => true,
    ],
];
```

### Evaluation Logging

```php
use CleaniqueCoders\Eligify\Facades\Eligify;
use Illuminate\Support\Facades\Log;

// Log evaluation attempts
Eligify::criteria('loan_approval')
    ->addRule('income', '>=', 3000)
    ->onEvaluate(function ($entity, $result) {
        Log::info('Eligibility evaluated', [
            'criteria' => 'loan_approval',
            'entity_id' => $entity->id,
            'passed' => $result->passed(),
            'score' => $result->score,
        ]);
    })
    ->evaluate($applicant);
```

### Error Logging

```php
use Illuminate\Support\Facades\Log;

try {
    $result = Eligify::criteria('test')
        ->addRule('income', '>=', 3000)
        ->evaluate($applicant);
} catch (\Exception $e) {
    Log::error('Eligibility evaluation failed', [
        'criteria' => 'test',
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);

    throw $e;
}
```

## Audit Tracking

### Enable Comprehensive Auditing

```php
// config/eligify.php
return [
    'audit' => [
        'enabled' => true,
        'include_user' => true,
        'include_snapshot' => true,
        'include_ip' => true,
        'include_user_agent' => true,
    ],
];
```

### Query Audit Logs

```php
use CleaniqueCoders\Eligify\Models\Audit;

// Recent evaluations
$recentAudits = Audit::with('user')
    ->latest()
    ->limit(100)
    ->get();

// Failed evaluations
$failures = Audit::where('passed', false)
    ->where('created_at', '>=', now()->subDay())
    ->get();

// Specific criteria performance
$loanApprovals = Audit::where('criteria_name', 'loan_approval')
    ->where('created_at', '>=', now()->subWeek())
    ->selectRaw('DATE(created_at) as date, COUNT(*) as count, AVG(score) as avg_score')
    ->groupBy('date')
    ->get();
```

## Performance Metrics

### Track Evaluation Duration

```php
use CleaniqueCoders\Eligify\Facades\Eligify;
use Illuminate\Support\Facades\Cache;

// Track timing
$start = microtime(true);

$result = Eligify::criteria('loan_approval')
    ->addRule('income', '>=', 3000)
    ->evaluate($applicant);

$duration = (microtime(true) - $start) * 1000; // milliseconds

// Store metrics
Cache::put("metrics:evaluation:duration:{$criteria}", $duration, 3600);

if ($duration > 100) {
    Log::warning('Slow evaluation detected', [
        'criteria' => 'loan_approval',
        'duration_ms' => $duration,
    ]);
}
```

### Cache Performance

```php
use Illuminate\Support\Facades\Cache;

// Track cache hits/misses
$cacheKey = "eligify:loan_approval:{$applicant->id}";

if (Cache::has($cacheKey)) {
    // Cache hit
    Cache::increment('metrics:cache:hits');
    $result = Cache::get($cacheKey);
} else {
    // Cache miss
    Cache::increment('metrics:cache:misses');
    $result = evaluateCriteria($applicant);
    Cache::put($cacheKey, $result, 3600);
}

// Calculate hit rate
$hits = Cache::get('metrics:cache:hits', 0);
$misses = Cache::get('metrics:cache:misses', 0);
$hitRate = $hits / ($hits + $misses) * 100;
```

## Health Checks

### Application Health Endpoint

```php
// routes/web.php
use Illuminate\Support\Facades\Route;
use CleaniqueCoders\Eligify\Facades\Eligify;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

Route::get('/health/eligify', function () {
    $health = [
        'status' => 'healthy',
        'timestamp' => now()->toIso8601String(),
        'checks' => [],
    ];

    // Database connectivity
    try {
        DB::connection()->getPdo();
        $health['checks']['database'] = 'ok';
    } catch (\Exception $e) {
        $health['checks']['database'] = 'failed';
        $health['status'] = 'unhealthy';
    }

    // Cache connectivity
    try {
        Cache::put('health_check', true, 10);
        $health['checks']['cache'] = Cache::get('health_check') ? 'ok' : 'failed';
    } catch (\Exception $e) {
        $health['checks']['cache'] = 'failed';
        $health['status'] = 'unhealthy';
    }

    // Evaluation test
    try {
        $testEntity = new class {
            public $test_value = 100;
        };

        $result = Eligify::criteria('health_check')
            ->addRule('test_value', '>=', 50)
            ->withoutCache()
            ->evaluate($testEntity);

        $health['checks']['evaluation'] = $result->passed() ? 'ok' : 'failed';
    } catch (\Exception $e) {
        $health['checks']['evaluation'] = 'failed';
        $health['status'] = 'unhealthy';
    }

    $statusCode = $health['status'] === 'healthy' ? 200 : 503;

    return response()->json($health, $statusCode);
});
```

### Queue Health

```php
use Illuminate\Support\Facades\Queue;

Route::get('/health/queues', function () {
    $queueSize = Queue::size('eligibility');

    return response()->json([
        'queue' => 'eligibility',
        'size' => $queueSize,
        'status' => $queueSize < 1000 ? 'ok' : 'warning',
    ]);
});
```

## Metrics Dashboard

### Create Metrics Collector

```php
// app/Services/EligifyMetrics.php
namespace App\Services;

use CleaniqueCoders\Eligify\Models\Audit;
use Illuminate\Support\Facades\Cache;

class EligifyMetrics
{
    public function collect(): array
    {
        return [
            'evaluations' => $this->evaluationMetrics(),
            'performance' => $this->performanceMetrics(),
            'cache' => $this->cacheMetrics(),
            'errors' => $this->errorMetrics(),
        ];
    }

    protected function evaluationMetrics(): array
    {
        $today = Audit::whereDate('created_at', today())->count();
        $passed = Audit::whereDate('created_at', today())
            ->where('passed', true)
            ->count();

        return [
            'total_today' => $today,
            'passed_today' => $passed,
            'failed_today' => $today - $passed,
            'pass_rate' => $today > 0 ? ($passed / $today) * 100 : 0,
        ];
    }

    protected function performanceMetrics(): array
    {
        return [
            'avg_duration_ms' => Cache::get('metrics:avg_duration', 0),
            'max_duration_ms' => Cache::get('metrics:max_duration', 0),
            'slow_queries' => Cache::get('metrics:slow_queries', 0),
        ];
    }

    protected function cacheMetrics(): array
    {
        $hits = Cache::get('metrics:cache:hits', 0);
        $misses = Cache::get('metrics:cache:misses', 0);

        return [
            'hits' => $hits,
            'misses' => $misses,
            'hit_rate' => $hits + $misses > 0
                ? ($hits / ($hits + $misses)) * 100
                : 0,
        ];
    }

    protected function errorMetrics(): array
    {
        return [
            'errors_24h' => Cache::get('metrics:errors:24h', 0),
            'last_error' => Cache::get('metrics:last_error'),
        ];
    }
}
```

### Metrics Endpoint

```php
use App\Services\EligifyMetrics;

Route::get('/metrics/eligify', function (EligifyMetrics $metrics) {
    return response()->json($metrics->collect());
})->middleware('auth:sanctum');
```

## Integration with Monitoring Tools

### Laravel Telescope

```php
// config/telescope.php
'watchers' => [
    Watchers\QueryWatcher::class => [
        'enabled' => env('TELESCOPE_QUERY_WATCHER', true),
        'slow' => 100, // Log queries slower than 100ms
    ],

    Watchers\CacheWatcher::class => [
        'enabled' => env('TELESCOPE_CACHE_WATCHER', true),
    ],

    Watchers\LogWatcher::class => [
        'enabled' => env('TELESCOPE_LOG_WATCHER', true),
        'level' => 'warning',
    ],
],
```

### Laravel Pulse

```php
// config/pulse.php
return [
    'recorders' => [
        Recorders\CacheInteractions::class => [
            'enabled' => env('PULSE_CACHE_INTERACTIONS_ENABLED', true),
        ],

        Recorders\SlowQueries::class => [
            'enabled' => env('PULSE_SLOW_QUERIES_ENABLED', true),
            'threshold' => 1000, // 1 second
        ],
    ],
];
```

### Sentry Integration

```php
// config/sentry.php
'integrations' => [
    new \Sentry\Integration\IgnoreErrorsIntegration([
        'ignore_exceptions' => [
            // Ignore expected exceptions
        ],
    ]),
],

// Log to Sentry
use Sentry\State\Scope;

Eligify::criteria('test')
    ->onError(function ($exception, $entity) {
        \Sentry\captureException($exception, function (Scope $scope) use ($entity) {
            $scope->setContext('eligify', [
                'entity_type' => get_class($entity),
                'entity_id' => $entity->id,
            ]);
        });
    })
    ->evaluate($applicant);
```

### New Relic

```php
// Track custom metrics
if (extension_loaded('newrelic')) {
    newrelic_custom_metric('Custom/Eligify/Evaluations', 1);
    newrelic_custom_metric('Custom/Eligify/PassRate', $passRate);
}
```

## Alerting

### Set Up Alerts

```php
// app/Services/AlertService.php
namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\EligifyAlert;

class AlertService
{
    public function checkThresholds(): void
    {
        // High failure rate
        $failureRate = $this->getFailureRate();
        if ($failureRate > 50) {
            $this->sendAlert(
                'High failure rate detected',
                "Current failure rate: {$failureRate}%"
            );
        }

        // Slow evaluations
        $avgDuration = Cache::get('metrics:avg_duration', 0);
        if ($avgDuration > 500) {
            $this->sendAlert(
                'Slow evaluations detected',
                "Average duration: {$avgDuration}ms"
            );
        }

        // Low cache hit rate
        $hitRate = $this->getCacheHitRate();
        if ($hitRate < 70) {
            $this->sendAlert(
                'Low cache hit rate',
                "Cache hit rate: {$hitRate}%"
            );
        }
    }

    protected function sendAlert(string $title, string $message): void
    {
        Log::alert($title, ['message' => $message]);

        Notification::route('slack', config('services.slack.alerts_webhook'))
            ->notify(new EligifyAlert($title, $message));
    }
}
```

### Schedule Alert Checks

```php
// app/Console/Kernel.php
use App\Services\AlertService;

protected function schedule(Schedule $schedule): void
{
    $schedule->call(function (AlertService $alerts) {
        $alerts->checkThresholds();
    })->everyFiveMinutes();
}
```

## Custom Monitoring Dashboard

### Create Dashboard Controller

```php
// app/Http/Controllers/EligifyDashboardController.php
namespace App\Http\Controllers;

use CleaniqueCoders\Eligify\Models\Audit;
use App\Services\EligifyMetrics;

class EligifyDashboardController extends Controller
{
    public function index(EligifyMetrics $metrics)
    {
        return view('eligify.dashboard', [
            'metrics' => $metrics->collect(),
            'recentEvaluations' => Audit::with('user')
                ->latest()
                ->limit(20)
                ->get(),
            'topCriteria' => $this->getTopCriteria(),
        ]);
    }

    protected function getTopCriteria(): array
    {
        return Audit::selectRaw('criteria_name, COUNT(*) as count')
            ->whereDate('created_at', today())
            ->groupBy('criteria_name')
            ->orderByDesc('count')
            ->limit(10)
            ->pluck('count', 'criteria_name')
            ->toArray();
    }
}
```

## Log Aggregation

### ELK Stack Integration

```php
// config/logging.php
'channels' => [
    'eligify' => [
        'driver' => 'monolog',
        'handler' => Monolog\Handler\ElasticsearchHandler::class,
        'handler_with' => [
            'client' => Elasticsearch\ClientBuilder::create()
                ->setHosts([env('ELASTICSEARCH_HOST')])
                ->build(),
            'options' => [
                'index' => 'eligify-logs',
                'type' => '_doc',
            ],
        ],
    ],
],
```

### CloudWatch Integration

```php
// config/logging.php
'channels' => [
    'eligify' => [
        'driver' => 'custom',
        'via' => \App\Logging\CloudWatchLogger::class,
        'level' => 'info',
        'group' => env('CLOUDWATCH_LOG_GROUP', 'eligify'),
        'stream' => env('CLOUDWATCH_LOG_STREAM', 'production'),
    ],
],
```

## Monitoring Commands

```bash
# View recent metrics
php artisan eligify:metrics --period=24h

# Check system health
php artisan eligify:health-check

# Generate performance report
php artisan eligify:performance-report --output=report.pdf

# Monitor in real-time
php artisan eligify:monitor --watch
```

## Best Practices

### Structured Logging

```php
Log::info('Eligibility evaluated', [
    'criteria' => $criteriaName,
    'entity_type' => get_class($entity),
    'entity_id' => $entity->id,
    'passed' => $result->passed(),
    'score' => $result->score,
    'duration_ms' => $duration,
    'cached' => $fromCache,
]);
```

### Context Enrichment

```php
use Illuminate\Support\Facades\Log;

Log::withContext([
    'request_id' => request()->id(),
    'user_id' => auth()->id(),
    'ip' => request()->ip(),
]);
```

### Metric Collection

```php
// Collect metrics in middleware
class EligifyMetricsMiddleware
{
    public function handle($request, Closure $next)
    {
        $start = microtime(true);

        $response = $next($request);

        $duration = (microtime(true) - $start) * 1000;

        Cache::increment('metrics:requests:total');
        Cache::set('metrics:requests:last_duration', $duration);

        return $response;
    }
}
```

## Related Documentation

- [Optimization](optimization.md)
- [Troubleshooting](troubleshooting.md)
- [Production Guide](production.md)
