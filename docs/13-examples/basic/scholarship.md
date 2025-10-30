# Scholarship Eligibility Example

Student scholarship eligibility based on GPA and financial need.

## Use Case

A university needs to determine which students qualify for merit-based scholarships based on academic performance and financial need.

## Business Rules

- Minimum GPA: 3.5
- Maximum family income: $60,000
- Must be full-time student
- Must have completed at least 1 year

## Implementation

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

$result = Eligify::criteria('scholarship')
    ->addRule('gpa', '>=', 3.5)
    ->addRule('family_income', '<=', 60000)
    ->addRule('enrollment_status', '==', 'full-time')
    ->addRule('completed_years', '>=', 1)
    ->evaluate($student);
```

## Testing

```php
test('qualified student receives scholarship', function () {
    $student = Student::factory()->create([
        'gpa' => 3.8,
        'family_income' => 45000,
        'enrollment_status' => 'full-time',
        'completed_years' => 2,
    ]);

    $result = Eligify::criteria('scholarship')
        ->addRule('gpa', '>=', 3.5)
        ->addRule('family_income', '<=', 60000)
        ->addRule('enrollment_status', '==', 'full-time')
        ->addRule('completed_years', '>=', 1)
        ->evaluate($student);

    expect($result->passed())->toBeTrue();
});
```

## Related

- [Loan Approval](loan-approval.md)
- [Job Screening](job-screening.md)
