# Criteria Builder

The Criteria Builder provides a fluent interface for constructing eligibility criteria programmatically.

## Overview

The builder pattern allows you to create criteria definitions using chainable methods, making the code readable and maintainable.

## Basic Usage

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

$criteria = Eligify::criteria('Loan Approval')
    ->addRule('income', '>=', 3000)
    ->addRule('credit_score', '>=', 650)
    ->setScoring('weighted')
    ->evaluate($applicant);
```

## Creating Criteria

### Named Criteria

```php
$criteria = Eligify::criteria('Scholarship Eligibility);
```

### Anonymous Criteria

```php
$criteria = Eligify::criteria(); // No name, one-time use
```

## Adding Rules

### Basic Rules

```php
->addRule($field, $operator, $value)
```

**Example:**

```php
->addRule('age', '>=', 18)
->addRule('country', '==', 'US')
->addRule('status', '!=', 'banned')
```

### Weighted Rules

```php
->addRule($field, $operator, $value, weight: $weight)
```

**Example:**

```php
->addRule('income', '>=', 3000, weight: 40)
->addRule('credit_score', '>=', 650, weight: 60)
```

### Rules with Labels

```php
->addRule($field, $operator, $value, label: $label)
```

**Example:**

```php
->addRule('age', '>=', 18, label: 'Must be 18 or older')
->addRule('gpa', '>=', 3.0, label: 'Minimum GPA requirement')
```

### Complex Rules

```php
->addRule('profile.address.country', 'in', ['US', 'CA', 'UK'])
->addRule('subscriptions', 'not_empty')
->addRule('created_at', '>=', now()->subYear())
```

## Configuring Scoring

### Set Scoring Method

```php
->setScoring('weighted')    // Weighted scoring (default)
->setScoring('pass_fail')   // All must pass
->setScoring('sum')         // Sum of weights
->setScoring('average')     // Average pass rate
```

### Set Passing Threshold

```php
->setThreshold(70)  // Require 70+ score to pass
```

**Example:**

```php
Eligify::criteria('Premium Upgrade')
    ->setScoring('weighted')
    ->setThreshold(80)  // Need 80% to qualify
    ->addRule('points', '>=', 1000, weight: 50)
    ->addRule('active_months', '>=', 6, weight: 50);
```

## Adding Workflows

### OnPass Callback

Execute when evaluation passes:

```php
->onPass(function ($subject) {
    $subject->approve();
    Notification::send($subject->user, new Approved());
})
```

### OnFail Callback

Execute when evaluation fails:

```php
->onFail(function ($subject) {
    Log::warning('Eligibility failed', ['subject' => $subject->id]);
    $subject->reject();
})
```

### Both Callbacks

```php
Eligify::criteria('Membership Upgrade')
    ->addRule('points', '>=', 1000)
    ->onPass(fn($user) => $user->upgradeMembership())
    ->onFail(fn($user) => $user->sendUpgradeReminder())
    ->evaluate($user);
```

## Rule Groups

Group related rules together:

```php
Eligify::criteria('Comprehensive Check')
    ->addRuleGroup('identity', [
        ['field' => 'email_verified', 'operator' => '==', 'value' => true],
        ['field' => 'phone_verified', 'operator' => '==', 'value' => true],
    ])
    ->addRuleGroup('activity', [
        ['field' => 'last_login', 'operator' => '>=', 'value' => now()->subDays(30)],
        ['field' => 'post_count', 'operator' => '>=', 'value' => 10],
    ]);
```

## Conditional Rules

Add rules based on conditions:

```php
$builder = Eligify::criteria('Dynamic Criteria')
    ->addRule('age', '>=', 18);

if ($requiresVerification) {
    $builder->addRule('identity_verified', '==', true);
}

if ($isPremiumTier) {
    $builder->addRule('premium_since', '<=', now()->subMonths(3));
}

$result = $builder->evaluate($user);
```

## Copying and Modifying

### Clone Existing Criteria

```php
$baseCriteria = Eligify::criteria('Base Approval')
    ->addRule('age', '>=', 18)
    ->addRule('country', 'in', ['US', 'CA']);

$premiumCriteria = $baseCriteria->clone()
    ->addRule('premium_member', '==', true)
    ->addRule('account_age', '>=', 365);
```

## Metadata

### Add Description

```php
->setDescription('Determines eligibility for premium membership upgrade')
```

### Add Tags

```php
->addTag('premium')
->addTag('membership')
->addTag('automated')
```

### Add Metadata

```php
->setMetadata([
    'version' => '2.0',
    'author' => 'System',
    'department' => 'Marketing',
    'requires_approval' => false,
])
```

## Validation

### Validate Before Save

```php
try {
    $criteria = Eligify::criteria('Test')
        ->addRule('invalid_field', 'unknown_operator', 'value')
        ->validate()
        ->save();
} catch (ValidationException $e) {
    dump($e->errors());
}
```

### Validation Rules

The builder validates:

- Criteria name uniqueness (if saving)
- Operator validity
- Rule field names (non-empty)
- Weight values (positive numbers)
- Threshold values (0-100 for weighted)

## Persistence

### Save to Database

```php
$criteria = Eligify::criteria('Loan Approval')
    ->addRule('income', '>=', 3000)
    ->addRule('credit_score', '>=', 650)
    ->save();  // Persists to database

// Returns Criteria model
echo $criteria->id;  // Database ID
```

### Load from Database

```php
$criteria = Eligify::load('Loan Approval');
```

### Update Existing

```php
$criteria = Eligify::load('Loan Approval')
    ->addRule('employment_status', '==', 'employed')
    ->save();  // Updates existing record
```

### Delete

```php
$criteria = Eligify::load('Loan Approval');
$criteria->delete();
```

## Batch Operations

### Add Multiple Rules

```php
$rules = [
    ['field' => 'age', 'operator' => '>=', 'value' => 18, 'weight' => 20],
    ['field' => 'income', 'operator' => '>=', 'value' => 3000, 'weight' => 40],
    ['field' => 'credit_score', 'operator' => '>=', 'value' => 650, 'weight' => 40],
];

Eligify::criteria('Loan Approval')
    ->addRules($rules)
    ->save();
```

### Import from Array

```php
$definition = [
    'name' => 'premium_check',
    'scoring' => 'weighted',
    'threshold' => 75,
    'rules' => [
        ['field' => 'points', 'operator' => '>=', 'value' => 1000, 'weight' => 50],
        ['field' => 'active', 'operator' => '==', 'value' => true, 'weight' => 50],
    ],
];

$criteria = Eligify::import($definition);
```

### Export to Array

```php
$criteria = Eligify::load('Premium Check');
$array = $criteria->toArray();

/*
[
    'name' => 'premium_check',
    'scoring' => 'weighted',
    'threshold' => 75,
    'rules' => [
        ['field' => 'points', 'operator' => '>=', 'value' => 1000, 'weight' => 50],
        ['field' => 'active', 'operator' => '==', 'value' => true, 'weight' => 50],
    ],
]
*/
```

## Advanced Builder Methods

### Set Priority

```php
->setPriority('high')  // Affects evaluation order in batch operations
```

### Enable/Disable

```php
->setEnabled(true)   // Enable criteria
->setEnabled(false)  // Disable (won't evaluate)
```

### Set Expiry

```php
->setExpiresAt(now()->addDays(30))  // Criteria expires in 30 days
```

### Set Owner

```php
->setOwner(auth()->user())  // Associate with a user
```

## Chaining Example

Complete example showing multiple features:

```php
$criteria = Eligify::criteria('Comprehensive Loan Approval')
    ->setDescription('Full loan approval criteria for standard applicants')
    ->setScoring('weighted')
    ->setThreshold(75)
    ->addTag('finance')
    ->addTag('loans')
    ->addRule('age', 'between', [18, 70], weight: 10, label: 'Age requirement')
    ->addRule('income', '>=', 3000, weight: 30, label: 'Minimum income')
    ->addRule('credit_score', '>=', 650, weight: 40, label: 'Credit score requirement')
    ->addRule('employment_status', 'in', ['employed', 'self-employed'], weight: 10, label: 'Employment status')
    ->addRule('existing_loans', '<=', 3, weight: 10, label: 'Existing loan limit')
    ->onPass(function ($application) {
        $application->approve();
        event(new LoanApproved($application));
    })
    ->onFail(function ($application) {
        $application->reject();
        event(new LoanRejected($application));
    })
    ->setMetadata([
        'version' => '3.0',
        'effective_date' => '2025-01-01',
        'department' => 'Lending',
    ])
    ->save();
```

## Builder API Reference

### Core Methods

| Method | Description |
|--------|-------------|
| `criteria($name = null)` | Create new criteria builder |
| `load($name)` | Load existing criteria |
| `addRule($field, $operator, $value, $weight = 1)` | Add single rule |
| `addRules(array $rules)` | Add multiple rules |
| `setScoring($method)` | Set scoring method |
| `setThreshold($value)` | Set passing threshold |
| `onPass(Closure $callback)` | Add onPass workflow |
| `onFail(Closure $callback)` | Add onFail workflow |
| `save()` | Persist to database |
| `evaluate($subject)` | Evaluate against subject |

### Metadata Methods

| Method | Description |
|--------|-------------|
| `setDescription($text)` | Set description |
| `addTag($tag)` | Add tag |
| `setMetadata(array $data)` | Set metadata |
| `setPriority($priority)` | Set priority |
| `setEnabled($enabled)` | Enable/disable |
| `setExpiresAt($date)` | Set expiry date |
| `setOwner($user)` | Set owner |

### Utility Methods

| Method | Description |
|--------|-------------|
| `validate()` | Validate criteria |
| `clone()` | Clone criteria |
| `toArray()` | Export to array |
| `import(array $data)` | Import from array |
| `delete()` | Delete criteria |

## Related Documentation

- [Rule Engine](rule-engine.md) - How rules are evaluated
- [Scoring Methods](scoring-methods.md) - Scoring algorithms
- [Evaluation Engine](evaluation-engine.md) - Evaluation process
- [Workflow Callbacks](workflow-callbacks.md) - Workflow system
