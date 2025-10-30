# Request Lifecycle

This document explains how an eligibility evaluation flows through the Eligify system from start to finish.

## Overview

When you call `Eligify::criteria('name')->evaluate($subject)`, the request goes through multiple stages. Understanding this flow helps with debugging, optimization, and extending the system.

## The Complete Flow

```
1. Facade Call
   ↓
2. Criteria Loading/Building
   ↓
3. Data Extraction
   ↓
4. Rule Evaluation (Loop)
   ↓
5. Score Calculation
   ↓
6. Result Compilation
   ↓
7. Audit Logging
   ↓
8. Workflow Execution
   ↓
9. Event Broadcasting
   ↓
10. Return Result
```

## Detailed Breakdown

### 1. Facade Call

The entry point is typically the Eligify facade:

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

$result = Eligify::criteria('loan_approval')->evaluate($applicant);
```

**What happens:**
- Laravel resolves the facade to the `Eligify` service
- The service is retrieved from the container
- A `CriteriaBuilder` instance is created

### 2. Criteria Loading/Building

The criteria is either loaded from the database or built in memory:

```php
// Loading from database
$criteria = Criteria::where('name', 'loan_approval')->firstOrFail();

// Or building in memory
$builder = new CriteriaBuilder('loan_approval');
$builder->addRule('income', '>=', 3000);
```

**Key classes:**
- `CleaniqueCoders\Eligify\Models\Criteria`
- `CleaniqueCoders\Eligify\Builder\CriteriaBuilder`

**Events fired:**
- `CriteriaLoaded` (if loaded from DB)
- `CriteriaCreated` (if newly created)

### 3. Data Extraction

The subject data is extracted and normalized:

```php
$extractor = new DataExtractor();
$data = $extractor->extract($subject);
```

**Extraction strategies:**

**For Arrays:**
```php
// Direct pass-through
$data = $subject;
```

**For Models:**
```php
// Use mapper if available
$mapperClass = config('eligify.mappers.' . get_class($subject));
if ($mapperClass) {
    $mapper = new $mapperClass($subject);
    $data = $mapper->toArray();
} else {
    // Fall back to model attributes
    $data = $subject->toArray();
}
```

**For Custom Objects:**
```php
// Check for Mappable interface
if ($subject instanceof Mappable) {
    $data = $subject->toEligibilityData();
} else {
    $data = get_object_vars($subject);
}
```

**Key classes:**
- `CleaniqueCoders\Eligify\Support\DataExtractor`
- `CleaniqueCoders\Eligify\Support\Mappers\BaseMapper`

### 4. Rule Evaluation Loop

Each rule is evaluated against the extracted data:

```php
$ruleEngine = new RuleEngine();
$results = [];

foreach ($criteria->rules as $rule) {
    $operator = OperatorFactory::make($rule->operator);
    $fieldValue = data_get($data, $rule->field);
    $passed = $operator->evaluate($fieldValue, $rule->value);

    $results[] = [
        'rule' => $rule,
        'passed' => $passed,
        'actual_value' => $fieldValue,
        'expected_value' => $rule->value,
    ];
}
```

**Key classes:**
- `CleaniqueCoders\Eligify\Engine\RuleEngine`
- `CleaniqueCoders\Eligify\Engine\OperatorFactory`
- `CleaniqueCoders\Eligify\Engine\Operators\*`

**Operator resolution:**
```php
match($operator) {
    '==', 'equals' => new EqualsOperator(),
    '>', 'greater_than' => new GreaterThanOperator(),
    '>=', 'gte' => new GreaterThanOrEqualOperator(),
    'in', 'contains' => new InOperator(),
    // ... etc
}
```

### 5. Score Calculation

The scoring method calculates the final score:

```php
$scorer = ScorerFactory::make($criteria->scoring_method);
$score = $scorer->calculate($criteria->rules, $results);
$passed = $scorer->isPassing($score, $criteria->passing_threshold);
```

**Scoring methods:**

**Weighted:**
```php
$totalWeight = sum(rule->weight);
$earnedWeight = sum(passed ? rule->weight : 0);
$score = ($earnedWeight / $totalWeight) * 100;
```

**Pass/Fail:**
```php
$score = all_passed ? 100 : 0;
```

**Sum:**
```php
$score = sum(passed ? rule->weight : 0);
```

**Average:**
```php
$score = (count(passed) / count(all)) * 100;
```

**Key classes:**
- `CleaniqueCoders\Eligify\Engine\ScorerFactory`
- `CleaniqueCoders\Eligify\Engine\Scorers\*`

### 6. Result Compilation

All evaluation data is compiled into a result object:

```php
$result = new EvaluationResult(
    passed: $passed,
    score: $score,
    passedRules: array_filter($results, fn($r) => $r['passed']),
    failedRules: array_filter($results, fn($r) => !$r['passed']),
    details: [
        'criteria_name' => $criteria->name,
        'evaluated_at' => now(),
        'rule_count' => count($criteria->rules),
        'execution_time_ms' => $executionTime,
    ]
);
```

**Key classes:**
- `CleaniqueCoders\Eligify\Data\EvaluationResult`

### 7. Audit Logging

If audit logging is enabled, a record is created:

```php
if (config('eligify.audit.enabled')) {
    Evaluation::create([
        'criteria_id' => $criteria->id,
        'subject_type' => get_class($subject),
        'subject_id' => $subject->id ?? null,
        'snapshot' => $data,
        'result' => $result,
        'passed' => $passed,
        'score' => $score,
        'evaluated_at' => now(),
        'user_id' => auth()->id(),
    ]);
}
```

**Key classes:**
- `CleaniqueCoders\Eligify\Models\Evaluation`
- `CleaniqueCoders\Eligify\Audit\AuditLogger`

### 8. Workflow Execution

Workflow callbacks are executed based on the result:

```php
$workflowManager = new WorkflowManager($criteria);

