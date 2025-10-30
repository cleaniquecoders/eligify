# High-Traffic Optimization

Performance optimization techniques for high-traffic eligibility systems.

## Implementation

```php
use CleaniqueCoders\Eligify\Facades\Eligify;
use Illuminate\Support\Facades\Cache;

// Strategy 1: Aggressive caching
$result = Eligify::criteria('Loan Approval')
    ->addRule('income', '>=', 3000)
    ->addRule('credit_score', '>=', 650)
    ->cacheFor(3600) // Cache for 1 hour
    ->evaluate($applicant);

// Strategy 2: Queue for async processing
$result = Eligify::criteria('heavy_computation')
    ->addRule('complex_calculation', '>=', 100)
    ->evaluateAsync($applicant);

// Strategy 3: Batch evaluations
$applicants = User::where('status', 'pending')->get();
$results = Eligify::criteria('Loan Approval')
    ->evaluateBatch($applicants);

// Strategy 4: Pre-warm cache
Artisan::command('eligify:warm-cache', function () {
    $criteria = Eligify::criteria('Loan Approval')->getRules();
    $users = User::limit(1000)->get();

    foreach ($users as $user) {
        Eligify::criteria('Loan Approval')
            ->loadRules($criteria)
            ->cacheFor(7200)
            ->evaluate($user);
    }
});
```

## Related

- [Multi-Tenant](multi-tenant.md)
- [Performance Testing](../../09-testing/performance-testing.md)
