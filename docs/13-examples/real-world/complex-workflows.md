# Complex Multi-Stage Workflows

Multi-stage evaluation with dependencies and conditional logic.

## Implementation

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

function processLoanApplication($applicant)
{
    // Stage 1: Basic eligibility check
    $basicCheck = Eligify::criteria('basic_eligibility')
        ->addRule('age', 'between', [18, 65])
        ->addRule('citizenship', '==', 'US')
        ->evaluate($applicant);

    if (!$basicCheck->passed()) {
        return [
            'status' => 'rejected',
            'stage' => 'basic_eligibility',
            'reason' => $basicCheck->failedRules,
        ];
    }

    // Stage 2: Financial assessment
    $financialCheck = Eligify::criteria('financial_assessment')
        ->addRule('income', '>=', 3000)
        ->addRule('debt_ratio', '<=', 0.4)
        ->addRule('employment_months', '>=', 12)
        ->evaluate($applicant);

    if (!$financialCheck->passed()) {
        return [
            'status' => 'rejected',
            'stage' => 'financial_assessment',
            'reason' => $financialCheck->failedRules,
        ];
    }

    // Stage 3: Credit check
    $creditCheck = Eligify::criteria('credit_check')
        ->addRule('credit_score', '>=', 650)
        ->addRule('delinquencies', '==', 0)
        ->addRule('bankruptcies', '==', 0)
        ->evaluate($applicant);

    if (!$creditCheck->passed()) {
        return [
            'status' => 'manual_review',
            'stage' => 'credit_check',
            'score' => $creditCheck->score,
        ];
    }

    // All stages passed
    return [
        'status' => 'approved',
        'final_score' => ($basicCheck->score + $financialCheck->score + $creditCheck->score) / 3,
    ];
}
```

## Related

- [Loan Approval](../basic/loan-approval.md)
- [Credit Card Approval](../advanced/credit-card.md)
