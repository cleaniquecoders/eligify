# Test Helpers

Utilities and helpers for testing Eligify applications.

## Overview

Eligify provides various test helpers, factories, and utilities to make testing easier and more expressive.

## Factory Helpers

### Criteria Factory

```php
use CleaniqueCoders\Eligify\Models\Criteria;

// Basic criteria
$criteria = Criteria::factory()->create();

// With specific rules
$criteria = Criteria::factory()
    ->withRules([
        ['field' => 'income', 'operator' => '>=', 'value' => 3000],
        ['field' => 'credit_score', 'operator' => '>=', 'value' => 650],
    ])
    ->create();

// With scoring method
$criteria = Criteria::factory()
    ->weightedScoring()
    ->create();

// Inactive criteria
$criteria = Criteria::factory()
    ->inactive()
    ->create();
```

### Audit Factory

```php
use CleaniqueCoders\Eligify\Models\Audit;

// Basic audit
$audit = Audit::factory()->create();

// Passed audit
$audit = Audit::factory()
    ->passed()
    ->create();

// Failed audit with specific score
$audit = Audit::factory()
    ->failed()
    ->withScore(45.5)
    ->create();

// For specific criteria
$audit = Audit::factory()
    ->forCriteria('Loan Approval')
    ->create();
```

### Snapshot Factory

```php
use CleaniqueCoders\Eligify\Models\Snapshot;

// Basic snapshot
$snapshot = Snapshot::factory()->create();

// With specific data
$snapshot = Snapshot::factory()
    ->withData([
        'income' => 5000,
        'credit_score' => 750,
    ])
    ->create();

// For specific context
$snapshot = Snapshot::factory()
    ->forContext('loan_application')
    ->create();
```

## Builder Helpers

### Quick Criteria Builder

```php
use CleaniqueCoders\Eligify\Support\TestHelpers\CriteriaBuilder;

// Build test criteria quickly
$builder = CriteriaBuilder::make('loan_approval')
    ->income(3000)
    ->creditScore(650)
    ->employmentMonths(6);

$result = $builder->evaluate($applicant);
```

### Rule Builder

```php
use CleaniqueCoders\Eligify\Support\TestHelpers\RuleBuilder;

// Build complex rules
$rule = RuleBuilder::make()
    ->field('income')
    ->operator('>=')
    ->value(3000)
    ->weight(0.4)
    ->toArray();
```

## Custom Assertions

### Result Assertions

```php
// Add custom expectations for results
expect()->extend('toPassEligibility', function () {
    return $this->value->passed() === true;
});

expect()->extend('toFailEligibility', function () {
    return $this->value->passed() === false;
});

expect()->extend('toHaveScore', function (float $expected) {
    return abs($this->value->score - $expected) < 0.01;
});

// Usage
test('applicant passes eligibility', function () {
    $result = evaluateApplicant();
    expect($result)->toPassEligibility();
    expect($result)->toHaveScore(85.5);
});
```

### Audit Assertions

```php
expect()->extend('toHaveAuditLog', function (string $criteriaName) {
    $exists = Audit::where('criteria_name', $criteriaName)->exists();
    return $exists;
});

// Usage
test('creates audit log', function () {
    evaluateApplicant();
    expect(true)->toHaveAuditLog('loan_approval');
});
```

## Mock Builders

### Mock Applicant Builder

```php
use CleaniqueCoders\Eligify\Support\TestHelpers\MockApplicantBuilder;

// Create mock applicants quickly
$qualified = MockApplicantBuilder::qualified()
    ->income(5000)
    ->creditScore(750)
    ->build();

$unqualified = MockApplicantBuilder::unqualified()
    ->income(2000)
    ->creditScore(550)
    ->build();

$edge = MockApplicantBuilder::edgeCase()
    ->income(3000) // Exactly at threshold
    ->creditScore(650) // Exactly at threshold
    ->build();
```

## Data Providers

### Test Scenario Providers

```php
use CleaniqueCoders\Eligify\Support\TestHelpers\ScenarioProvider;

// Get predefined test scenarios
$scenarios = ScenarioProvider::loanApproval();

foreach ($scenarios as $scenario) {
    test("loan approval: {$scenario['description']}", function () use ($scenario) {
        $applicant = User::factory()->create($scenario['data']);
        $result = evaluateLoan($applicant);

        expect($result->passed())->toBe($scenario['expected']);
    });
}

// Available providers
ScenarioProvider::loanApproval();
ScenarioProvider::scholarshipEligibility();
ScenarioProvider::creditCardApproval();
ScenarioProvider::insuranceUnderwriting();
```

