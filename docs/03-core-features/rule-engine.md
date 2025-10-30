# Rule Engine

The Rule Engine is the core component that evaluates individual rules and determines whether conditions are met.

## Overview

The Rule Engine processes rules by:
1. Extracting field values from subject data
2. Applying the appropriate operator
3. Comparing actual vs expected values
4. Returning pass/fail results

## How Rules Work

### Rule Anatomy

A rule consists of four parts:

```php
->addRule($field, $operator, $value, $weight)
```

- **Field**: The data attribute to check (e.g., `'income'`, `'age'`)
- **Operator**: The comparison method (e.g., `'>='`, `'=='`, `'in'`)
- **Value**: The expected value to compare against
- **Weight**: Optional importance factor for scoring

### Example Rule

```php
->addRule('credit_score', '>=', 650, weight: 40)
```

This rule checks if the credit score is greater than or equal to 650, and assigns it a weight of 40 points.

## Operators

### Comparison Operators

#### Equals (`==`, `equals`)

Exact match comparison:

```php
->addRule('status', '==', 'active')
->addRule('verified', 'equals', true)
```

#### Not Equals (`!=`, `not_equals`)

Not equal comparison:

```php
->addRule('type', '!=', 'banned')
->addRule('status', 'not_equals', 'suspended')
```

#### Greater Than (`>`, `greater_than`)

Numeric greater than:

```php
->addRule('age', '>', 18)
->addRule('score', 'greater_than', 100)
```

#### Greater or Equal (`>=`, `gte`)

Numeric greater than or equal to:

```php
->addRule('income', '>=', 3000)
->addRule('points', 'gte', 1000)
```

#### Less Than (`<`, `less_than`)

Numeric less than:

```php
->addRule('debt', '<', 10000)
->addRule('attempts', 'less_than', 3)
```

#### Less or Equal (`<=`, `lte`)

Numeric less than or equal to:

```php
->addRule('age', '<=', 65)
->addRule('active_loans', 'lte', 2)
```

### Membership Operators

#### In Array (`in`, `contains`)

Value exists in array:

```php
->addRule('country', 'in', ['US', 'CA', 'UK'])
->addRule('role', 'contains', ['admin', 'editor'])
```

#### Not In Array (`not_in`)

Value not in array:

```php
->addRule('status', 'not_in', ['banned', 'suspended', 'deleted'])
```

#### Between (`between`)

Value within range (inclusive):

```php
->addRule('age', 'between', [18, 65])
->addRule('score', 'between', [0, 100])
```

### Existence Operators

#### Empty (`empty`)

Field is empty, null, or zero:

```php
->addRule('notes', 'empty')
->addRule('warnings', 'empty')
```

#### Not Empty (`not_empty`)

Field has a value:

```php
->addRule('email', 'not_empty')
->addRule('phone', 'not_empty')
```

#### Exists (`exists`)

Field exists in data:

```php
->addRule('profile', 'exists')
->addRule('settings', 'exists')
```

#### Not Exists (`not_exists`)

Field doesn't exist:

```php
->addRule('deleted_at', 'not_exists')
```

### String Operators

#### Starts With (`starts_with`)

String begins with value:

```php
->addRule('email', 'starts_with', 'admin@')
->addRule('code', 'starts_with', 'PRE')
```

#### Ends With (`ends_with`)

String ends with value:

```php
->addRule('email', 'ends_with', '@company.com')
->addRule('file', 'ends_with', '.pdf')
```

#### Contains Text (`contains_text`)

String contains substring:

```php
->addRule('description', 'contains_text', 'premium')
->addRule('tags', 'contains_text', 'featured')
```

#### Matches Pattern (`matches`, `regex`)

Matches regular expression:

```php
->addRule('phone', 'matches', '/^\+1\d{10}$/')
->addRule('postal_code', 'regex', '/^[A-Z]\d[A-Z]\s?\d[A-Z]\d$/')
```

### Date/Time Operators

#### Before (`before`)

Date is before specified date:

```php
->addRule('birth_date', 'before', now()->subYears(18))
->addRule('expires_at', 'before', now())
```

#### After (`after`)

Date is after specified date:

```php
->addRule('created_at', 'after', now()->subYear())
->addRule('last_login', 'after', now()->subDays(30))
```

#### Date Between (`date_between`)

Date within range:

```php
->addRule('joined_at', 'date_between', [
    now()->subYear(),
    now()->subMonths(6)
])
```

## Field Access

### Dot Notation

Access nested fields using dot notation:

```php
->addRule('profile.address.country', '==', 'US')
->addRule('settings.notifications.email', '==', true)
->addRule('user.subscription.status', '==', 'active')
```

### Array Access

Access array elements:

```php
->addRule('tags.0', '==', 'featured')  // First tag
->addRule('scores.math', '>=', 80)     // Math score
```

### Relationship Access

Access related models (when using mappers):

```php
->addRule('user.credit_score', '>=', 650)
->addRule('account.balance', '>', 0)
->addRule('subscription.plan.name', '==', 'Premium')
```

## Rule Evaluation Process

### Step-by-Step Evaluation

1. **Extract Field Value**

```php
$value = data_get($subject, $rule->field);
// e.g., data_get($user, 'profile.address.country')
```

2. **Get Operator Instance**

```php
$operator = OperatorFactory::make($rule->operator);
// Returns instance of appropriate operator class
```

3. **Perform Comparison**

```php
$passed = $operator->evaluate($value, $rule->expected_value);
// true or false
```

