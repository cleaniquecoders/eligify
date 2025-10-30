# Data Extractor

The Data Extractor is responsible for extracting and normalizing data from various sources for evaluation.

## Overview

The Data Extractor:

- Handles multiple data types (arrays, models, objects)
- Uses Model Mappers for complex transformations
- Supports nested data access
- Normalizes data for rule evaluation

## Supported Data Types

### 1. Arrays

Direct pass-through for array data:

```php
$data = [
    'income' => 5000,
    'credit_score' => 750,
    'active_loans' => 1,
];

$result = $criteria->evaluate($data);
```

### 2. Eloquent Models

Extract from Laravel models:

```php
$user = User::find(1);
$result = $criteria->evaluate($user);
```

**Without Mapper:**
Uses `toArray()` method:

```php
$data = $user->toArray();
```

**With Mapper:**
Uses custom mapper for transformation:

```php
// Automatically uses UserMapper if registered
$mapper = new UserMapper($user);
$data = $mapper->toArray();
```

### 3. Custom Objects

Extract from plain PHP objects:

```php
class LoanApplicant
{
    public int $income = 5000;
    public int $creditScore = 750;
}

$applicant = new LoanApplicant();
$result = $criteria->evaluate($applicant);
```

### 4. Collections

Extract from collections:

```php
$users = User::where('status', 'active')->get();
$results = $criteria->evaluateMany($users);
```

## Using Model Mappings

### Why Use Mappings?

Model mappings transform complex model data into evaluation-ready format:

```php
namespace App\Eligify\Mappings;

use CleaniqueCoders\Eligify\Data\Mappings\AbstractModelMapping;

class UserMapping extends AbstractModelMapping
{
    protected array $fieldMappings = [
        'annual_income' => 'income',
        'date_of_birth' => 'age',
    ];

    protected array $computedFields = [
        'account_age_days' => fn($model) => $model->created_at->diffInDays(now()),
            'average_order_value' => $this->model->orders()->avg('total'),

            // Relationship data
            'total_orders' => $this->model->orders()->count(),
            'has_premium' => $this->model->subscription?->isPremium() ?? false,

            // Complex logic
            'credit_score' => $this->getCreditScore(),
        ];
    }

    protected function getCreditScore(): int
    {
        // Complex calculation
        return $this->model->creditReports()->latest()->first()?->score ?? 0;
    }
}
```

### Register Mapping

```php
// config/eligify.php
'model_extraction' => [
    'model_mappings' => [
        \App\Models\User::class => \App\Eligify\Mappings\UserMapping::class,
        \App\Models\LoanApplication::class => \App\Eligify\Mappings\LoanApplicationMapping::class,
    ],
],
```

### Manual Extraction

```php
use CleaniqueCoders\Eligify\Data\Extractor;

$extractor = new Extractor();
$data = $extractor->extract($user);
```

## Nested Data Access

Access nested data using dot notation:

```php
$data = [
    'user' => [
        'profile' => [
            'address' => [
                'country' => 'US',
            ],
        ],
    ],
];

// Access nested field
->addRule('user.profile.address.country', '==', 'US')
```

## Relationship Data

### One-to-Many

```php
class UserMapping extends AbstractModelMapping
{
    protected array $computedFields = [
        'orders_count' => fn($model) => $model->orders()->count(),
        'orders_total' => fn($model) => $model->orders()->sum('amount'),
        'latest_order_date' => fn($model) => $model->orders()->latest()->first()?->created_at,
    ];
}
```

### Many-to-Many

```php
protected array $computedFields = [
    'roles' => fn($model) => $model->roles->pluck('name')->toArray(),
    'has_admin_role' => fn($model) => $model->roles()->where('name', 'admin')->exists(),
    'permissions_count' => fn($model) => $model->roles()->with('permissions')->get()
        ->pluck('permissions')->flatten()->unique('id')->count(),
];
```

### Belongs To

```php
protected array $computedFields = [
    'company_name' => fn($model) => $model->company?->name,
    'company_size' => fn($model) => $model->company?->employee_count,
    'company_industry' => fn($model) => $model->company?->industry,
];
```

## Computed Values

### Time-Based Calculations

```php
protected array $computedFields = [
    'account_age_days' => fn($model) => $model->created_at->diffInDays(now()),
    'days_since_last_login' => fn($model) => $model->last_login_at?->diffInDays(now()) ?? 999,
    'subscription_days_remaining' => fn($model) => $model->subscription_ends_at?->diffInDays(now()) ?? 0,
];
```