### Edge Case Generators

```php
use CleaniqueCoders\Eligify\Support\TestHelpers\EdgeCaseGenerator;

// Generate edge cases automatically
$edgeCases = EdgeCaseGenerator::for('income', [
    'threshold' => 3000,
    'test_values' => [2999, 3000, 3001],
]);

foreach ($edgeCases as $case) {
    test("edge case: {$case['description']}", function () use ($case) {
        $applicant = User::factory()->create(['income' => $case['value']]);
        $result = evaluateIncome($applicant);

        expect($result->passed())->toBe($case['should_pass']);
    });
}
```

## Fake Data Generators

### Random Criteria Generator

```php
use CleaniqueCoders\Eligify\Support\TestHelpers\FakeCriteriaGenerator;

// Generate random valid criteria for testing
$criteria = FakeCriteriaGenerator::generate([
    'rule_count' => 3,
    'scoring_method' => 'weighted',
]);

// Generate bulk criteria
$multipleCriteria = FakeCriteriaGenerator::generateMany(10);
```

### Random Applicant Generator

```php
use CleaniqueCoders\Eligify\Support\TestHelpers\FakeApplicantGenerator;

// Generate realistic test data
$applicant = FakeApplicantGenerator::realistic([
    'income_range' => [30000, 100000],
    'credit_score_range' => [550, 850],
]);

// Generate edge case applicant
$applicant = FakeApplicantGenerator::edgeCase('income', 3000);
```

## Time Helpers

### Freeze Time for Testing

```php
use Illuminate\Support\Facades\Date;

test('audit timestamp is correct', function () {
    Date::setTestNow('2025-01-15 10:00:00');

    $audit = evaluateAndGetAudit();

    expect($audit->created_at->format('Y-m-d H:i:s'))
        ->toBe('2025-01-15 10:00:00');

    Date::setTestNow(); // Reset
});
```

## Database Helpers

### Seed Test Data

```php
use CleaniqueCoders\Eligify\Support\TestHelpers\DatabaseSeeder;

beforeEach(function () {
    DatabaseSeeder::seedCriteria([
        'loan_approval',
        'scholarship_eligibility',
        'credit_card_approval',
    ]);
});

// Seed with relationships
DatabaseSeeder::seedWithAudits('loan_approval', 100);
```

### Clean Test Data

```php
use CleaniqueCoders\Eligify\Support\TestHelpers\DatabaseCleaner;

afterEach(function () {
    DatabaseCleaner::cleanAudits();
    DatabaseCleaner::cleanSnapshots();
    DatabaseCleaner::cleanCache();
});
```

## Response Helpers

### Result Factory

```php
use CleaniqueCoders\Eligify\Support\TestHelpers\ResultFactory;

// Create mock results without evaluation
$passedResult = ResultFactory::passed([
    'score' => 95.0,
    'passed_rules' => 3,
]);

$failedResult = ResultFactory::failed([
    'score' => 45.0,
    'failed_rules' => ['income', 'credit_score'],
]);

// Use in tests
test('handles passed result', function () {
    $result = ResultFactory::passed();
    processResult($result);

    // assertions...
});
```

## Test Traits

### WithCriteria Trait

```php
use CleaniqueCoders\Eligify\Support\TestHelpers\WithCriteria;

uses(WithCriteria::class);

test('uses criteria trait', function () {
    $criteria = $this->createCriteria('Test', [
        ['field' => 'income', 'operator' => '>=', 'value' => 3000],
    ]);

    expect($criteria->name)->toBe('test');
});
```

### WithAudit Trait

```php
use CleaniqueCoders\Eligify\Support\TestHelpers\WithAudit;

uses(WithAudit::class);

test('creates audit log', function () {
    $audit = $this->createAuditLog('loan_approval', passed: true);

    expect($audit->criteria_name)->toBe('loan_approval');
});
```

## Debugging Helpers

### Dump Evaluation Details