4. **Store Result**

```php
$result = [
    'rule' => $rule,
    'passed' => $passed,
    'actual' => $value,
    'expected' => $rule->expected_value,
];
```

### Example Evaluation

```php
// Rule: credit_score >= 650
// Subject: User with credit_score = 750

$value = $user->credit_score;  // 750
$operator = new GreaterThanOrEqualOperator();
$passed = $operator->evaluate(750, 650);  // true
```

## Custom Operators

Create custom operators for specialized logic:

```php
namespace App\Eligify\Operators;

use CleaniqueCoders\Eligify\Engine\Contracts\OperatorInterface;

class DivisibleByOperator implements OperatorInterface
{
    public function evaluate(mixed $actual, mixed $expected): bool
    {
        if (!is_numeric($actual) || !is_numeric($expected)) {
            return false;
        }

        return $expected != 0 && $actual % $expected === 0;
    }

    public function validate(mixed $value): bool
    {
        return is_numeric($value);
    }

    public function getDescription(): string
    {
        return 'Checks if value is divisible by expected value';
    }
}
```

Register in config:

```php
// config/eligify.php
'operators' => [
    'divisible_by' => \App\Eligify\Operators\DivisibleByOperator::class,
],
```

Use in criteria:

```php
->addRule('quantity', 'divisible_by', 12)  // Must be sold in dozens
```

## Rule Chaining Logic

### All Rules Must Pass (AND)

Default behavior - all rules must pass:

```php
Eligify::criteria('strict_approval')
    ->setScoring('pass_fail')
    ->addRule('age', '>=', 18)
    ->addRule('income', '>=', 3000)
    ->addRule('credit_score', '>=', 650);
// All three must pass
```

### Weighted Rules (Partial Pass)

Some rules can fail with weighted scoring:

```php
Eligify::criteria('flexible_approval')
    ->setScoring('weighted')
    ->setThreshold(70)
    ->addRule('age', '>=', 18, weight: 20)
    ->addRule('income', '>=', 3000, weight: 40)
    ->addRule('credit_score', '>=', 650, weight: 40);
// Need 70+ points to pass (can fail age rule)
```

### Rule Groups (OR Logic)

Group rules with OR logic:

```php
// Pass if EITHER verification method succeeds
Eligify::criteria('verification')
    ->addRuleGroup('email_or_phone', [
        ['field' => 'email_verified', 'operator' => '==', 'value' => true],
        ['field' => 'phone_verified', 'operator' => '==', 'value' => true],
    ], logic: 'or');
```

## Error Handling

### Invalid Field

```php
try {
    $result = $criteria->evaluate($subject);
} catch (FieldNotFoundException $e) {
    // Field doesn't exist in subject data
    Log::error("Field not found: {$e->getField()}");
}
```

### Invalid Operator

```php
try {
    Eligify::criteria('test')
        ->addRule('age', 'invalid_operator', 18);
} catch (InvalidOperatorException $e) {
    // Unknown operator
}
```

### Type Mismatch

```php
// Attempting numeric comparison on string
->addRule('name', '>=', 18)  // Will fail evaluation
```

The engine handles type mismatches gracefully, returning false for incompatible comparisons.

## Performance Optimization

### Early Termination

For pass/fail scoring, stop on first failure:

```php
// config/eligify.php
'engine' => [
    'early_termination' => true,  // Stop on first failed rule
],
```

### Caching

Cache operator instances:

```php
// Operators are singletons by default
$operator = OperatorFactory::make('>=');  // Cached
```

### Lazy Evaluation

Rules are only evaluated when needed:

```php
$criteria = Eligify::criteria('test')
    ->addRule('expensive_calculation', '>=', 100);

// Rule not evaluated until evaluate() is called
$result = $criteria->evaluate($subject);
```

## Testing Rules

### Unit Test Example

```php
test('income rule passes for qualified applicant', function () {
    $engine = new RuleEngine();
    $rule = new Rule([
        'field' => 'income',
        'operator' => '>=',
        'value' => 3000,
    ]);

    $result = $engine->evaluateRule($rule, ['income' => 5000]);

    expect($result)->toBeTrue();
});

test('income rule fails for unqualified applicant', function () {
    $engine = new RuleEngine();
    $rule = new Rule([
        'field' => 'income',
        'operator' => '>=',
        'value' => 3000,
    ]);

    $result = $engine->evaluateRule($rule, ['income' => 2000]);

    expect($result)->toBeFalse();
});
```

## Debugging Rules

Enable debug mode to see detailed evaluation:

```php
// config/eligify.php
'debug' => true,

// Or per-evaluation
$result = $criteria->evaluate($subject, debug: true);

dump($result->getDebugInfo());
/*
[
    'rules_evaluated' => 3,
    'rules_passed' => 2,
    'rules_failed' => 1,
    'execution_time_ms' => 12.5,
    'details' => [
        ['rule' => 'income >= 3000', 'passed' => true, 'actual' => 5000],
        ['rule' => 'credit_score >= 650', 'passed' => true, 'actual' => 750],
        ['rule' => 'age >= 21', 'passed' => false, 'actual' => 19],
    ],
]
*/
```

## Related Documentation

- [Criteria Builder](criteria-builder.md) - Building criteria
- [Scoring Methods](scoring-methods.md) - How scores are calculated
- [Evaluation Engine](evaluation-engine.md) - Complete evaluation process
- [Extensibility](../02-architecture/extensibility.md) - Custom operators
