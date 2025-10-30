# Insurance Underwriting Example

Insurance eligibility with risk assessment and weighted scoring.

## Use Case

Insurance company needs to assess applicant eligibility and calculate risk-based premiums.

## Business Rules

- Age between 18-65
- BMI within healthy range
- Non-smoker preferred
- No major pre-existing conditions

## Implementation

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

$result = Eligify::criteria('insurance_underwriting')
    ->addRule('age', 'between', [18, 65], 0.2)
    ->addRule('smoker', '==', false, 0.3)
    ->addRule('bmi', '<=', 30, 0.25)
    ->addRule('pre_existing_conditions', '==', 0, 0.25)
    ->scoringMethod('weighted')
    ->evaluate($applicant);

// Calculate premium based on score
$basePremium = 100;
$premium = $basePremium * (2 - ($result->score / 100));
```

## Related

- [Credit Card Approval](../advanced/credit-card.md)
