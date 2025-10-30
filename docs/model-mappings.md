# Creating Custom Model Mappings

Model mappings allow you to configure how data is extracted from Eloquent models for eligibility evaluation. This guide shows you how to create your own mapping classes.

## Quick Start

### 1. Create Your Mapping Class

Create a new class in your application (or extend the package):

```php
<?php

namespace App\Eligify\Mappings;

use CleaniqueCoders\Eligify\Mappings\AbstractModelMapping;

class CustomerModelMapping extends AbstractModelMapping
{
    /**
     * Get the model class this mapping is for
     */
    public function getModelClass(): string
    {
        return 'App\Models\Customer';
    }

    /**
     * Field mappings: original_field => new_field_name
     */
    protected array $fieldMappings = [
        'email_verified_at' => 'verified_date',
        'created_at' => 'signup_date',
    ];

    /**
     * Initialize computed fields
     */
    public function __construct()
    {
        $this->computedFields = [
            // Simple computed field
            'is_verified' => fn ($model) => !is_null($model->email_verified_at),

            // Relationship count
            'total_orders' => fn ($model) => $this->safeRelationshipCount($model, 'orders'),

            // Relationship sum
            'lifetime_value' => fn ($model) => $this->safeRelationshipSum($model, 'orders', 'total'),

            // Complex logic
            'customer_tier' => function ($model) {
                $value = $this->safeRelationshipSum($model, 'orders', 'total');
                return match (true) {
                    $value >= 10000 => 'vip',
                    $value >= 5000 => 'gold',
                    $value >= 1000 => 'silver',
                    default => 'standard'
                };
            },
        ];
    }
}
```

### 2. Register in Config

Add your mapping to `config/eligify.php`:

```php
'model_extraction' => [
    'model_mappings' => [
        'App\Models\User' => \CleaniqueCoders\Eligify\Mappings\UserModelMapping::class,
        'App\Models\Customer' => \App\Eligify\Mappings\CustomerModelMapping::class,
        'App\Models\Order' => \App\Eligify\Mappings\OrderModelMapping::class,
    ],
],
```

### 3. Use It

```php
use CleaniqueCoders\Eligify\Data\Extractor;

$customer = Customer::find(1);
$extractor = Extractor::forModel('App\Models\Customer');
$data = $extractor->extract($customer);

// Now use this data in eligibility rules
Eligify::criteria('vip_program')
    ->addRule('customer_tier', 'in', ['gold', 'vip'])
    ->addRule('lifetime_value', '>=', 5000)
    ->evaluate($data);
```

## Available Helper Methods

The `AbstractModelMapping` provides these helpers in your computed fields:

### Relationship Helpers

```php
// Check if relationship exists with scope
$this->safeRelationshipCheck($model, 'subscriptions', 'active')

// Count relationships
$this->safeRelationshipCount($model, 'orders')

// Sum relationship field
$this->safeRelationshipSum($model, 'orders', 'total')

// Average relationship field
$this->safeRelationshipAvg($model, 'orders', 'rating')

// Get max value
$this->safeRelationshipMax($model, 'orders', 'created_at')

// Get min value
$this->safeRelationshipMin($model, 'orders', 'total')

// Check if relationship method exists
$this->hasRelationship($model, 'orders')

// Get relationship data with default
$this->getRelationshipData($model, 'profile', [])
```

## Field Mapping Types

### 1. Simple Field Renaming

```php
protected array $fieldMappings = [
    'created_at' => 'registration_date',
    'email_verified_at' => 'verification_timestamp',
];
```

### 2. Computed Fields

```php
$this->computedFields = [
    // Boolean checks
    'is_active' => fn ($model) => $model->status === 'active',

    // Date calculations
    'account_age_days' => fn ($model) => now()->diffInDays($model->created_at),

    // Numeric calculations
    'average_rating' => fn ($model) => $this->safeRelationshipAvg($model, 'reviews', 'rating'),
];
```

### 3. Complex Logic