if ($result->passed()) {
    $workflowManager->executeOnPass($subject, $result);
} else {
    $workflowManager->executeOnFail($subject, $result);
}
```

**Callback types:**

**OnPass:**
```php
->onPass(function ($subject, $result) {
    $subject->approve();
    Notification::send($subject->user, new Approved());
})
```

**OnFail:**
```php
->onFail(function ($subject, $result) {
    Log::warning('Eligibility failed', [
        'subject' => $subject->id,
        'score' => $result->score(),
    ]);
})
```

**Key classes:**
- `CleaniqueCoders\Eligify\Workflow\WorkflowManager`
- `CleaniqueCoders\Eligify\Workflow\Callbacks\*`

### 9. Event Broadcasting

Events are dispatched for the evaluation:

```php
event(new EvaluationCompleted($criteria, $subject, $result));

if ($result->passed()) {
    event(new EligibilityPassed($criteria, $subject, $result));
} else {
    event(new EligibilityFailed($criteria, $subject, $result));
}
```

**Available events:**
- `EvaluationStarted`
- `EvaluationCompleted`
- `EligibilityPassed`
- `EligibilityFailed`
- `RuleEvaluated`

**Key classes:**
- `CleaniqueCoders\Eligify\Events\*`

### 10. Return Result

Finally, the result is returned to the caller:

```php
return $result;
```

The caller can then check the result:

```php
if ($result->passed()) {
    // Handle success
}

$score = $result->score();
$failedRules = $result->failedRules();
```

## Performance Considerations

### Caching

Criteria can be cached to avoid database queries:

```php
$criteria = Cache::remember(
    "eligify:criteria:{$name}",
    3600,
    fn() => Criteria::findByName($name)
);
```

### Result Caching

Results can be cached for identical inputs:

```php
$cacheKey = "eligify:result:{$criteria->name}:" . md5(serialize($subject));
$result = Cache::remember($cacheKey, 300, fn() => $criteria->evaluate($subject));
```

### Lazy Evaluation

Rules can be evaluated lazily with short-circuit logic:

```php
// Stop on first failure for pass/fail scoring
if ($scoringMethod === 'pass_fail' && !$passed) {
    break;
}
```

### Database Optimization

Use eager loading for criteria with many rules:

```php
$criteria = Criteria::with('rules')->findByName($name);
```

## Debugging the Flow

### Enable Detailed Logging

```php
// config/eligify.php
'debug' => [
    'enabled' => env('ELIGIFY_DEBUG', false),
    'log_evaluations' => true,
    'log_rule_results' => true,
],
```

### Use Events for Tracking

```php
Event::listen(RuleEvaluated::class, function ($event) {
    Log::debug('Rule evaluated', [
        'field' => $event->rule->field,
        'operator' => $event->rule->operator,
        'passed' => $event->passed,
    ]);
});
```

### Inspect Result Details

```php
$result = $criteria->evaluate($subject);

dump($result->details());
// Shows execution time, rule breakdown, etc.
```

## Extension Points

You can hook into various points in the lifecycle:

### 1. Custom Data Extraction

```php
class CustomExtractor extends DataExtractor
{
    protected function extract($subject): array
    {
        // Custom logic
        return parent::extract($subject);
    }
}
```

### 2. Custom Operators

```php
class CustomOperator implements OperatorInterface
{
    public function evaluate($actual, $expected): bool
    {
        // Custom comparison logic
    }
}

// Register in config
'operators' => [
    'custom' => CustomOperator::class,
],
```

### 3. Middleware

```php
class EvaluationMiddleware
{
    public function handle($criteria, $subject, $next)
    {
        // Before evaluation

        $result = $next($criteria, $subject);

        // After evaluation

        return $result;
    }
}
```

### 4. Event Listeners

```php
class CustomEvaluationListener
{
    public function handle(EvaluationCompleted $event): void
    {
        // Custom logic after evaluation
    }
}
```

## Error Handling

Exceptions can be thrown at various stages:

```php
try {
    $result = Eligify::criteria('loan_approval')->evaluate($applicant);
} catch (CriteriaNotFoundException $e) {
    // Criteria doesn't exist
} catch (InvalidOperatorException $e) {
    // Unknown operator
} catch (DataExtractionException $e) {
    // Failed to extract data
} catch (EvaluationException $e) {
    // Evaluation failed
}
```

## Summary

The request lifecycle in Eligify is:

1. **Facade** → Entry point
2. **Load/Build** → Get criteria definition
3. **Extract** → Normalize subject data
4. **Evaluate** → Check each rule
5. **Score** → Calculate result
6. **Compile** → Build result object
7. **Audit** → Log evaluation
8. **Workflow** → Execute callbacks
9. **Events** → Broadcast results
10. **Return** → Deliver to caller

Understanding this flow helps you:
- Debug evaluation issues
- Optimize performance
- Add custom behavior
- Extend functionality

## Related Documentation

- [Design Patterns](design-patterns.md) - Patterns used in lifecycle
- [Package Structure](package-structure.md) - Where code lives
- [Extensibility](extensibility.md) - How to extend the lifecycle
