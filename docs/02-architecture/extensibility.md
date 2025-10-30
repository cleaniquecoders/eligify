# Extensibility

Eligify is designed to be extended and customized to meet your specific needs. This guide shows you how to extend various components of the system.

## Extension Points

Eligify provides multiple extension points:

1. **Custom Operators** - Add new comparison operators
2. **Custom Scorers** - Implement new scoring methods
3. **Custom Mappers** - Create data transformation logic
4. **Event Listeners** - React to system events
5. **Middleware** - Intercept evaluation flow
6. **Custom Actions** - Add new action classes
7. **UI Components** - Extend the web interface

## Custom Operators

Create operators for specialized comparison logic.

### Step 1: Create Operator Class

```php
namespace App\Eligify\Operators;

use CleaniqueCoders\Eligify\Engine\Contracts\OperatorInterface;

class ContainsWordOperator implements OperatorInterface
{
    public function evaluate(mixed $actual, mixed $expected): bool
    {
        if (!is_string($actual)) {
            return false;
        }

        $words = explode(' ', strtolower($actual));
        $searchWord = strtolower($expected);

        return in_array($searchWord, $words);
    }

    public function validate(mixed $value): bool
    {
        return is_string($value);
    }

    public function getDescription(): string
    {
        return 'Checks if a string contains a specific word';
    }
}
```

### Step 2: Register Operator

```php
// config/eligify.php
'operators' => [
    'contains_word' => \App\Eligify\Operators\ContainsWordOperator::class,
],
```

### Step 3: Use Your Operator

```php
Eligify::criteria('content_check')
    ->addRule('description', 'contains_word', 'premium')
    ->evaluate($product);
```

## Custom Scorers

Implement custom scoring algorithms.

### Step 1: Create Scorer Class

```php
namespace App\Eligify\Scorers;

use CleaniqueCoders\Eligify\Engine\Contracts\ScorerInterface;

class TieredScorer implements ScorerInterface
{
    public function calculate(array $rules, array $results): float
    {
        $passedCount = count(array_filter($results));
        $totalCount = count($rules);
        $percentage = ($passedCount / $totalCount) * 100;

        // Return tiered scores: 0, 50, 75, 100
        return match(true) {
            $percentage >= 90 => 100,
            $percentage >= 70 => 75,
            $percentage >= 50 => 50,
            default => 0,
        };
    }

    public function isPassing(float $score, float $threshold): bool
    {
        return $score >= $threshold;
    }

    public function getDescription(): string
    {
        return 'Tiered scoring: 100, 75, 50, or 0 based on pass percentage';
    }
}
```

### Step 2: Register Scorer

```php
// config/eligify.php
'scoring' => [
    'methods' => [
        'weighted' => WeightedScorer::class,
        'pass_fail' => PassFailScorer::class,
        'tiered' => \App\Eligify\Scorers\TieredScorer::class,
    ],
],
```

### Step 3: Use Your Scorer

```php
Eligify::criteria('membership_tier')
    ->setScoring('tiered')
    ->addRule('points', '>=', 1000)
    ->addRule('months_active', '>=', 12)
    ->evaluate($user);
```

## Custom Mappers

Transform complex models into evaluation-ready data.

### Step 1: Create Mapper Class

```php
namespace App\Eligify\Mappers;

use CleaniqueCoders\Eligify\Support\Mappers\BaseMapper;
use App\Models\LoanApplication;

class LoanApplicationMapper extends BaseMapper
{
    protected function mapping(): array
    {
        return [
            // Direct attributes
            'amount' => $this->model->amount,
            'term_months' => $this->model->term_months,

            // Computed values
            'monthly_payment' => $this->calculateMonthlyPayment(),
            'debt_to_income' => $this->calculateDebtToIncome(),

            // Relationship data
            'applicant_credit_score' => $this->model->applicant->credit_score,
            'applicant_income' => $this->model->applicant->annual_income,
            'existing_loans_count' => $this->model->applicant->loans()->active()->count(),

            // Aggregated data
            'total_existing_debt' => $this->model->applicant->loans()->sum('balance'),
            'average_payment_history' => $this->calculateAveragePaymentHistory(),

            // Boolean flags
            'has_collateral' => $this->model->collateral()->exists(),
            'is_first_time_borrower' => $this->model->applicant->loans()->count() === 0,
        ];
    }

    protected function calculateMonthlyPayment(): float
    {
        $principal = $this->model->amount;
        $rate = $this->model->interest_rate / 12 / 100;
        $months = $this->model->term_months;

        return $principal * ($rate * pow(1 + $rate, $months)) / (pow(1 + $rate, $months) - 1);
    }

    protected function calculateDebtToIncome(): float
    {
        $monthlyIncome = $this->model->applicant->annual_income / 12;
        $totalDebt = $this->model->applicant->loans()->sum('monthly_payment');
        $newPayment = $this->calculateMonthlyPayment();

        return ($totalDebt + $newPayment) / $monthlyIncome;
    }

    protected function calculateAveragePaymentHistory(): float
    {
        return $this->model->applicant->payments()
            ->whereNotNull('paid_at')
            ->avg('days_until_paid') ?? 0;
    }
}
```

