# Government Aid Qualification

Aid eligibility for government assistance programs.

## Use Case

Government agency needs to determine eligibility for financial assistance programs.

## Implementation

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

$result = Eligify::criteria('aid_qualification')
    ->addRule('annual_income', '<=', 25000)
    ->addRule('family_size', '>=', 3)
    ->addRule('citizenship_status', '==', 'citizen')
    ->addRule('has_dependents', '==', true)
    ->addRule('employment_status', 'in', ['unemployed', 'part-time'])
    ->evaluate($applicant);
```

## Related

- [Scholarship](../basic/scholarship.md)
