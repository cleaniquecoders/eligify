# Scoring Methods

Eligify supports multiple scoring methods to calculate eligibility scores based on rule evaluation results.

## Available Scoring Methods

### 1. Weighted Scoring (Default)

Rules have individual weights, and the score is calculated as a percentage of total possible weight.

**How it works:**
- Each rule has a weight (default: 1)
- Score = (sum of passed rule weights / total weight) × 100
- Result is 0-100

**Example:**

```php
Eligify::criteria('loan_approval')
    ->setScoring('weighted')
    ->addRule('income', '>=', 3000, weight: 40)
    ->addRule('credit_score', '>=', 650, weight: 60)
    ->evaluate($applicant);
```

**Calculation:**
- If both pass: (40 + 60) / 100 × 100 = 100%
- If only income passes: 40 / 100 × 100 = 40%
- If only credit passes: 60 / 100 × 100 = 60%
- If both fail: 0 / 100 × 100 = 0%

**Use cases:**
- Loan approvals
- Job candidate screening
- Scholarship eligibility
- Any scenario where some criteria are more important than others

### 2. Pass/Fail Scoring

All rules must pass for eligibility. Score is either 100 (all pass) or 0 (any fail).

**How it works:**
- All rules must pass
- Score = 100 if all pass, 0 otherwise
- No partial credit

**Example:**

```php
Eligify::criteria('age_verification')
    ->setScoring('pass_fail')
    ->addRule('age', '>=', 18)
    ->addRule('id_verified', '==', true)
    ->addRule('terms_accepted', '==', true)
    ->evaluate($user);
```

**Calculation:**
- All pass: Score = 100
- Any fail: Score = 0

**Use cases:**
- Age verification
- Compliance checks
- Security requirements
- Mandatory prerequisites

### 3. Sum Scoring

Score is the sum of all passed rule weights.

**How it works:**
- Add weights of all passed rules
- No normalization to 100
- Score can exceed 100

**Example:**

```php
Eligify::criteria('loyalty_points')
    ->setScoring('sum')
    ->addRule('purchased_this_month', '==', true, weight: 10)
    ->addRule('referred_friend', '==', true, weight: 20)
    ->addRule('review_submitted', '==', true, weight: 15)
    ->addRule('social_share', '==', true, weight: 5)
    ->evaluate($customer);
```

**Calculation:**
- All pass: 10 + 20 + 15 + 5 = 50 points
- Only purchase and referral: 10 + 20 = 30 points

**Use cases:**
- Points systems
- Achievement tracking
- Bonus calculations
- Accumulative scoring

### 4. Average Scoring

Score is the percentage of rules that passed.

**How it works:**
- Count passed rules
- Score = (passed count / total count) × 100
- Weights are ignored

**Example:**

```php
Eligify::criteria('profile_completeness')
    ->setScoring('average')
    ->addRule('bio', 'not_empty')
    ->addRule('avatar', 'not_empty')
    ->addRule('location', 'not_empty')
    ->addRule('website', 'not_empty')
    ->evaluate($profile);
```

**Calculation:**
- 4 rules, all pass: 4/4 × 100 = 100%
- 4 rules, 3 pass: 3/4 × 100 = 75%
- 4 rules, 2 pass: 2/4 × 100 = 50%

**Use cases:**
- Profile completeness
- Feature adoption
- Task completion
- Equal-weight criteria

## Configuring Scoring

### Set Scoring Method

```php
->setScoring('weighted')   // Weighted (default)
->setScoring('pass_fail')  // Pass/Fail
->setScoring('sum')        // Sum
->setScoring('average')    // Average
```

### Set Passing Threshold

```php
->setThreshold(70)  // Require 70 score to pass
```

**Example:**

```php
Eligify::criteria('premium_upgrade')
    ->setScoring('weighted')
    ->setThreshold(80)  // Need 80% to qualify
    ->addRule('points', '>=', 1000, weight: 50)
    ->addRule('active_months', '>=', 6, weight: 50);
```

## Comparison Table

| Method | Weights Matter? | Score Range | Best For |
|--------|----------------|-------------|----------|
| **Weighted** | Yes | 0-100 | Varied importance |
| **Pass/Fail** | No | 0 or 100 | All mandatory |
| **Sum** | Yes | 0-∞ | Points accumulation |
| **Average** | No | 0-100 | Equal importance |

## Custom Scoring Methods

Create custom scorers for specialized algorithms:

```php
namespace App\Eligify\Scorers;

use CleaniqueCoders\Eligify\Engine\Contracts\ScorerInterface;

class TieredScorer implements ScorerInterface
{
    public function calculate(array $rules, array $results): float
    {
        $passedCount = count(array_filter($results));
        $totalCount = count($rules);
        $percentage = ($passedCount / $totalCount) * 100;

        // Tiered scoring
        return match(true) {
            $percentage >= 90 => 100,
            $percentage >= 75 => 85,
            $percentage >= 60 => 70,
            $percentage >= 50 => 55,
            default => 0,
        };
    }

    public function isPassing(float $score, float $threshold): bool
    {
        return $score >= $threshold;
    }
}
```

Register:

```php
// config/eligify.php
'scoring' => [
    'methods' => [
        'tiered' => \App\Eligify\Scorers\TieredScorer::class,
    ],
],
```

Use:

```php
->setScoring('tiered')
```

## Examples by Use Case

### Loan Approval (Weighted)

```php
Eligify::criteria('loan_approval')
    ->setScoring('weighted')
    ->setThreshold(75)
    ->addRule('credit_score', '>=', 650, weight: 40)
    ->addRule('income', '>=', 3000, weight: 30)
    ->addRule('employment_years', '>=', 2, weight: 20)
    ->addRule('existing_loans', '<=', 2, weight: 10);
```

### Age Verification (Pass/Fail)

```php
Eligify::criteria('age_verify')
    ->setScoring('pass_fail')
    ->addRule('age', '>=', 18)
    ->addRule('id_verified', '==', true);
```

### Rewards Program (Sum)

```php
Eligify::criteria('monthly_rewards')
    ->setScoring('sum')
    ->setThreshold(100)
    ->addRule('purchase_made', '==', true, weight: 50)
    ->addRule('review_left', '==', true, weight: 30)
    ->addRule('referral', '==', true, weight: 40)
    ->addRule('social_share', '==', true, weight: 20);
```

### Profile Setup (Average)

```php
Eligify::criteria('profile_complete')
    ->setScoring('average')
    ->setThreshold(80)
    ->addRule('name', 'not_empty')
    ->addRule('email', 'not_empty')
    ->addRule('phone', 'not_empty')
    ->addRule('address', 'not_empty')
    ->addRule('avatar', 'not_empty');
```

## Related Documentation

- [Criteria Builder](criteria-builder.md) - Building criteria
- [Rule Engine](rule-engine.md) - Rule evaluation
- [Evaluation Engine](evaluation-engine.md) - Complete process