### Step 2: Register Mapper

```php
// config/eligify.php
'mappers' => [
    \App\Models\LoanApplication::class => \App\Eligify\Mappers\LoanApplicationMapper::class,
],
```

### Step 3: Use Mapper Automatically

```php
$application = LoanApplication::find(1);

// Mapper is automatically used
$result = Eligify::criteria('loan_approval')->evaluate($application);
```

## Event Listeners

React to evaluation events for custom logic.

### Step 1: Create Listener

```php
namespace App\Listeners;

use CleaniqueCoders\Eligify\Events\EvaluationCompleted;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class NotifyOnEligibilityChange
{
    public function handle(EvaluationCompleted $event): void
    {
        $criteria = $event->criteria;
        $subject = $event->subject;
        $result = $event->result;

        // Check previous evaluation
        $previous = $subject->evaluations()
            ->where('criteria_id', $criteria->id)
            ->latest()
            ->skip(1)
            ->first();

        // Status changed?
        if ($previous && $previous->passed !== $result->passed()) {
            $this->notifyStatusChange($subject, $result);
        }

        // Log all evaluations
        Log::info('Eligibility evaluated', [
            'criteria' => $criteria->name,
            'subject' => get_class($subject) . ':' . $subject->id,
            'passed' => $result->passed(),
            'score' => $result->score(),
        ]);
    }

    protected function notifyStatusChange($subject, $result): void
    {
        if ($result->passed()) {
            Notification::send($subject->user, new EligibilityGranted($result));
        } else {
            Notification::send($subject->user, new EligibilityRevoked($result));
        }
    }
}
```

### Step 2: Register Listener

```php
// app/Providers/EventServiceProvider.php
use CleaniqueCoders\Eligify\Events\EvaluationCompleted;
use App\Listeners\NotifyOnEligibilityChange;

protected $listen = [
    EvaluationCompleted::class => [
        NotifyOnEligibilityChange::class,
    ],
];
```

## Middleware

Intercept and modify the evaluation flow.

### Step 1: Create Middleware

```php
namespace App\Eligify\Middleware;

use Closure;
use CleaniqueCoders\Eligify\Models\Criteria;

class RateLimitEvaluations
{
    public function handle(Criteria $criteria, mixed $subject, Closure $next)
    {
        $key = sprintf(
            'eligify:ratelimit:%s:%s',
            get_class($subject),
            $subject->id ?? 'guest'
        );

        if (cache()->has($key)) {
            throw new TooManyEvaluationsException(
                'Rate limit exceeded. Please try again later.'
            );
        }

        // Set rate limit (5 evaluations per minute)
        cache()->put($key, true, now()->addMinute());

        return $next($criteria, $subject);
    }
}
```

### Step 2: Register Middleware

```php
// config/eligify.php
'middleware' => [
    \App\Eligify\Middleware\RateLimitEvaluations::class,
    \App\Eligify\Middleware\LogEvaluations::class,
],
```

## Custom Actions

Create reusable action classes.

### Example: Bulk Evaluation Action

```php
namespace App\Eligify\Actions;

use Illuminate\Support\Collection;
use CleaniqueCoders\Eligify\Models\Criteria;

class BulkEvaluate
{
    public function execute(Criteria $criteria, Collection $subjects): Collection
    {
        return $subjects->map(function ($subject) use ($criteria) {
            return [
                'subject' => $subject,
                'result' => $criteria->evaluate($subject),
            ];
        });
    }

    public function executeAsync(Criteria $criteria, Collection $subjects): void
    {
        $subjects->chunk(100)->each(function ($chunk) use ($criteria) {
            EvaluateBulkJob::dispatch($criteria, $chunk);
        });
    }
}
```

### Usage

