# Credit Card Approval Example

Complex credit evaluation with multiple weighted factors.

## Implementation

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

$result = Eligify::criteria('credit_card_approval')
    ->addRule('credit_score', '>=', 700, 0.35)
    ->addRule('annual_income', '>=', 40000, 0.25)
    ->addRule('debt_to_income_ratio', '<=', 0.35, 0.20)
    ->addRule('employment_length_months', '>=', 12, 0.10)
    ->addRule('recent_inquiries', '<=', 3, 0.10)
    ->scoringMethod('weighted')
    ->threshold(75)
    ->evaluate($applicant);

// Determine credit limit based on score
$creditLimit = calculateCreditLimit($result->score, $applicant->annual_income);
```

## Related

- [Loan Approval](../basic/loan-approval.md)
- [Insurance](../intermediate/insurance.md)
