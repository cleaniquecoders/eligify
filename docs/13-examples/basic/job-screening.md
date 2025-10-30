# Job Candidate Screening Example

Basic candidate filtering for job applications.

## Use Case

HR department needs to automatically screen job candidates based on minimum requirements.

## Business Rules

- Minimum years of experience: 3
- Required skills match: At least 5 skills
- Must have required certification
- Available to start within 30 days

## Implementation

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

$result = Eligify::criteria('Job Screening')
    ->addRule('years_experience', '>=', 3)
    ->addRule('matching_skills_count', '>=', 5)
    ->addRule('has_certification', '==', true)
    ->addRule('available_days', '<=', 30)
    ->evaluate($candidate);
```

## Testing

```php
test('qualified candidate passes screening', function () {
    $candidate = Candidate::factory()->create([
        'years_experience' => 5,
        'matching_skills_count' => 7,
        'has_certification' => true,
        'available_days' => 14,
    ]);

    $result = evaluateCandidate($candidate);

    expect($result->passed())->toBeTrue();
});
```

## Related

- [Loan Approval](loan-approval.md)
- [Scholarship](scholarship.md)
