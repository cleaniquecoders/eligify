# Advanced Features

This guide covers advanced features and patterns for power users of Eligify.

## Table of Contents

- [Advanced Rule Engine](#advanced-rule-engine)
- [Custom Operators](#custom-operators)
- [Custom Scoring Methods](#custom-scoring-methods)
- [Dynamic Criteria](#dynamic-criteria)
- [Conditional Rules](#conditional-rules)
- [Rule Groups](#rule-groups)
- [Workflow Automation](#workflow-automation)
- [Event System](#event-system)
- [Performance Optimization](#performance-optimization)
- [Multi-Tenancy](#multi-tenancy)

## Advanced Rule Engine

Eligify includes two rule engines:

### Basic Rule Engine

Default engine for most use cases:

```php
use CleaniqueCoders\Eligify\Engine\RuleEngine;

$engine = new RuleEngine();
$result = $engine->evaluate($criteria, $data);
```

### Advanced Rule Engine

Optimized engine with additional features:

```php
use CleaniqueCoders\Eligify\Engine\AdvancedRuleEngine;

$engine = new AdvancedRuleEngine();

// Configure options
$engine->setOption('parallel_execution', true)
       ->setOption('cache_results', true)
       ->setOption('optimization_level', 'aggressive');

$result = $engine->evaluate($criteria, $data);

// Get execution plan
$plan = $engine->getExecutionPlan($criteria);

// Get performance metrics
$metrics = $engine->getMetrics();
```

**Advanced Engine Options:**

```php
$engine->setOptions([
    'parallel_execution' => true,       // Run rules in parallel
    'cache_results' => true,            // Cache evaluation results
    'optimization_level' => 'aggressive', // none, basic, aggressive
    'short_circuit' => true,            // Stop on first failure (pass_fail mode)
    'rule_compilation' => true,         // Pre-compile rules
    'batch_size' => 100,                // Batch processing size
]);
```

**Using Advanced Engine by Default:**

```php
// In AppServiceProvider
use CleaniqueCoders\Eligify\Engine\RuleEngine;
use CleaniqueCoders\Eligify\Engine\AdvancedRuleEngine;

public function register()
{
    $this->app->bind(RuleEngine::class, AdvancedRuleEngine::class);
}
```

## Custom Operators

Create custom operators for specialized logic.

### Define Custom Operator

```php
// config/eligify.php
'operators' => [
    // ... existing operators

    'divisible_by' => [
        'name' => 'Divisible By',
        'description' => 'Check if number is divisible by value',
        'types' => ['integer', 'numeric'],
        'example' => "addRule('quantity', 'divisible_by', 12)",
    ],

    'is_business_day' => [
        'name' => 'Is Business Day',
        'description' => 'Check if date falls on a business day',
        'types' => ['date'],
        'example' => "addRule('appointment_date', 'is_business_day', true)",
    ],

    'within_distance' => [
        'name' => 'Within Distance',
        'description' => 'Check if location is within distance',
        'types' => ['array'],
        'example' => "addRule('location', 'within_distance', ['lat' => 40.7128, 'lng' => -74.0060, 'km' => 50])",
    ],
],
```

### Implement Custom Operator

```php
namespace App\Rules;

use CleaniqueCoders\Eligify\Engine\RuleEngine;

class CustomRuleEngine extends RuleEngine
{
    /**
     * Check if number is divisible by value
     */
    protected function evaluateDivisibleBy($fieldValue, $expectedValue): bool
    {
        if (!is_numeric($fieldValue) || !is_numeric($expectedValue)) {
            return false;
        }

        return $fieldValue % $expectedValue === 0;
    }

    /**
     * Check if date is a business day
     */
    protected function evaluateIsBusinessDay($fieldValue, $expectedValue): bool
    {
        $date = \Carbon\Carbon::parse($fieldValue);

        // Not Saturday or Sunday
        if ($date->isWeekend()) {
            return $expectedValue === false;
        }

        // Check holidays (example)
        $holidays = ['2025-12-25', '2025-01-01']; // Add your holidays

        if (in_array($date->format('Y-m-d'), $holidays)) {
            return $expectedValue === false;
        }

        return $expectedValue === true;
    }

    /**
     * Check if location is within distance
     */
    protected function evaluateWithinDistance($fieldValue, $expectedValue): bool
    {
        // $fieldValue = ['lat' => 40.7580, 'lng' => -73.9855]
        // $expectedValue = ['lat' => 40.7128, 'lng' => -74.0060, 'km' => 50]

        $distance = $this->calculateDistance(
            $fieldValue['lat'],
            $fieldValue['lng'],
            $expectedValue['lat'],
            $expectedValue['lng']
        );

        return $distance <= $expectedValue['km'];
    }

    /**
     * Calculate distance between two coordinates (Haversine formula)
     */
    protected function calculateDistance($lat1, $lng1, $lat2, $lng2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng/2) * sin($dLng/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        return $earthRadius * $c;
    }
}
```

### Register Custom Engine

```php
// In AppServiceProvider
use App\Rules\CustomRuleEngine;
use CleaniqueCoders\Eligify\Engine\RuleEngine;

public function register()
{
    $this->app->bind(RuleEngine::class, CustomRuleEngine::class);
}
```

### Use Custom Operators

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

$criteria = Eligify::criteria('delivery_eligibility')
    ->addRule('package_count', 'divisible_by', 12)
    ->addRule('delivery_date', 'is_business_day', true)
    ->addRule('store_location', 'within_distance', [
        'lat' => 40.7128,
        'lng' => -74.0060,
        'km' => 50
    ])
    ->save();
```

## Custom Scoring Methods

Implement custom scoring logic.

### Create Custom Scoring Engine

```php
namespace App\Scoring;

use CleaniqueCoders\Eligify\Engine\RuleEngine;

class CustomScoringEngine extends RuleEngine
{
    /**
     * Custom scoring with bonuses and penalties
     */
    protected function calculateScore(array $ruleResults): int
    {
        $score = 0;
        $consecutivePasses = 0;
        $consecutiveFails = 0;

        foreach ($ruleResults as $result) {
            if ($result['passed']) {
                $consecutivePasses++;
                $consecutiveFails = 0;

                // Base weight
                $score += $result['rule']->weight;

                // Streak bonus (every 3 consecutive passes)
                if ($consecutivePasses % 3 === 0) {
                    $score += 5;
                }
            } else {
                $consecutiveFails++;
                $consecutivePasses = 0;

                // Penalty for critical rules
                if ($result['rule']->weight >= 8) {
                    $score -= 10;
                }

                // Increasing penalty for consecutive failures
                $score -= ($consecutiveFails * 2);
            }
        }

        // Normalize to 0-100
        $totalWeight = array_sum(array_column($ruleResults, 'rule.weight'));
        $normalizedScore = ($score / $totalWeight) * 100;

        return max(0, min(100, round($normalizedScore)));
    }

    /**
     * Apply time-based decay to older evaluations
     */
    protected function applyTimeDecay(int $score, \Carbon\Carbon $evaluatedAt): int
    {
        $daysOld = $evaluatedAt->diffInDays(now());

        if ($daysOld <= 30) {
            return $score; // No decay
        }

        // 1% decay per month after 30 days
        $monthsOld = floor($daysOld / 30);
        $decay = min(50, $monthsOld); // Max 50% decay

        return round($score * (1 - $decay / 100));
    }
}
```

### Tiered Scoring System

```php
namespace App\Scoring;

use CleaniqueCoders\Eligify\Engine\RuleEngine;

class TieredScoringEngine extends RuleEngine
{
    protected array $tiers = [
        'platinum' => ['min_score' => 90, 'multiplier' => 1.2],
        'gold' => ['min_score' => 75, 'multiplier' => 1.1],
        'silver' => ['min_score' => 60, 'multiplier' => 1.0],
        'bronze' => ['min_score' => 0, 'multiplier' => 0.9],
    ];

    protected function calculateScore(array $ruleResults): int
    {
        $baseScore = parent::calculateScore($ruleResults);

        // Apply tier multiplier
        $tier = $this->determineTier($baseScore);
        $multiplier = $this->tiers[$tier]['multiplier'];

        return min(100, round($baseScore * $multiplier));
    }

    protected function determineTier(int $score): string
    {
        foreach ($this->tiers as $tier => $config) {
            if ($score >= $config['min_score']) {
                return $tier;
            }
        }

        return 'bronze';
    }
}
```

## Dynamic Criteria

Create and evaluate criteria without persisting to database.

### Ad-Hoc Evaluation

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

$result = Eligify::evaluateDynamic($data, function($builder) {
    $builder->addRule('age', '>=', 18)
           ->addRule('country', 'in', ['US', 'CA'])
           ->addRule('verified', '==', true)
           ->passThreshold(100); // All must pass
});

if ($result['passed']) {
    // Proceed with action
}
```

### Conditional Criteria Builder

```php
$result = Eligify::evaluateDynamic($data, function($builder) use ($userType) {
    // Base rules for all users
    $builder->addRule('email_verified', '==', true)
           ->addRule('age', '>=', 18);

    // Additional rules based on user type
    if ($userType === 'premium') {
        $builder->addRule('subscription_active', '==', true)
               ->addRule('payment_current', '==', true);
    }

    if ($userType === 'enterprise') {
        $builder->addRule('team_size', '>=', 10)
               ->addRule('contract_signed', '==', true);
    }
});
```

### Criteria Templates

```php
namespace App\Services;

class CriteriaTemplates
{
    public static function loanApproval(array $overrides = [])
    {
        return function($builder) use ($overrides) {
            $builder->addRule('credit_score', '>=', $overrides['min_credit'] ?? 650)
                   ->addRule('income', '>=', $overrides['min_income'] ?? 30000)
                   ->addRule('debt_ratio', '<=', $overrides['max_debt_ratio'] ?? 0.43)
                   ->passThreshold($overrides['threshold'] ?? 70);
        };
    }

    public static function membershipTier(string $tier)
    {
        return match($tier) {
            'platinum' => fn($b) => $b->addRule('points', '>=', 10000)
                                      ->addRule('tenure_months', '>=', 12),
            'gold' => fn($b) => $b->addRule('points', '>=', 5000)
                                  ->addRule('tenure_months', '>=', 6),
            'silver' => fn($b) => $b->addRule('points', '>=', 1000)
                                    ->addRule('tenure_months', '>=', 3),
        };
    }
}

// Usage
$result = Eligify::evaluateDynamic(
    $data,
    CriteriaTemplates::loanApproval(['min_credit' => 700])
);
```

## Conditional Rules

Add rules that only apply under certain conditions.

### Simple Conditional Rules

```php
$criteria = Eligify::criteria('age_restricted_content')
    ->addRule('age', '>=', 18)
    ->addConditionalRule(
        condition: fn($data) => $data['age'] < 21,
        rule: ['field' => 'parental_consent', 'operator' => '==', 'value' => true]
    )
    ->save();
```

### Complex Conditional Logic

```php
namespace App\Eligibility;

use CleaniqueCoders\Eligify\Builder\CriteriaBuilder;

class ConditionalCriteriaBuilder extends CriteriaBuilder
{
    public function addConditionalRuleGroup(callable $condition, array $rules): self
    {
        $this->conditionalRules[] = [
            'condition' => $condition,
            'rules' => $rules,
        ];

        return $this;
    }

    public function evaluate(array $data): array
    {
        // Apply conditional rules
        foreach ($this->conditionalRules as $conditional) {
            if (call_user_func($conditional['condition'], $data)) {
                foreach ($conditional['rules'] as $rule) {
                    $this->addRule(...$rule);
                }
            }
        }

        return parent::evaluate($data);
    }
}

// Usage
$builder = new ConditionalCriteriaBuilder('complex_loan');

$builder->addRule('credit_score', '>=', 650)
        ->addConditionalRuleGroup(
            condition: fn($data) => $data['loan_amount'] > 100000,
            rules: [
                ['field' => 'income', 'operator' => '>=', 'value' => 100000, 'weight' => 10],
                ['field' => 'employment_years', 'operator' => '>=', 'value' => 5, 'weight' => 8],
                ['field' => 'debt_ratio', 'operator' => '<=', 'value' => 0.30, 'weight' => 9],
            ]
        )
        ->save();
```

## Rule Groups

Organize rules into logical groups.

### Basic Rule Groups

```php
$criteria = Eligify::criteria('comprehensive_check')
    ->addRuleGroup('identity', [
        ['field' => 'ssn_verified', 'operator' => '==', 'value' => true, 'weight' => 10],
        ['field' => 'address_verified', 'operator' => '==', 'value' => true, 'weight' => 8],
        ['field' => 'id_document', 'operator' => 'exists', 'value' => true, 'weight' => 9],
    ])
    ->addRuleGroup('financial', [
        ['field' => 'credit_score', 'operator' => '>=', 'value' => 650, 'weight' => 10],
        ['field' => 'income', 'operator' => '>=', 'value' => 30000, 'weight' => 8],
        ['field' => 'debt_ratio', 'operator' => '<=', 'value' => 0.43, 'weight' => 7],
    ])
    ->save();
```

### Group-Level Requirements

```php
namespace App\Eligibility;

class GroupedCriteria
{
    public function createWithGroupRequirements()
    {
        return Eligify::criteria('strict_approval')
            ->addRuleGroup('critical', [
                ['field' => 'kyc_complete', 'operator' => '==', 'value' => true],
                ['field' => 'sanctions_clear', 'operator' => '==', 'value' => true],
            ], minPass: 2) // Both must pass
            ->addRuleGroup('verification', [
                ['field' => 'email_verified', 'operator' => '==', 'value' => true],
                ['field' => 'phone_verified', 'operator' => '==', 'value' => true],
                ['field' => 'address_verified', 'operator' => '==', 'value' => true],
            ], minPass: 2) // At least 2 must pass
            ->save();
    }
}
```

## Workflow Automation

Advanced workflow patterns with Eligify.

### Multi-Step Workflows

```php
use CleaniqueCoders\Eligify\Workflow\WorkflowManager;

$workflow = new WorkflowManager();

$workflow->addStep('initial_check', function($data) {
    return Eligify::evaluate('basic_eligibility', $data);
})
->addStep('credit_check', function($data, $previousResult) {
    if (!$previousResult['passed']) {
        return ['passed' => false, 'message' => 'Failed initial check'];
    }
    return Eligify::evaluate('credit_requirements', $data);
})
->addStep('final_approval', function($data, $previousResult) {
    if ($previousResult['score'] >= 90) {
        return ['passed' => true, 'tier' => 'premium'];
    } elseif ($previousResult['score'] >= 70) {
        return ['passed' => true, 'tier' => 'standard'];
    }
    return ['passed' => false];
})
->onComplete(function($data, $results) {
    // Send notification
    // Update database
    // Trigger external API
});

$result = $workflow->execute($data);
```

### Async Workflows

```php
use Illuminate\Support\Facades\Queue;

$criteria = Eligify::criteria('async_approval')
    ->addRule('credit_score', '>=', 650)
    ->onPass(function($data, $result) {
        // Queue async tasks
        Queue::push(new SendApprovalEmail($data));
        Queue::push(new CreateLoanAccount($data, $result));
        Queue::push(new NotifyUnderwriter($data));
    })
    ->save();
```

### Retry Logic

```php
namespace App\Workflows;

use CleaniqueCoders\Eligify\Facades\Eligify;

class ResilientEvaluator
{
    public function evaluateWithRetry(string $criteria, array $data, int $maxAttempts = 3): array
    {
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            try {
                return Eligify::evaluate($criteria, $data);
            } catch (\Exception $e) {
                $attempt++;

                if ($attempt >= $maxAttempts) {
                    throw $e;
                }

                // Exponential backoff
                sleep(pow(2, $attempt));
            }
        }
    }
}
```

## Event System

Listen and respond to Eligify events.

### Available Events

```php
use CleaniqueCoders\Eligify\Events\{
    CriteriaCreated,
    CriteriaUpdated,
    CriteriaDeleted,
    RuleCreated,
    RuleExecuted,
    EvaluationCompleted,
    EvaluationFailed,
};
```

### Creating Event Listeners

```php
namespace App\Listeners;

use CleaniqueCoders\Eligify\Events\EvaluationCompleted;
use Illuminate\Support\Facades\Log;

class LogEvaluationCompleted
{
    public function handle(EvaluationCompleted $event)
    {
        Log::info('Evaluation completed', [
            'criteria' => $event->evaluation->criteria->name,
            'passed' => $event->evaluation->passed,
            'score' => $event->evaluation->score,
            'evaluable_type' => $event->evaluation->evaluable_type,
            'evaluable_id' => $event->evaluation->evaluable_id,
        ]);
    }
}
```

### Register Listeners

```php
// In EventServiceProvider
protected $listen = [
    EvaluationCompleted::class => [
        LogEvaluationCompleted::class,
        SendNotification::class,
        UpdateDashboard::class,
    ],
    EvaluationFailed::class => [
        NotifyAdministrator::class,
        CreateSupportTicket::class,
    ],
];
```

### Custom Events

```php
namespace App\Events;

use CleaniqueCoders\Eligify\Models\Evaluation;

class HighValueLoanApproved
{
    public function __construct(
        public Evaluation $evaluation,
        public float $loanAmount
    ) {}
}

// Dispatch in callback
$criteria->onPass(function($data, $result) {
    if ($data['loan_amount'] > 100000) {
        event(new HighValueLoanApproved($result['evaluation'], $data['loan_amount']));
    }
});
```

## Performance Optimization

### Query Optimization

```php
// Eager load relationships
$criteria = Criteria::with(['rules', 'evaluations.evaluable'])->find($id);

// Select specific columns
$evaluations = Evaluation::select('id', 'criteria_id', 'passed', 'score')
    ->where('criteria_id', $criteriaId)
    ->get();

// Use chunking for large datasets
Evaluation::where('created_at', '<', now()->subYear())
    ->chunk(1000, function($evaluations) {
        foreach ($evaluations as $evaluation) {
            // Process
        }
    });
```

### Caching

```php
use Illuminate\Support\Facades\Cache;

// Cache criteria
$criteria = Cache::remember("criteria:{$slug}", 3600, function() use ($slug) {
    return Criteria::with('rules')->whereSlug($slug)->first();
});

// Cache evaluation results (be careful with this!)
$cacheKey = "evaluation:{$criteriaId}:" . md5(json_encode($data));
$result = Cache::remember($cacheKey, 300, function() use ($criteria, $data) {
    return Eligify::evaluate($criteria, $data);
});
```

### Rule Compilation

```php
// In config/eligify.php
'performance' => [
    'compile_rules' => true,
    'compilation_cache_ttl' => 1440, // 24 hours
],
```

### Batch Processing

```php
use Illuminate\Support\Facades\DB;

DB::transaction(function() use ($users) {
    $batchSize = 100;

    foreach ($users->chunk($batchSize) as $batch) {
        $evaluations = [];

        foreach ($batch as $user) {
            $result = Eligify::evaluate('membership', $user->toArray(), false);

            $evaluations[] = [
                'criteria_id' => $result['criteria_id'],
                'evaluable_type' => User::class,
                'evaluable_id' => $user->id,
                'passed' => $result['passed'],
                'score' => $result['score'],
                'evaluated_at' => now(),
            ];
        }

        Evaluation::insert($evaluations);
    }
});
```

## Multi-Tenancy

### Tenant Isolation

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant()
    {
        static::addGlobalScope('tenant', function ($query) {
            if (auth()->check()) {
                $query->where('tenant_id', auth()->user()->tenant_id);
            }
        });

        static::creating(function ($model) {
            if (auth()->check() && !$model->tenant_id) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });
    }
}

// Apply to Eligify models
class Criteria extends Model
{
    use BelongsToTenant;
}
```

### Per-Tenant Configuration

```php
namespace App\Services;

class TenantEligibilityService
{
    public function getCriteriaForTenant(string $criteriaName): ?Criteria
    {
        $tenant = auth()->user()->tenant;

        // Try tenant-specific criteria first
        $criteria = Criteria::where('slug', "{$tenant->id}-{$criteriaName}")
            ->first();

        // Fall back to default
        if (!$criteria) {
            $criteria = Criteria::where('slug', $criteriaName)->first();
        }

        return $criteria;
    }
}
```

## Next Steps

- [Examples](../examples/README.md) - See real-world implementations
- [API Reference](api-reference.md) - Complete API documentation
- [Troubleshooting](troubleshooting.md) - Common issues and solutions
