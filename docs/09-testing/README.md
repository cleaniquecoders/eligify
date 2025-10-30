# Testing

This section covers testing strategies for Eligify-powered applications.

## Overview

Ensure your eligibility logic works correctly through comprehensive testing.

## Documentation in this Section

- **[Unit Testing](unit-testing.md)** - Testing criteria and rules
- **[Integration Testing](integration-testing.md)** - Testing full workflows
- **[Performance Testing](performance-testing.md)** - Benchmarking and optimization
- **[Test Helpers](test-helpers.md)** - Testing utilities

## Quick Example

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

test('loan approval passes for qualified applicant', function () {
    $applicant = User::factory()->create([
        'income' => 5000,
        'credit_score' => 750,
    ]);

    $result = Eligify::criteria('Loan Approval')
        ->addRule('income', '>=', 3000)
        ->addRule('credit_score', '>=', 650)
        ->evaluate($applicant);

    expect($result->passed())->toBeTrue();
    expect($result->score)->toBeGreaterThan(70);
});
```

## Testing Strategies

### 1. Unit Tests

Test individual rules and criteria in isolation:

```php
test('credit score rule evaluates correctly')
test('income threshold is enforced')
test('custom operators work as expected')
```

### 2. Integration Tests

Test complete workflows:

```php
test('loan approval workflow triggers notifications')
test('failed evaluation creates audit log')
test('callbacks execute on pass/fail')
```

### 3. Performance Tests

Benchmark evaluation speed:

```php
test('evaluation completes within 100ms')
test('caching improves performance by 50%')
test('bulk evaluations scale linearly')
```

### 4. Edge Cases

Test boundary conditions:

```php
test('handles null values gracefully')
test('missing fields fail validation')
test('circular criteria references are prevented')
```

## Test Helpers

Eligify provides testing utilities:

```php
use CleaniqueCoders\Eligify\Testing\CriteriaFactory;

// Create test criteria
$criteria = CriteriaFactory::make('test_criteria')
    ->withRule('field', '>=', 100)
    ->withScoring('weighted');

// Create test entities
$entity = EntityFactory::make()
    ->with('field', 150)
    ->build();
```

## Related Sections

- [Core Features](../03-core-features/) - What to test
- [Examples](../13-examples/) - Testing patterns in examples
- [Deployment](../10-deployment/) - Testing before deployment