### Aggregations

```php
protected array $computedFields = [
    'average_order_value' => fn($model) => $model->orders()->avg('total'),
    'total_spent' => fn($model) => $model->orders()->sum('total'),
    'order_count' => fn($model) => $model->orders()->count(),
    'repeat_customer' => fn($model) => $model->orders()->count() > 1,
];
```

### Financial Calculations

```php
class UserMapping extends AbstractModelMapping
{
    protected array $computedFields = [
        'debt_to_income_ratio' => fn($model) => $this->calculateDebtToIncome($model),
        'monthly_payment' => fn($model) => $this->calculateMonthlyPayment($model),
        'disposable_income' => fn($model) => $model->income - $this->calculateExpenses($model),
    ];

    protected function calculateDebtToIncome($model): float
    {
        $monthlyIncome = $model->annual_income / 12;
        $totalDebt = $model->loans()->sum('monthly_payment');

        return $monthlyIncome > 0 ? ($totalDebt / $monthlyIncome) : 0;
    }
}
```

## Caching Extracted Data

Cache expensive extractions:

```php
use Illuminate\Support\Facades\Cache;

class UserMapping extends AbstractModelMapping
{
    public function configure(Extractor $extractor): Extractor
    {
        $extractor = parent::configure($extractor);

        $extractor->setComputedFields([
            'credit_score' => fn($model) => Cache::remember(
                "user_credit_score:{$model->id}",
                300, // 5 minutes
                fn() => $this->getCreditScore($model)
            ),
        ]);

        return $extractor;
    }

    protected function getCreditScore($model): int
    {
        // Expensive calculation
        return $model->creditReports()->latest()->first()?->score ?? 0;
    }
}
```

## Default Values

Provide defaults for missing data using field mappings:

```php
protected array $fieldMappings = [
    'annual_income' => 'income',
    'credit_rating' => 'credit_score',
];

protected array $computedFields = [
    'income' => fn($model) => $model->annual_income ?? 0,
    'credit_score' => fn($model) => $model->credit_rating ?? 300,
    'phone' => fn($model) => $model->phone ?? 'N/A',
];
```

## Type Casting

Ensure correct data types with computed fields:

```php
protected array $computedFields = [
    'age' => fn($model) => (int) $model->age,
    'income' => fn($model) => (float) $model->income,
    'verified' => fn($model) => (bool) $model->verified,
    'tags' => fn($model) => (array) $model->tags,
];
```

## Testing Data Extraction

```php
use CleaniqueCoders\Eligify\Data\Extractor;

test('data extractor extracts user data correctly', function () {
    $user = User::factory()->create([
        'annual_income' => 5000,
        'age' => 30,
    ]);

    $data = Extractor::forModel(User::class)->extract($user);

    expect($data)->toHaveKey('user.income');
    expect($data['user.income'])->toBe(5000);
});

test('mapping transforms model data', function () {
    $user = User::factory()->create([
        'created_at' => now()->subDays(100),
    ]);

    $data = Extractor::forModel(User::class)->extract($user);

    expect($data)->toHaveKey('account_age_days');
    expect($data['account_age_days'])->toBe(100);
});
```

## Performance Tips

### 1. Eager Load Relationships

```php
$user = User::with(['orders', 'subscription', 'company'])->find(1);
$data = Extractor::forModel(User::class)->extract($user);
```

### 2. Use Database Calculations

```php
// Good: Database aggregation
protected array $computedFields = [
    'total_orders' => fn($model) => $model->orders()->count(),
];

// Bad: Loading all records
protected array $computedFields = [
    'total_orders' => fn($model) => count($model->orders),
];
```

### 3. Cache Expensive Operations

```php
protected array $computedFields = [
    'risk_score' => fn($model) => Cache::remember(
        "risk_score:{$model->id}",
        3600,
        fn() => $this->calculateComplexRiskScore($model)
    ),
];

protected function calculateComplexRiskScore($model): float
{
    // Complex calculation
    return 0.0;
}
```

## Related Documentation

- [Model Mapping](../model-mapping/) - Complete mapping guide
- [Extraction Guide](guide.md) - Decision flowchart and best practices
- [Snapshot System](../snapshot/) - Data snapshots
- [Main Data Management](../README.md) - Back to overview
