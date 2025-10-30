# Rental Application Screening

Comprehensive tenant screening system for property rentals.

## Implementation

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

$monthlyRent = 1500;

$result = Eligify::criteria('rental_screening')
    ->addRule('monthly_income', '>=', $monthlyRent * 3)
    ->addRule('credit_score', '>=', 650)
    ->addRule('eviction_history_count', '==', 0)
    ->addRule('criminal_record', '==', false)
    ->addRule('employment_verified', '==', true)
    ->addRule('previous_landlord_reference', '>=', 4.0)
    ->evaluate($applicant);
```

## Related

- [Loan Approval](../basic/loan-approval.md)
- [Credit Card Approval](credit-card.md)
