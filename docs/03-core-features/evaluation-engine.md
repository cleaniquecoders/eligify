# Evaluation Engine

The Evaluation Engine orchestrates the complete evaluation process, from data extraction to result compilation.

## Overview

The Evaluation Engine coordinates:

1. Data extraction from subjects
2. Rule evaluation via the Rule Engine
3. Score calculation via Scorers
4. Result compilation
5. Audit logging
6. Workflow execution

## Basic Evaluation

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

$criteria = Eligify::criteria('Loan Approval')
    ->addRule('income', '>=', 3000)
    ->addRule('credit_score', '>=', 650);

$result = $criteria->evaluate($applicant);
```

## Evaluation Process

### Step 1: Data Extraction

Subject data is extracted and normalized:

```php
// Array
$data = ['income' => 5000, 'credit_score' => 750];
$result = $criteria->evaluate($data);

// Model
$user = User::find(1);
$result = $criteria->evaluate($user);

// Custom Object
$applicant = new LoanApplicant();
$result = $criteria->evaluate($applicant);
```

### Step 2: Rule Evaluation

Each rule is evaluated sequentially:

```php
foreach ($criteria->rules as $rule) {
    $value = data_get($data, $rule->field);
    $passed = $operator->evaluate($value, $rule->expected_value);
}
```

### Step 3: Score Calculation

Scorer calculates final score:

```php
$scorer = ScorerFactory::make($criteria->scoring_method);
$score = $scorer->calculate($criteria->rules, $results);
```

### Step 4: Result Compilation

Results are packaged into result object:

```php
$result = new EvaluationResult(
    passed: $score >= $threshold,
    score: $score,
    passedRules: $passed,
    failedRules: $failed,
    details: $details
);
```

## Result Object

### Checking Results

```php
$result = $criteria->evaluate($subject);

// Did it pass?
if ($result->passed()) {
    echo "Eligible!";
}

// Get score
echo $result->score(); // 85.5

// Get failed rules
$failedRules = $result->failedRules();
foreach ($failedRules as $rule) {
    echo $rule['field'] . ' failed';
}

// Get passed rules
$passedRules = $result->passedRules();

// Get all details
$details = $result->details();
```

### Result Methods

| Method | Return Type | Description |
|--------|------------|-------------|
| `passed()` | bool | Whether evaluation passed |
| `failed()` | bool | Whether evaluation failed |
| `score()` | float | Calculated score |
| `passedRules()` | array | Rules that passed |
| `failedRules()` | array | Rules that failed |
| `details()` | array | Full evaluation details |
| `getCriteria()` | Criteria | Criteria used |
| `getSubject()` | mixed | Subject evaluated |

## Evaluation Options

### Debug Mode

```php
$result = $criteria->evaluate($subject, debug: true);

dump($result->getDebugInfo());
```

### Skip Audit

```php
$result = $criteria->evaluate($subject, skipAudit: true);
```

### Custom Context

```php
$result = $criteria->evaluate($subject, context: [
    'ip_address' => request()->ip(),
    'user_agent' => request()->userAgent(),
]);
```

## Batch Evaluation

Evaluate multiple subjects at once:

```php
$subjects = User::where('status', 'pending')->get();

$results = Eligify::criteria('Approval')
    ->evaluateMany($subjects);

foreach ($results as $subject => $result) {
    if ($result->passed()) {
        $subject->approve();
    }
}
```

## Conditional Evaluation

### Evaluate Only If

```php
$criteria = Eligify::criteria('Premium Check');

if ($user->isActive()) {
    $result = $criteria->evaluate($user);
}
```

### Lazy Evaluation

```php
$result = $criteria->lazy()->evaluate($user);
// Rules evaluated only when accessed
```

## Caching Evaluations

### Cache Results

```php
use Illuminate\Support\Facades\Cache;

$cacheKey = "eligibility:{$criteria->name}:{$user->id}";

$result = Cache::remember($cacheKey, 3600, function () use ($criteria, $user) {
    return $criteria->evaluate($user);
});
```

### Clear Cache

```php
Cache::forget("eligibility:{$criteria->name}:{$user->id}");
```

## Error Handling

### Try-Catch

```php
use CleaniqueCoders\Eligify\Exceptions\EvaluationException;

try {
    $result = $criteria->evaluate($subject);
} catch (EvaluationException $e) {
    Log::error('Evaluation failed', [
        'criteria' => $criteria->name,
        'error' => $e->getMessage(),
    ]);
}
```

### Validation Errors

```php
try {
    $result = $criteria->evaluate($subject);
} catch (InvalidDataException $e) {
    return back()->withErrors([
        'evaluation' => 'Invalid data provided for evaluation'
    ]);
}
```

## Performance Optimization

### Eager Loading

```php
$criteria = Criteria::with('rules')->findByName('loan_approval');
$result = $criteria->evaluate($user);
```

### Early Termination

```php
// Stop on first failure for pass/fail
'engine' => [
    'early_termination' => true,
],
```

### Parallel Evaluation

```php
// Evaluate rules in parallel (requires pcntl extension)
'engine' => [
    'parallel_execution' => true,
    'max_workers' => 4,
],
```

## Event Hooks

Listen to evaluation events:

```php
use CleaniqueCoders\Eligify\Events\EvaluationCompleted;

Event::listen(EvaluationCompleted::class, function ($event) {
    Log::info('Evaluation completed', [
        'criteria' => $event->criteria->name,
        'passed' => $event->result->passed(),
    ]);
});
```

## Testing Evaluations

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

test('user passes eligibility check', function () {
    $user = User::factory()->create([
        'income' => 5000,
        'credit_score' => 750,
    ]);

    $criteria = Eligify::criteria('Loan Approval')
        ->addRule('income', '>=', 3000)
        ->addRule('credit_score', '>=', 650);

    $result = $criteria->evaluate($user);

    expect($result->passed())->toBeTrue();
    expect($result->score())->toBeGreaterThan(0);
});

test('user fails eligibility check', function () {
    $user = User::factory()->create([
        'income' => 2000,
        'credit_score' => 600,
    ]);

    $criteria = Eligify::criteria('Loan Approval')
        ->addRule('income', '>=', 3000)
        ->addRule('credit_score', '>=', 650);

    $result = $criteria->evaluate($user);

    expect($result->failed())->toBeTrue();
    expect($result->failedRules())->toHaveCount(2);
});
```

## Related Documentation

- [Criteria Builder](criteria-builder.md) - Building criteria
- [Rule Engine](rule-engine.md) - Rule evaluation
- [Scoring Methods](scoring-methods.md) - Score calculation
- [Workflow Callbacks](workflow-callbacks.md) - Post-evaluation actions