```php
$this->computedFields = [
    'eligibility_score' => function ($model) {
        $score = 0;

        // Add points for verified email
        if (!is_null($model->email_verified_at)) {
            $score += 20;
        }

        // Add points for orders
        $orderCount = $this->safeRelationshipCount($model, 'orders');
        $score += min($orderCount * 5, 50); // Max 50 points

        // Add points for account age
        $accountAgeDays = now()->diffInDays($model->created_at);
        $score += min($accountAgeDays / 10, 30); // Max 30 points

        return $score;
    },
];
```

## Best Practices

### ✅ DO

- Keep computed fields focused and single-purpose
- Use the safe helper methods to avoid errors
- Return appropriate default values (0, null, false)
- Document complex logic with comments
- Use meaningful field names

### ❌ DON'T

- Make database queries inside computed fields (use relationships)
- Throw exceptions (they're caught, but slow)
- Modify the model state
- Access undefined properties without checking
- Create circular dependencies

## Examples

### E-commerce Customer

```php
class CustomerModelMapping extends AbstractModelMapping
{
    public function getModelClass(): string
    {
        return 'App\Models\Customer';
    }

    public function __construct()
    {
        $this->fieldMappings = [
            'created_at' => 'customer_since',
        ];

        $this->computedFields = [
            'order_count' => fn ($m) => $this->safeRelationshipCount($m, 'orders'),
            'total_spent' => fn ($m) => $this->safeRelationshipSum($m, 'orders', 'total'),
            'avg_order_value' => fn ($m) => $this->safeRelationshipAvg($m, 'orders', 'total'),
            'last_purchase_days' => function ($m) {
                $date = $this->safeRelationshipMax($m, 'orders', 'created_at');
                return $date ? now()->diffInDays($date) : 999;
            },
            'is_vip' => fn ($m) => $this->safeRelationshipSum($m, 'orders', 'total') > 10000,
        ];
    }
}
```

### SaaS User

```php
class SaasUserMapping extends AbstractModelMapping
{
    public function getModelClass(): string
    {
        return 'App\Models\User';
    }

    public function __construct()
    {
        $this->computedFields = [
            'has_trial' => fn ($m) => !is_null($m->trial_ends_at ?? null),
            'trial_expired' => fn ($m) => ($m->trial_ends_at ?? now())->isPast(),
            'is_paying' => fn ($m) => $this->safeRelationshipCheck($m, 'subscriptions', 'active'),
            'mrr' => fn ($m) => $this->safeRelationshipSum($m, 'subscriptions', 'monthly_value'),
            'feature_usage_count' => fn ($m) => $this->safeRelationshipCount($m, 'usageRecords'),
        ];
    }
}
```

## Testing Your Mappings

```php
use Tests\TestCase;
use App\Models\Customer;
use App\Eligify\Mappings\CustomerModelMapping;
use CleaniqueCoders\Eligify\Data\Extractor;

class CustomerMappingTest extends TestCase
{
    public function test_customer_mapping_extracts_correct_data()
    {
        $customer = Customer::factory()
            ->has(Order::factory()->count(3))
            ->create();

        $extractor = Extractor::forModel('App\Models\Customer');
        $data = $extractor->extract($customer);

        $this->assertEquals(3, $data['order_count']);
        $this->assertArrayHasKey('total_spent', $data);
        $this->assertArrayHasKey('customer_since', $data);
    }
}
```

## Troubleshooting

### Mapping Not Applied

1. Check the model class name matches exactly (with namespace)
2. Verify the mapping class is registered in config
3. Clear config cache: `php artisan config:clear`

### Computed Field Returns Null

1. Check if the relationship exists on the model
2. Verify the relationship is loaded
3. Use the safe helper methods
4. Check for typos in field/relationship names

### Performance Issues

1. Eager load relationships before extraction
2. Avoid N+1 queries in computed fields
3. Use relationship aggregates instead of loading collections
4. Consider caching expensive computations

## Advanced: Dynamic Mappings

You can also create mappings dynamically without config:

```php
$extractor = new Extractor();
$extractor->setFieldMappings(['old_field' => 'new_field'])
    ->setComputedFields([
        'computed' => fn($m) => $m->field * 2
    ]);

$data = $extractor->extract($model);
```
