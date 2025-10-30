# Quick Start Guide

Get started with Eligify in 5 minutes! This guide shows you how to create your first eligibility criteria and evaluate it.

## Your First Criteria

Let's create a simple loan approval system.

### Step 1: Define the Criteria

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

$criteria = Eligify::criteria('Loan Approval')
    ->addRule('income', '>=', 3000)
    ->addRule('credit_score', '>=', 650)
    ->addRule('active_loans', '<=', 2);
```

### Step 2: Evaluate Against Data

```php
$applicant = [
    'income' => 5000,
    'credit_score' => 750,
    'active_loans' => 1,
];

$result = $criteria->evaluate($applicant);
```

### Step 3: Check the Result

```php
if ($result->passed()) {
    echo "Loan approved!";
    echo "Score: " . $result->score();
} else {
    echo "Loan denied.";
    echo "Failed rules: " . implode(', ', $result->failedRules());
}
```

## Working with Models

Evaluate Laravel models directly:

```php
use App\Models\User;

$user = User::find(1);

$result = Eligify::criteria('Scholarship')
    ->addRule('gpa', '>=', 3.5)
    ->addRule('attendance_rate', '>=', 0.9)
    ->evaluate($user);
```

## Adding Workflows

Execute callbacks based on results:

```php
$criteria = Eligify::criteria('Membership Upgrade')
    ->addRule('points', '>=', 1000)
    ->addRule('active_months', '>=', 6)
    ->onPass(function ($user) {
        $user->upgrade();
        Notification::send($user, new MembershipUpgraded());
    })
    ->onFail(function ($user) {
        Log::info("User {$user->id} not eligible for upgrade");
    });

$criteria->evaluate($user);
```

## Using Weighted Rules

Assign importance to rules:

```php
$criteria = Eligify::criteria('Job Applicant')
    ->setScoring('weighted')
    ->addRule('experience_years', '>=', 3, weight: 40)
    ->addRule('education_level', 'in', ['bachelor', 'master'], weight: 30)
    ->addRule('skills_match', '>=', 0.7, weight: 30);

$result = $criteria->evaluate($applicant);

echo "Total score: " . $result->score() . "/100";
```

## Saving Criteria

Persist criteria for reuse:

```php
$criteria = Eligify::criteria('Insurance Eligibility')
    ->addRule('age', 'between', [18, 65])
    ->addRule('health_score', '>=', 60)
    ->save();

// Later, load and use it
$criteria = Eligify::load('insurance_eligibility');
$result = $criteria->evaluate($person);
```

## Using the Playground

Test your criteria interactively:

1. Navigate to `/eligify/playground`
2. Select or create a criterion
3. Input test data in JSON format
4. Click "Evaluate"
5. View results and debug information

```json
{
  "income": 5000,
  "credit_score": 750,
  "active_loans": 1
}
```

## Common Patterns

### Age Verification

```php
Eligify::criteria('Age Verify')
    ->addRule('age', '>=', 18)
    ->evaluate($user);
```

### Range Check

```php
Eligify::criteria('Temperature Check')
    ->addRule('temperature', 'between', [36.1, 37.2])
    ->evaluate($reading);
```

### Multiple Options

```php
Eligify::criteria('Region Check')
    ->addRule('country', 'in', ['US', 'CA', 'UK'])
    ->evaluate($location);
```

### Nested Data

```php
Eligify::criteria('Profile Completeness')
    ->addRule('profile.bio', 'not_empty')
    ->addRule('profile.avatar', 'not_empty')
    ->addRule('profile.verified', 'equals', true)
    ->evaluate($user);
```

## Available Operators

| Operator | Description | Example |
|----------|-------------|---------|
| `==`, `equals` | Equal to | `->addRule('status', '==', 'active')` |
| `!=`, `not_equals` | Not equal to | `->addRule('type', '!=', 'banned')` |
| `>`, `greater_than` | Greater than | `->addRule('age', '>', 18)` |
| `>=`, `gte` | Greater or equal | `->addRule('score', '>=', 70)` |
| `<`, `less_than` | Less than | `->addRule('debts', '<', 1000)` |
| `<=`, `lte` | Less or equal | `->addRule('attempts', '<=', 3)` |
| `in`, `contains` | In array | `->addRule('role', 'in', ['admin', 'editor'])` |
| `not_in` | Not in array | `->addRule('status', 'not_in', ['banned', 'suspended'])` |
| `between` | Between range | `->addRule('age', 'between', [18, 65])` |
| `empty` | Is empty | `->addRule('notes', 'empty')` |
| `not_empty` | Is not empty | `->addRule('email', 'not_empty')` |

## Scoring Methods

### Weighted (Default)

```php
->setScoring('weighted') // Rules have weights, total score out of 100
```

### Pass/Fail

```php
->setScoring('pass_fail') // All rules must pass, score is 0 or 100
```

### Sum

```php
->setScoring('sum') // Sum of all rule values
```

### Average

```php
->setScoring('average') // Average of all rule values
```

## Next Steps

- [Usage Guide](usage-guide.md) - Comprehensive examples
- [Core Concepts](core-concepts.md) - Deep dive into architecture
- [Core Features](../03-core-features/) - Advanced features
- [Examples](../13-examples/) - Real-world use cases

## Need Help?

- Check [FAQ](../15-appendix/faq.md)
- Browse [Examples](../13-examples/)
- Review [API Reference](../14-reference/)