```php
use CleaniqueCoders\Eligify\Support\TestHelpers\DebugHelper;

test('debug evaluation', function () {
    $result = evaluateApplicant();

    DebugHelper::dumpEvaluation($result);

    // Output:
    // Criteria: loan_approval
    // Passed: true
    // Score: 95.0
    // Rules:
    //   ✓ income >= 3000 (actual: 5000)
    //   ✓ credit_score >= 650 (actual: 750)
});
```

### Explain Result

```php
use CleaniqueCoders\Eligify\Support\TestHelpers\Explainer;

test('explain why failed', function () {
    $result = evaluateApplicant();

    $explanation = Explainer::why($result);

    // Example output:
    // "Failed because: income (2000) is less than required (3000)"

    expect($explanation)->toContain('income');
});
```

## Performance Helpers

### Benchmark Helper

```php
use CleaniqueCoders\Eligify\Support\TestHelpers\Benchmark;

test('evaluation is fast', function () {
    $time = Benchmark::measure(function () {
        Eligify::criteria('Test')
            ->addRule('income', '>=', 3000)
            ->evaluate(User::factory()->create());
    });

    expect($time)->toBeLessThan(100); // milliseconds
});
```

### Memory Profiler

```php
use CleaniqueCoders\Eligify\Support\TestHelpers\MemoryProfiler;

test('memory usage is reasonable', function () {
    MemoryProfiler::start();

    // Perform operations
    evaluateManyApplicants(1000);

    $memoryUsed = MemoryProfiler::end();

    expect($memoryUsed)->toBeLessThan(50 * 1024 * 1024); // 50MB
});
```

## Configuration Helpers

### Temporary Config

```php
use CleaniqueCoders\Eligify\Support\TestHelpers\ConfigHelper;

test('uses temporary config', function () {
    ConfigHelper::temporary('eligify.cache.enabled', false, function () {
        // Cache is disabled for this test
        $result = evaluateWithoutCache();

        expect(Cache::has('eligify:test'))->toBeFalse();
    });

    // Config is restored after callback
});
```

## Spy Helpers

### Evaluation Spy

```php
use CleaniqueCoders\Eligify\Support\TestHelpers\EvaluationSpy;

test('tracks all evaluations', function () {
    $spy = EvaluationSpy::start();

    evaluateApplicant1();
    evaluateApplicant2();

    expect($spy->count())->toBe(2);
    expect($spy->criteriaNames())->toContain('loan_approval');
});
```

## Custom Matchers

### Array Matchers

```php
expect()->extend('toContainRule', function (string $field) {
    $rules = $this->value;
    return collect($rules)->contains('field', $field);
});

test('criteria contains income rule', function () {
    $criteria = Criteria::factory()->create();
    expect($criteria->rules)->toContainRule('income');
});
```

## Integration Helpers

### HTTP Test Helpers

```php
use CleaniqueCoders\Eligify\Support\TestHelpers\HttpHelper;

test('evaluation via API', function () {
    $response = HttpHelper::evaluateViaApi([
        'criteria' => 'loan_approval',
        'entity_id' => 1,
        'entity_type' => 'App\\Models\\User',
    ]);

    expect($response->json('passed'))->toBeTrue();
});
```

## Best Practices

### Organize Helpers

```php
// tests/Helpers/EligifyHelpers.php
namespace Tests\Helpers;

trait EligifyHelpers
{
    protected function createQualifiedApplicant(): User
    {
        return User::factory()->create([
            'income' => 5000,
            'credit_score' => 750,
        ]);
    }

    protected function evaluateLoan(User $applicant): EvaluationResult
    {
        return Eligify::criteria('Loan Approval')
            ->addRule('income', '>=', 3000)
            ->addRule('credit_score', '>=', 650)
            ->evaluate($applicant);
    }
}
```

### Shared Setup

```php
// tests/Pest.php
use Tests\Helpers\EligifyHelpers;

uses(EligifyHelpers::class)->in('Feature', 'Unit');

// Now available in all tests
test('uses shared helper', function () {
    $applicant = $this->createQualifiedApplicant();
    $result = $this->evaluateLoan($applicant);

    expect($result->passed())->toBeTrue();
});
```

## Related Documentation

- [Unit Testing](unit-testing.md)
- [Integration Testing](integration-testing.md)
- [Performance Testing](performance-testing.md)
