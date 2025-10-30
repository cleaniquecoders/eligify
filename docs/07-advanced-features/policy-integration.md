# Policy Integration

The Eligify package provides a `HasEligibility` trait that can be used in Laravel policies to add powerful eligibility checking capabilities.

## Installation

Simply use the trait in your policy class:

```php
<?php

namespace App\Policies;

use CleaniqueCoders\Eligify\Concerns\HasEligibility;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization, HasEligibility;

    public function update(User $user, User $model)
    {
        // Check if user meets admin criteria
        if ($this->hasEligibility($user, 'admin_privileges')) {
            return true;
        }

        // Check if user can update their own profile
        return $user->id === $model->id &&
               $this->hasEligibility($user, 'profile_update');
    }
}
```

## Available Methods

### Basic Eligibility Checks

#### `hasEligibility(Model $model, string $criteriaName): bool`

Simple boolean check for eligibility:

```php
if ($this->hasEligibility($user, 'loan_approval')) {
    // User is eligible for loan
}
```

#### `checkEligibility(Model $model, string $criteriaName): array`

Get detailed eligibility results:

```php
$result = $this->checkEligibility($user, 'loan_approval');
// Returns: ['passed' => true, 'score' => 85, 'decision' => 'Approved', 'failed_rules' => []]
```

### Custom Criteria Builder

#### `checkCriteria(Model $model, Closure $criteriaBuilder): array`

Define criteria on-the-fly:

```php
$result = $this->checkCriteria($user, function ($criteria) {
    $criteria->addRule('age', '>=', 18)
            ->addRule('income', '>=', 25000)
            ->addRule('credit_score', '>=', 650);
});
```

### Model-based Evaluation

#### `evaluateModel(Model $model, Criteria $criteria): array`

Evaluate against an existing criteria model:

```php
$criteria = Criteria::where('slug', 'membership-eligibility')->first();
$result = $this->evaluateModel($user, $criteria);
```

### Score-based Checks

#### `hasMinimumScore(Model $model, string $criteriaName, int $minimumScore): bool`

Check if model meets minimum score threshold:

```php
if ($this->hasMinimumScore($user, 'premium_membership', 90)) {
    // User qualifies for premium features
}
```

### Batch Operations

#### `checkBatchEligibility(array $models, string $criteriaName): array`

Check multiple models at once:

```php
$users = User::where('status', 'pending')->get();
$results = $this->checkBatchEligibility($users->toArray(), 'approval_criteria');

foreach ($results as $userId => $result) {
    if ($result['passed']) {
        // Process approved user
    }
}
```

### Status and Messaging

#### `getEligibilityStatus(Model $model, string $criteriaName): array`

Get status with human-readable messages:

```php
$status = $this->getEligibilityStatus($user, 'loan_approval');
// Returns: ['eligible' => true, 'score' => 85, 'message' => 'Eligible (Score: 85)', ...]
```

### Multiple Criteria Checks

#### `passesAnyCriteria(Model $model, array $criteriaNames): bool`

Check if model passes any of the given criteria:

```php
if ($this->passesAnyCriteria($user, ['basic_plan', 'premium_plan', 'enterprise_plan'])) {
    // User qualifies for at least one plan
}
```

#### `passesAllCriteria(Model $model, array $criteriaNames): bool`

Check if model passes all criteria:

```php
if ($this->passesAllCriteria($user, ['identity_verified', 'credit_approved', 'income_verified'])) {
    // User meets all requirements
}
```

#### `checkMultipleCriteria(Model $model, array $criteriaNames): array`

Get detailed results for multiple criteria:

```php
$results = $this->checkMultipleCriteria($user, ['basic_check', 'premium_check']);
// Returns: ['basic_check' => [...], 'premium_check' => [...]]
```

## Data Extraction

The trait automatically extracts data from your models for evaluation. By default, it:

- Gets all model attributes
- Adds computed fields like `created_days_ago` and `updated_days_ago`
- Includes loaded relationship data and counts

### Custom Data Extraction

Override the `extractModelData()` method in your policy to customize:

```php
protected function extractModelData(Model $model): array
{
    $data = parent::extractModelData($model);

    // Add custom computed fields
    if ($model instanceof User) {
        $data['account_age_days'] = $model->created_at->diffInDays(now());
        $data['total_orders'] = $model->orders()->count();
        $data['average_order_value'] = $model->orders()->avg('total');
    }

    return $data;
}
```

## Real-world Examples

### Loan Approval Policy

```php
class LoanPolicy
{
    use HasEligibility;

    public function approve(User $user, Loan $loan)
    {
        // Check basic eligibility
        if (!$this->hasEligibility($loan->applicant, 'loan_basic_requirements')) {
            return false;
        }

        // Check amount-specific criteria
        $amountCriteria = $loan->amount > 100000 ? 'large_loan_criteria' : 'standard_loan_criteria';

        return $this->hasMinimumScore($loan->applicant, $amountCriteria, 75);
    }
}
```

### Membership Upgrade Policy

```php
class MembershipPolicy
{
    use HasEligibility;

    public function upgrade(User $user, string $targetPlan)
    {
        $result = $this->checkCriteria($user, function ($criteria) use ($targetPlan) {
            switch ($targetPlan) {
                case 'premium':
                    $criteria->addRule('account_age_days', '>=', 30)
                            ->addRule('total_purchases', '>=', 5)
                            ->addRule('support_tickets', '<=', 2);
                    break;
                case 'enterprise':
                    $criteria->addRule('team_size', '>=', 10)
                            ->addRule('monthly_usage', '>=', 1000)
                            ->addRule('payment_history_months', '>=', 12);
                    break;
            }
        });

        return $result['passed'];
    }
}
```

## Error Handling

The trait includes built-in error handling:

- Returns `false` for simple boolean checks when errors occur
- Returns error structure for detailed checks
- Logs errors when debug mode is enabled
- Configurable error behavior through package configuration

```php
// In config/eligify.php
'debug' => env('ELIGIFY_DEBUG', false),
'workflow' => [
    'fail_on_callback_error' => false,
    'log_callback_errors' => true,
],
```

## Configuration

The trait respects all Eligify package configuration settings. You can customize behavior through the `config/eligify.php` file:

```php
return [
    'debug' => env('ELIGIFY_DEBUG', false),
    'rule_weights' => [
        'low' => 1,
        'medium' => 3,
        'high' => 5,
        'critical' => 10,
    ],
    // ... other configuration options
];
```
