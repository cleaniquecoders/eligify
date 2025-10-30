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

## Using Model Mappers

### Why Use Mappers?

Mappers transform complex model data into evaluation-ready format:

```php
namespace App\Eligify\Mappers;

use CleaniqueCoders\Eligify\Support\Mappers\BaseMapper;

class UserMapper extends BaseMapper
{
    protected function mapping(): array
    {
        return [
            // Direct attributes
            'income' => $this->model->annual_income,
            'age' => $this->model->age,

            // Computed values
            'account_age_days' => $this->model->created_at->diffInDays(now()),
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

### Register Mapper

```php
// config/eligify.php
'mappers' => [
    \App\Models\User::class => \App\Eligify\Mappers\UserMapper::class,
    \App\Models\LoanApplication::class => \App\Eligify\Mappers\LoanApplicationMapper::class,
],
```

### Manual Extraction

```php
use CleaniqueCoders\Eligify\Support\DataExtractor;

$extractor = new DataExtractor();
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
class UserMapper extends BaseMapper
{
    protected function mapping(): array
    {
        return [
            'orders_count' => $this->model->orders()->count(),
            'orders_total' => $this->model->orders()->sum('amount'),
            'latest_order_date' => $this->model->orders()->latest()->first()?->created_at,
        ];
    }
}
```

### Many-to-Many

```php
protected function mapping(): array
{
    return [
        'roles' => $this->model->roles->pluck('name')->toArray(),
        'has_admin_role' => $this->model->roles()->where('name', 'admin')->exists(),
        'permissions_count' => $this->model->roles()->with('permissions')->get()
            ->pluck('permissions')->flatten()->unique('id')->count(),
    ];
}
```

### Belongs To

```php
protected function mapping(): array
{
    return [
        'company_name' => $this->model->company?->name,
        'company_size' => $this->model->company?->employee_count,
        'company_industry' => $this->model->company?->industry,
    ];
}
```

## Computed Values

### Time-Based Calculations

```php
protected function mapping(): array
{
    return [
        'account_age_days' => $this->model->created_at->diffInDays(now()),
        'days_since_last_login' => $this->model->last_login_at?->diffInDays(now()) ?? 999,
        'subscription_days_remaining' => $this->model->subscription_ends_at?->diffInDays(now()) ?? 0,
    ];
}
```

### Aggregations

```php
protected function mapping(): array
{
    return [
        'average_order_value' => $this->model->orders()->avg('total'),
        'total_spent' => $this->model->orders()->sum('total'),
        'order_count' => $this->model->orders()->count(),
        'repeat_customer' => $this->model->orders()->count() > 1,
    ];
}
```

### Financial Calculations

```php
protected function mapping(): array
{
    return [
        'debt_to_income_ratio' => $this->calculateDebtToIncome(),
        'monthly_payment' => $this->calculateMonthlyPayment(),
        'disposable_income' => $this->model->income - $this->calculateExpenses(),
    ];
}

protected function calculateDebtToIncome(): float
{
    $monthlyIncome = $this->model->annual_income / 12;
    $totalDebt = $this->model->loans()->sum('monthly_payment');

    return $monthlyIncome > 0 ? ($totalDebt / $monthlyIncome) : 0;
}
```

## Caching Extracted Data

Cache expensive extractions:

```php
class UserMapper extends BaseMapper
{
    protected function mapping(): array
    {
        return Cache::remember(
            "user_eligibility_data:{$this->model->id}",
            300, // 5 minutes
            fn() => [
                'credit_score' => $this->getCreditScore(),
                'risk_assessment' => $this->calculateRisk(),
                // ... expensive calculations
            ]
        );
    }
}
```

## Data Validation

Validate extracted data:

```php
class UserMapper extends BaseMapper
{
    protected function mapping(): array
    {
        $data = [
            'income' => $this->model->income,
            'age' => $this->model->age,
        ];

        // Validate
        validator($data, [
            'income' => 'required|numeric|min:0',
            'age' => 'required|integer|min:0|max:150',
        ])->validate();

        return $data;
    }
}
```

## Default Values

Provide defaults for missing data:

```php
protected function mapping(): array
{
    return [
        'income' => $this->model->income ?? 0,
        'credit_score' => $this->model->credit_score ?? 300,
        'phone' => $this->model->phone ?? 'N/A',
    ];
}
```

## Type Casting

Ensure correct data types:

```php
protected function mapping(): array
{
    return [
        'age' => (int) $this->model->age,
        'income' => (float) $this->model->income,
        'verified' => (bool) $this->model->verified,
        'tags' => (array) $this->model->tags,
    ];
}
```

## Testing Data Extraction

```php
test('data extractor extracts user data correctly', function () {
    $user = User::factory()->create([
        'income' => 5000,
        'age' => 30,
    ]);

    $extractor = new DataExtractor();
    $data = $extractor->extract($user);

    expect($data)->toHaveKey('income');
    expect($data['income'])->toBe(5000);
    expect($data['age'])->toBe(30);
});

test('mapper transforms model data', function () {
    $user = User::factory()->create([
        'created_at' => now()->subDays(100),
    ]);

    $mapper = new UserMapper($user);
    $data = $mapper->toArray();

    expect($data)->toHaveKey('account_age_days');
    expect($data['account_age_days'])->toBe(100);
});
```

## Performance Tips

### 1. Eager Load Relationships

```php
$user = User::with(['orders', 'subscription', 'company'])->find(1);
$result = $criteria->evaluate($user);
```

### 2. Use Database Calculations

```php
// Good: Database aggregation
'total_orders' => $this->model->orders()->count(),

// Bad: Loading all records
'total_orders' => count($this->model->orders),
```

### 3. Cache Expensive Operations

```php
protected function mapping(): array
{
    return [
        'risk_score' => $this->getCachedRiskScore(),
    ];
}

protected function getCachedRiskScore(): float
{
    return Cache::remember(
        "risk_score:{$this->model->id}",
        3600,
        fn() => $this->calculateComplexRiskScore()
    );
}
```

## Related Documentation

- [Model Mapping](model-mapping/) - Complete mapping guide
- [Snapshot System](snapshot/) - Data snapshots
- [Dynamic Values](dynamic-values.md) - Dynamic data input
- [Relationship Mapping](model-mapping/relationship-mapping.md) - Handling relationships
