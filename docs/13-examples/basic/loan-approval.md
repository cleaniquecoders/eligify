# Loan Approval Example

A basic loan approval eligibility system.

## Use Case

A bank needs to determine if applicants are eligible for a personal loan based on income and credit score.

## Business Rules

1. Minimum income: $3,000/month
2. Minimum credit score: 650
3. Both criteria must be met for approval

## Implementation

```php
use CleaniqueCoders\Eligify\Facades\Eligify;
use App\Models\User;
use App\Notifications\LoanApprovedNotification;
use App\Notifications\LoanRejectedNotification;

// Define eligibility criteria
$result = Eligify::criteria('loan_approval')
    ->addRule('income', '>=', 3000)
    ->addRule('credit_score', '>=', 650)
    ->scoringMethod('pass_fail')
    ->onPass(function ($applicant) {
        $applicant->notify(new LoanApprovedNotification());
        $applicant->update(['loan_status' => 'approved']);
    })
    ->onFail(function ($applicant, $result) {
        $applicant->notify(new LoanRejectedNotification($result));
        $applicant->update(['loan_status' => 'rejected']);
    })
    ->evaluate($applicant);

// Check result
if ($result->passed()) {
    echo "Loan approved!";
} else {
    echo "Loan denied. Failed rules: " . implode(', ', $result->failedRules);
}
```

## With Model Integration

```php
// app/Models/User.php
use CleaniqueCoders\Eligify\Concerns\HasEligibility;

class User extends Model
{
    use HasEligibility;

    public function applyForLoan(): EvaluationResult
    {
        return $this->checkEligibility('loan_approval', [
            ['field' => 'income', 'operator' => '>=', 'value' => 3000],
            ['field' => 'credit_score', 'operator' => '>=', 'value' => 650],
        ]);
    }
}

// Usage
$user = User::find(1);
$result = $user->applyForLoan();
```

## Testing

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

test('qualified applicant gets loan approval', function () {
    $applicant = User::factory()->create([
        'income' => 5000,
        'credit_score' => 750,
    ]);

    $result = Eligify::criteria('loan_approval')
        ->addRule('income', '>=', 3000)
        ->addRule('credit_score', '>=', 650)
        ->evaluate($applicant);

    expect($result->passed())->toBeTrue();
    expect($result->score)->toBe(100.0);
});

test('unqualified applicant gets rejected', function () {
    $applicant = User::factory()->create([
        'income' => 2000, // Too low
        'credit_score' => 550, // Too low
    ]);

    $result = Eligify::criteria('loan_approval')
        ->addRule('income', '>=', 3000)
        ->addRule('credit_score', '>=', 650)
        ->evaluate($applicant);

    expect($result->passed())->toBeFalse();
    expect($result->failedRules)->toHaveCount(2);
});
```

## Variations

### With Weighted Scoring

```php
$result = Eligify::criteria('loan_approval_weighted')
    ->addRule('income', '>=', 3000, 0.4)
    ->addRule('credit_score', '>=', 650, 0.6)
    ->scoringMethod('weighted')
    ->evaluate($applicant);

// Applicant gets partial credit
// If only income passes: score = 40
// If both pass: score = 100
```

### With Additional Criteria

```php
$result = Eligify::criteria('loan_approval_strict')
    ->addRule('income', '>=', 3000)
    ->addRule('credit_score', '>=', 650)
    ->addRule('employment_months', '>=', 12)
    ->addRule('debt_ratio', '<=', 0.4)
    ->addRule('active_loans', '<=', 2)
    ->evaluate($applicant);
```

### With Dynamic Thresholds

```php
// Adjust thresholds based on loan amount
$loanAmount = 50000;
$minIncome = $loanAmount * 0.06; // 6% of loan amount
$minCreditScore = $loanAmount > 25000 ? 700 : 650;

$result = Eligify::criteria('dynamic_loan_approval')
    ->addRule('income', '>=', $minIncome)
    ->addRule('credit_score', '>=', $minCreditScore)
    ->evaluate($applicant);
```

## Database Storage

```php
use CleaniqueCoders\Eligify\Models\Criteria;

// Store criteria for reuse
$criteria = Criteria::create([
    'name' => 'loan_approval',
    'description' => 'Personal loan approval criteria',
    'rules' => [
        ['field' => 'income', 'operator' => '>=', 'value' => 3000, 'weight' => 0.4],
        ['field' => 'credit_score', 'operator' => '>=', 'value' => 650, 'weight' => 0.6],
    ],
    'scoring_method' => 'weighted',
    'is_active' => true,
]);

// Load and evaluate
$result = Eligify::criteria('loan_approval')
    ->loadFromDatabase($criteria)
    ->evaluate($applicant);
```

## Audit Trail

```php
use CleaniqueCoders\Eligify\Models\Audit;

// View all loan application evaluations
$audits = Audit::where('criteria_name', 'loan_approval')
    ->with('user')
    ->latest()
    ->paginate(20);

// Statistics
$totalApplications = Audit::where('criteria_name', 'loan_approval')->count();
$approvals = Audit::where('criteria_name', 'loan_approval')
    ->where('passed', true)
    ->count();
$approvalRate = ($approvals / $totalApplications) * 100;

echo "Approval rate: {$approvalRate}%";
```

## Key Takeaways

- **Simple and Clear**: Only 2 rules make it easy to understand
- **Binary Decision**: Pass/fail scoring for yes/no decisions
- **Audit Trail**: Every decision is logged
- **Testable**: Easy to write comprehensive tests
- **Extensible**: Can add more rules as requirements grow

## Related Examples

- [Scholarship Eligibility](scholarship.md)
- [Job Screening](job-screening.md)
- [Credit Card Approval](../advanced/credit-card.md)
