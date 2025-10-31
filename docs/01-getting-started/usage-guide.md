# Usage Guide

This guide covers common usage patterns and practical examples for Eligify.

## Table of Contents

- [Creating Criteria](#creating-criteria)
- [Adding Rules](#adding-rules)
- [Evaluating Eligibility](#evaluating-eligibility)
- [Working with Models](#working-with-models)
- [Batch Operations](#batch-operations)
- [Callbacks and Workflows](#callbacks-and-workflows)
- [Query and Retrieval](#query-and-retrieval)
- [Best Practices](#best-practices)

## Creating Criteria

### Basic Criteria

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

$criteria = Eligify::criteria('Basic Membership')
    ->description('Basic membership eligibility')
    ->addRule('age', '>=', 18)
    ->addRule('email_verified', '==', true)
    ->save();
```

### With Custom Threshold

```php
$criteria = Eligify::criteria('Premium Membership')
    ->description('Premium tier membership')
    ->addRule('account_age_months', '>=', 6, 5)
    ->addRule('purchase_count', '>=', 10, 8)
    ->addRule('review_count', '>=', 5, 3)
    ->passThreshold(75)  // Requires 75% score
    ->save();
```

### With Scoring Method

```php
use CleaniqueCoders\Eligify\Enums\ScoringMethod;

$criteria = Eligify::criteria('Strict Compliance')
    ->description('All-or-nothing compliance check')
    ->addRule('kyc_verified', '==', true)
    ->addRule('terms_accepted', '==', true)
    ->addRule('documents_uploaded', '==', true)
    ->scoringMethod(ScoringMethod::PASS_FAIL)  // 100 or 0
    ->save();
```

### Dynamic Criteria (Non-persistent)

```php
// Create and evaluate without saving to database
$result = Eligify::evaluateDynamic($data, function($builder) {
    $builder->addRule('temp_field', '>', 100)
           ->addRule('status', '==', 'active')
           ->passThreshold(70);
});
```

## Adding Rules

### Numeric Rules

```php
$criteria->addRule('credit_score', '>=', 650, 8)
         ->addRule('income', '>', 30000, 7)
         ->addRule('debt_ratio', '<=', 0.43, 6)
         ->addRule('active_loans', '<', 3, 4);
```

### String Rules

```php
$criteria->addRule('email', 'contains', '@company.com')
         ->addRule('account_number', 'starts_with', 'ACC')
         ->addRule('domain', 'ends_with', '.edu')
         ->addRule('phone', 'regex', '/^\+1[0-9]{10}$/');
```

### Array Rules

```php
$criteria->addRule('country', 'in', ['US', 'CA', 'UK', 'AU'])
         ->addRule('status', 'not_in', ['banned', 'suspended'])
         ->addRule('membership_tier', 'in', ['gold', 'platinum']);
```

### Range Rules

```php
$criteria->addRule('age', 'between', [18, 65])
         ->addRule('gpa', 'between', [3.0, 4.0])
         ->addRule('risk_score', 'not_between', [80, 100]);
```

### Boolean Rules

```php
$criteria->addRule('email_verified', '==', true)
         ->addRule('is_suspended', '==', false)
         ->addRule('terms_accepted', '==', true);
```

### Existence Rules

```php
$criteria->addRule('profile_photo', 'exists', true)
         ->addRule('bankruptcy_record', 'not_exists', true)
         ->addRule('social_security', 'exists', true);
```

### Weighted Rules

```php
// Higher weight = more important
$criteria->addRule('critical_field', '>=', 100, 10)  // Most critical
         ->addRule('important_field', '>=', 50, 7)   // Important
         ->addRule('moderate_field', '>=', 25, 5)    // Moderate
         ->addRule('minor_field', '>=', 10, 2);      // Minor
```

## Evaluating Eligibility

### Simple Evaluation

```php
$result = Eligify::evaluate('loan_approval', [
    'credit_score' => 720,
    'income' => 55000,
    'employment_status' => 'employed',
]);

// Check result
if ($result['passed']) {
    echo "Approved! Score: {$result['score']}%";
} else {
    echo "Denied. Failed rules: " . count($result['failed_rules']);
}
```

### Understanding Results

```php
$result = [
    'passed' => true,              // Boolean: did it pass?
    'score' => 85,                 // Integer: calculated score (0-100)
    'decision' => 'Approved',      // String: human-readable decision
    'failed_rules' => [],          // Array: rules that failed
    'criteria_id' => 1,            // ID of criteria evaluated
    'evaluation_id' => 123,        // ID of evaluation record
];
```

### Handling Failed Rules

```php
$result = Eligify::evaluate('membership_upgrade', $data);

if (!$result['passed']) {
    echo "Failed requirements:\n";

    foreach ($result['failed_rules'] as $failedRule) {
        $rule = $failedRule['rule'];
        $field = $rule->field;
        $operator = $rule->operator;
        $expected = $rule->value;
        $actual = $failedRule['field_value'];

        echo "- {$field}: Expected {$operator} {$expected}, got {$actual}\n";
    }
}
```

### Evaluation Without Saving

```php
// Evaluate but don't save to database
$result = Eligify::evaluate('test_criteria', $data, saveEvaluation: false);
```

## Working with Models

### Adding Trait to Models

```php
namespace App\Models;

use CleaniqueCoders\Eligify\Concerns\HasEligibility;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasEligibility;
}
```

### Evaluating Models

```php
$user = User::find(1);

// Evaluate user against criteria
$result = $user->evaluateEligibility('premium_membership');

// Check if eligible (boolean)
$isEligible = $user->isEligibleFor('loan_approval');

// Get eligibility score
$score = $user->getEligibilityScore('scholarship');
```

### Model Evaluation History

```php
// Get all evaluations for a model
$evaluations = $user->evaluations;

// Get passed evaluations
$passed = $user->evaluations()->where('passed', true)->get();

// Get recent evaluations
$recent = $user->evaluations()
    ->orderBy('evaluated_at', 'desc')
    ->limit(10)
    ->get();

// Get evaluations for specific criteria
$loanEvals = $user->evaluations()
    ->whereHas('criteria', function($query) {
        $query->where('slug', 'loan-approval');
    })
    ->get();
```

### Polymorphic Relations

```php
// Works with any model
class Application extends Model
{
    use HasEligibility;
}

$application = Application::find(1);
$result = $application->evaluateEligibility('loan_approval');

// Query evaluations
$evaluations = Evaluation::where('evaluable_type', Application::class)
    ->where('evaluable_id', $application->id)
    ->get();
```

### Attaching Criteria to Models

Use the `HasCriteria` trait to assign criteria to any model and query by context fields:

```php
use CleaniqueCoders\Eligify\Concerns\HasCriteria;
use CleaniqueCoders\Eligify\Models\Criteria;

class User extends Model
{
    use HasCriteria;
}

// Create or load criteria
$sub = Criteria::factory()->create(['type' => 'subscription']);
$feat = Criteria::factory()->create(['type' => 'feature', 'tags' => ['beta']]);

// Attach to a user
$user->attachCriteria([$sub->id, $feat->id]);

// Query
$all = $user->criteria()->get();
$byType = $user->criteriaOfType(['subscription', 'feature'])->get();
$byTags = $user->criteriaTagged(['beta'])->get();
```

## Batch Operations

### Batch Evaluation

```php
$users = User::whereIn('id', [1, 2, 3, 4, 5])->get();

$dataArray = $users->map(function($user) {
    return [
        'user_id' => $user->id,
        'credit_score' => $user->credit_score,
        'income' => $user->annual_income,
        'employment' => $user->employment_status,
    ];
})->toArray();

$results = Eligify::batchEvaluate('loan_approval', $dataArray);

foreach ($results as $index => $result) {
    $user = $users[$index];
    echo "{$user->name}: " . ($result['passed'] ? 'APPROVED' : 'DENIED') . "\n";
}
```

### Processing Large Datasets

```php
use Illuminate\Support\Facades\DB;

// Process in chunks
User::chunk(100, function($users) {
    foreach ($users as $user) {
        $result = $user->evaluateEligibility('premium_membership');

        if ($result['passed']) {
            $user->upgradeToPremium();
        }
    }
});
```

### Bulk Criteria Application

```php
// Apply multiple criteria to one dataset
$criteriaNames = ['basic_check', 'advanced_check', 'premium_check'];

foreach ($criteriaNames as $criteriaName) {
    $result = Eligify::evaluate($criteriaName, $data);

    if ($result['passed']) {
        echo "✅ Passed: {$criteriaName}\n";
    } else {
        echo "❌ Failed: {$criteriaName}\n";
        break; // Stop on first failure
    }
}
```

## Callbacks and Workflows

### Basic Callbacks

```php
$criteria = Eligify::criteria('Loan Approval')
    ->addRule('credit_score', '>=', 650)
    ->onPass(function($data, $result) {
        // Execute when approved
        Mail::to($data['email'])->send(new LoanApproved($result));
        DB::table('loans')->insert([
            'user_id' => $data['user_id'],
            'status' => 'approved',
            'score' => $result['score'],
        ]);
    })
    ->onFail(function($data, $result) {
        // Execute when denied
        Mail::to($data['email'])->send(new LoanDenied($result));
        Log::info('Loan denied', [
            'user_id' => $data['user_id'],
            'score' => $result['score'],
            'failed_rules' => $result['failed_rules'],
        ]);
    })
    ->save();
```

### Tiered Callbacks

```php
$criteria = Eligify::criteria('Membership Tiers')
    ->addRule('points', '>=', 1000)
    ->passThreshold(60)
    ->onExcellent(function($data, $result) {
        // Score >= 90
        $data['user']->assignRole('platinum_member');
        $data['user']->notify(new PlatinumUpgrade());
    })
    ->onGood(function($data, $result) {
        // 70 <= Score < 90
        $data['user']->assignRole('gold_member');
        $data['user']->notify(new GoldUpgrade());
    })
    ->onPass(function($data, $result) {
        // 60 <= Score < 70
        $data['user']->assignRole('silver_member');
        $data['user']->notify(new SilverUpgrade());
    })
    ->onFail(function($data, $result) {
        // Score < 60
        $data['user']->assignRole('basic_member');
    })
    ->save();
```

### Workflow Steps

```php
$criteria = Eligify::criteria('Complex Workflow')
    ->addRule('stage1', '==', 'complete')
    ->onPass(function($data, $result) {
        // Step 1: Update status
        DB::table('applications')
            ->where('id', $data['application_id'])
            ->update(['status' => 'approved']);
    })
    ->onPass(function($data, $result) {
        // Step 2: Send notifications
        SendApprovalNotification::dispatch($data);
    })
    ->onPass(function($data, $result) {
        // Step 3: Trigger external API
        Http::post('https://api.example.com/approved', $data);
    })
    ->save();
```

### Error Handling in Callbacks

```php
$criteria = Eligify::criteria('Safe Workflow')
    ->addRule('check', '==', true)
    ->onPass(function($data, $result) {
        try {
            // Risky operation
            ExternalService::process($data);
        } catch (\Exception $e) {
            Log::error('Workflow failed', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            // Optionally re-throw to fail evaluation
            // throw $e;
        }
    })
    ->save();
```

## Query and Retrieval

### Retrieving Criteria

```php
use CleaniqueCoders\Eligify\Models\Criteria;

// By slug
$criteria = Criteria::whereSlug('loan-approval')->first();

// By name
$criteria = Criteria::whereName('Loan Approval')->first();

// Active only
$activeCriteria = Criteria::where('is_active', true)->get();

// With rules
$criteria = Criteria::with('rules')->find($id);

// With evaluations
$criteria = Criteria::with('evaluations')->find($id);
```

### Retrieving Evaluations

```php
use CleaniqueCoders\Eligify\Models\Evaluation;

// Recent evaluations
$recent = Evaluation::orderBy('evaluated_at', 'desc')
    ->limit(20)
    ->get();

// Passed evaluations
$passed = Evaluation::where('passed', true)->get();

// High scorers
$excellent = Evaluation::where('score', '>=', 90)->get();

// Failed evaluations with reasons
$failed = Evaluation::where('passed', false)
    ->whereNotNull('failed_rules')
    ->get();
```

### Filtering by Date

```php
// Today's evaluations
$today = Evaluation::whereDate('evaluated_at', today())->get();

// Last 7 days
$lastWeek = Evaluation::where('evaluated_at', '>=', now()->subDays(7))->get();

// Date range
$range = Evaluation::whereBetween('evaluated_at', [
    '2025-10-01',
    '2025-10-31'
])->get();
```

### Statistical Queries

```php
// Pass rate for criteria
$passRate = Evaluation::where('criteria_id', $criteriaId)
    ->selectRaw('
        COUNT(*) as total,
        SUM(passed) as passed_count,
        AVG(score) as average_score,
        MAX(score) as highest_score,
        MIN(score) as lowest_score
    ')
    ->first();

echo "Pass Rate: " . ($passRate->passed_count / $passRate->total * 100) . "%\n";
echo "Average Score: {$passRate->average_score}\n";
```

### Scopes

```php
// Add scopes to Evaluation model
class Evaluation extends Model
{
    public function scopePassed($query)
    {
        return $query->where('passed', true);
    }

    public function scopeFailed($query)
    {
        return $query->where('passed', false);
    }

    public function scopeExcellent($query)
    {
        return $query->where('score', '>=', 90);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('evaluated_at', '>=', now()->subDays($days));
    }
}

// Usage
$excellentRecent = Evaluation::excellent()->recent(30)->get();
$failedToday = Evaluation::failed()->whereDate('evaluated_at', today())->get();
```

## Best Practices

### 1. Use Descriptive Names

```php
// ❌ Bad
Eligify::criteria('Approval);

// ✅ Good
Eligify::criteria('Personal Loan Approval Tier 1);
```

### 2. Add Descriptions

```php
$criteria = Eligify::criteria('Scholarship Eligibility')
    ->description('Merit-based scholarship for undergraduate students in STEM fields')
    ->addRule('gpa', '>=', 3.5)
    ->save();
```

### 3. Balance Rule Weights

```php
// Ensure weights reflect importance
$criteria->addRule('critical_check', '==', true, 10)  // Critical
         ->addRule('important_check', '>=', 50, 7)    // Important
         ->addRule('nice_to_have', '>', 10, 3);       // Nice to have

// Total weights: 10 + 7 + 3 = 20
```

### 4. Set Appropriate Thresholds

```php
// Strict (all must pass)
->passThreshold(100)

// Selective (high bar)
->passThreshold(80)

// Balanced (moderate)
->passThreshold(65)

// Lenient (low bar)
->passThreshold(50)
```

### 5. Handle Errors Gracefully

```php
try {
    $result = Eligify::evaluate('criteria_name', $data);
} catch (\InvalidArgumentException $e) {
    Log::error('Criteria not found', ['name' => 'criteria_name']);
    return response()->json(['error' => 'Invalid criteria'], 404);
} catch (\Exception $e) {
    Log::error('Evaluation failed', ['error' => $e->getMessage()]);
    return response()->json(['error' => 'Evaluation error'], 500);
}
```

### 6. Validate Input Data

```php
$validated = validator($request->all(), [
    'credit_score' => 'required|integer|min:300|max:850',
    'income' => 'required|numeric|min:0',
    'employment_status' => 'required|in:employed,self-employed,unemployed',
])->validate();

$result = Eligify::evaluate('loan_approval', $validated);
```

### 7. Use Transactions for Critical Operations

```php
DB::transaction(function() use ($data) {
    $result = Eligify::evaluate('loan_approval', $data);

    if ($result['passed']) {
        // Create loan record
        $loan = Loan::create([
            'user_id' => $data['user_id'],
            'amount' => $data['amount'],
            'status' => 'approved',
        ]);

        // Update user status
        User::find($data['user_id'])->update([
            'has_active_loan' => true,
        ]);
    }
});
```

### 8. Cache Frequently Used Criteria

```php
use Illuminate\Support\Facades\Cache;

$criteria = Cache::remember('criteria:loan-approval', 3600, function() {
    return Criteria::with('rules')->whereSlug('loan-approval')->first();
});
```

### 9. Monitor Performance

```php
$startTime = microtime(true);

$result = Eligify::evaluate('complex_criteria', $data);

$duration = microtime(true) - $startTime;

if ($duration > 1.0) {
    Log::warning('Slow evaluation', [
        'criteria' => 'complex_criteria',
        'duration' => $duration,
    ]);
}
```

### 10. Document Business Logic

```php
$criteria = Eligify::criteria('Insurance Underwriting')
    ->description('
        Insurance eligibility based on:
        - Age: 18-65 (weight: 8)
        - Health score: >= 70 (weight: 10)
        - No pre-existing conditions (weight: 9)
        - Clean driving record for auto insurance (weight: 7)

        Pass threshold: 75%
        Approved by: Risk Management Team
        Last updated: 2025-10-27
    ')
    ->addRule('age', 'between', [18, 65], 8)
    ->addRule('health_score', '>=', 70, 10)
    ->addRule('pre_existing_conditions', '==', false, 9)
    ->addRule('clean_driving_record', '==', true, 7)
    ->passThreshold(75)
    ->save();
```

## Next Steps

- [Advanced Features](advanced-features.md)
- [CLI Commands](cli-commands.md)
- [Policy Integration](policy-integration.md)
- [Real-World Examples](../examples/README.md)