```php
$action = new BulkEvaluate();
$results = $action->execute($criteria, $users);

foreach ($results as $item) {
    if ($item['result']->passed()) {
        // Handle passed
    }
}
```

## UI Extensions

Extend the web interface with custom components.

### Custom Dashboard Widget

```php
// app/Eligify/Widgets/CustomStatsWidget.php
namespace App\Eligify\Widgets;

use Livewire\Component;

class CustomStatsWidget extends Component
{
    public function render()
    {
        $stats = [
            'evaluations_today' => Evaluation::whereDate('created_at', today())->count(),
            'pass_rate' => Evaluation::where('passed', true)->count() / Evaluation::count() * 100,
        ];

        return view('eligify.widgets.custom-stats', compact('stats'));
    }
}
```

### Register Widget

```php
// config/eligify.php
'ui' => [
    'dashboard_widgets' => [
        \App\Eligify\Widgets\CustomStatsWidget::class,
    ],
],
```

## Package Extension

Create a package that extends Eligify.

### Example: Eligify ML Package

```php
// packages/eligify-ml/src/EligifyMLServiceProvider.php
namespace YourVendor\EligifyML;

use Illuminate\Support\ServiceProvider;

class EligifyMLServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Add ML-based operator
        config([
            'eligify.operators.ml_predict' => MLPredictOperator::class,
        ]);

        // Add ML scorer
        config([
            'eligify.scoring.methods.ml_score' => MLScorer::class,
        ]);
    }
}
```

## Testing Extensions

Test your custom extensions thoroughly.

### Testing Custom Operators

```php
use App\Eligify\Operators\ContainsWordOperator;

test('contains word operator works correctly', function () {
    $operator = new ContainsWordOperator();

    expect($operator->evaluate('This is premium content', 'premium'))->toBeTrue();
    expect($operator->evaluate('This is basic content', 'premium'))->toBeFalse();
    expect($operator->evaluate('PREMIUM CONTENT', 'premium'))->toBeTrue(); // Case insensitive
});
```

### Testing Custom Mappers

```php
use App\Eligify\Mappers\LoanApplicationMapper;

test('loan application mapper extracts correct data', function () {
    $application = LoanApplication::factory()->create([
        'amount' => 10000,
        'term_months' => 12,
    ]);

    $mapper = new LoanApplicationMapper($application);
    $data = $mapper->toArray();

    expect($data)->toHaveKey('monthly_payment');
    expect($data)->toHaveKey('debt_to_income');
    expect($data['amount'])->toBe(10000);
});
```

## Best Practices

### 1. Follow SOLID Principles

```php
// Good: Single responsibility
class EmailNotificationListener
{
    public function handle(EvaluationCompleted $event): void
    {
        // Only handles email notifications
    }
}

// Bad: Multiple responsibilities
class NotificationListener
{
    public function handle(EvaluationCompleted $event): void
    {
        // Sends email, SMS, push notifications, logs, updates cache...
    }
}
```

### 2. Use Dependency Injection

```php
// Good
class CustomScorer implements ScorerInterface
{
    public function __construct(
        protected ConfigRepository $config,
        protected CacheManager $cache
    ) {}
}

// Bad
class CustomScorer implements ScorerInterface
{
    public function calculate(): float
    {
        $config = config('app.scoring'); // Direct dependency
    }
}
```

### 3. Provide Clear Documentation

```php
/**
 * Tiered Scorer
 *
 * Scores eligibility in tiers based on pass percentage:
 * - 90-100% passing: Score 100
 * - 70-89% passing: Score 75
 * - 50-69% passing: Score 50
 * - <50% passing: Score 0
 *
 * Example:
 * ```
 * ->setScoring('tiered')
 * ```
 */
class TieredScorer implements ScorerInterface
{
    // ...
}
```

### 4. Handle Edge Cases

```php
class CustomOperator implements OperatorInterface
{
    public function evaluate(mixed $actual, mixed $expected): bool
    {
        // Handle null values
        if ($actual === null || $expected === null) {
            return false;
        }

        // Handle type mismatches
        if (gettype($actual) !== gettype($expected)) {
            return false;
        }

        // Your logic here
        return $actual === $expected;
    }
}
```

## Related Documentation

- [Design Patterns](design-patterns.md) - Patterns for extensions
- [Package Structure](package-structure.md) - Where to add code
- [Request Lifecycle](request-lifecycle.md) - How to intercept flow
- [Advanced Features](../07-advanced-features/) - More extension examples
