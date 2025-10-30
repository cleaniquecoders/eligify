# Unit Testing

Test individual rules, criteria, and components in isolation.

## Overview

Unit tests verify that individual components of your eligibility system work correctly in isolation, without dependencies on external systems or complex integrations.

## Testing Rules

### Basic Rule Evaluation

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

test('income rule evaluates correctly', function () {
    $applicant = User::factory()->create(['income' => 5000]);

    $result = Eligify::criteria('test')
        ->addRule('income', '>=', 3000)
        ->evaluate($applicant);

    expect($result->passed())->toBeTrue();
});

test('credit score rule fails correctly', function () {
    $applicant = User::factory()->create(['credit_score' => 550]);

    $result = Eligify::criteria('test')
        ->addRule('credit_score', '>=', 650)
        ->evaluate($applicant);

    expect($result->passed())->toBeFalse();
    expect($result->failedRules)->toHaveCount(1);
});
```

### Multiple Rules

```php
test('all rules must pass', function () {
    $applicant = User::factory()->create([
        'income' => 5000,
        'credit_score' => 750,
        'employment_months' => 12,
    ]);

    $result = Eligify::criteria('loan')
        ->addRule('income', '>=', 3000)
        ->addRule('credit_score', '>=', 650)
        ->addRule('employment_months', '>=', 6)
        ->evaluate($applicant);

    expect($result->passed())->toBeTrue();
    expect($result->passedRules)->toHaveCount(3);
});
```

## Testing Operators

### Built-in Operators

```php
test('equals operator works', function () {
    $user = User::factory()->create(['status' => 'active']);

    $result = Eligify::criteria('test')
        ->addRule('status', '==', 'active')
        ->evaluate($user);

    expect($result->passed())->toBeTrue();
});

test('in operator with arrays', function () {
    $user = User::factory()->create(['country' => 'US']);

    $result = Eligify::criteria('test')
        ->addRule('country', 'in', ['US', 'CA', 'UK'])
        ->evaluate($user);

    expect($result->passed())->toBeTrue();
});

test('between operator', function () {
    $user = User::factory()->create(['age' => 25]);

    $result = Eligify::criteria('test')
        ->addRule('age', 'between', [18, 65])
        ->evaluate($user);

    expect($result->passed())->toBeTrue();
});
```

### Custom Operators

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

beforeEach(function () {
    Eligify::registerOperator('divisible_by', function ($value, $divisor) {
        return $value % $divisor === 0;
    });
});

test('custom operator works', function () {
    $user = User::factory()->create(['points' => 100]);

    $result = Eligify::criteria('test')
        ->addRule('points', 'divisible_by', 10)
        ->evaluate($user);

    expect($result->passed())->toBeTrue();
});
```

## Testing Scoring Methods

### Weighted Scoring

```php
test('weighted scoring calculates correctly', function () {
    $applicant = User::factory()->create([
        'income' => 5000,
        'credit_score' => 750,
    ]);

    $result = Eligify::criteria('loan')
        ->addRule('income', '>=', 3000, 0.4)
        ->addRule('credit_score', '>=', 650, 0.6)
        ->scoringMethod('weighted')
        ->evaluate($applicant);

    expect($result->passed())->toBeTrue();
    expect($result->score)->toBe(100.0);
});

test('partial weighted score', function () {
    $applicant = User::factory()->create([
        'income' => 5000,    // passes (40%)
        'credit_score' => 600, // fails (0%)
    ]);

    $result = Eligify::criteria('loan')
        ->addRule('income', '>=', 3000, 0.4)
        ->addRule('credit_score', '>=', 650, 0.6)
        ->scoringMethod('weighted')
        ->evaluate($applicant);

    expect($result->passed())->toBeFalse();
    expect($result->score)->toBe(40.0);
});
```

### Pass/Fail Scoring

```php
test('pass fail scoring is binary', function () {
    $applicant = User::factory()->create([
        'income' => 5000,
        'credit_score' => 750,
    ]);

    $result = Eligify::criteria('loan')
        ->addRule('income', '>=', 3000)
        ->addRule('credit_score', '>=', 650)
        ->scoringMethod('pass_fail')
        ->evaluate($applicant);

    expect($result->score)->toBeIn([0, 100]);
});
```

## Testing Data Extraction

### Basic Extractor

```php
use CleaniqueCoders\Eligify\Support\Extractor;

test('extractor gets direct properties', function () {
    $user = User::factory()->create(['name' => 'John']);

    $value = Extractor::extract($user, 'name');

    expect($value)->toBe('John');
});

test('extractor uses dot notation', function () {
    $user = User::factory()->create();
    $user->profile()->create(['bio' => 'Developer']);

    $value = Extractor::extract($user, 'profile.bio');

    expect($value)->toBe('Developer');
});
```

### Custom Extractors

```php
use CleaniqueCoders\Eligify\Concerns\HasExtractor;

test('custom extractor method', function () {
    $applicant = new class {
        use HasExtractor;

        protected function extractCreditScore(): int
        {
            return 750;
        }
    };

    $result = Eligify::criteria('test')
        ->addRule('credit_score', '>=', 650)
        ->evaluate($applicant);

    expect($result->passed())->toBeTrue();
});
```

## Testing Snapshots

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

test('snapshot captures data correctly', function () {
    $user = User::factory()->create([
        'name' => 'John',
        'income' => 5000,
    ]);

    $snapshot = Eligify::snapshot($user, 'loan_application');

    expect($snapshot->data)->toMatchArray([
        'name' => 'John',
        'income' => 5000,
    ]);
    expect($snapshot->context)->toBe('loan_application');
});

test('snapshot is immutable', function () {
    $user = User::factory()->create(['income' => 5000]);
    $snapshot = Eligify::snapshot($user, 'test');

    $user->update(['income' => 6000]);

    expect($snapshot->data['income'])->toBe(5000);
    expect($user->income)->toBe(6000);
});
```

## Testing Caching

```php
use Illuminate\Support\Facades\Cache;

test('evaluation is cached', function () {
    $applicant = User::factory()->create(['income' => 5000]);

    $result1 = Eligify::criteria('loan')
        ->addRule('income', '>=', 3000)
        ->evaluate($applicant);

    $result2 = Eligify::criteria('loan')
        ->addRule('income', '>=', 3000)
        ->evaluate($applicant);

    // Second call should be cached
    expect($result1->toArray())->toBe($result2->toArray());
});

test('cache can be cleared', function () {
    Eligify::criteria('test')
        ->addRule('income', '>=', 3000)
        ->evaluate(User::factory()->create());

    Eligify::clearCache('test');

    expect(Cache::has('eligify:test'))->toBeFalse();
});
```

## Testing Workflows

### Callbacks

```php
test('onPass callback executes', function () {
    $executed = false;

    $applicant = User::factory()->create(['income' => 5000]);

    Eligify::criteria('test')
        ->addRule('income', '>=', 3000)
        ->onPass(function ($entity) use (&$executed) {
            $executed = true;
        })
        ->evaluate($applicant);

    expect($executed)->toBeTrue();
});

test('onFail callback executes', function () {
    $executed = false;

    $applicant = User::factory()->create(['income' => 2000]);

    Eligify::criteria('test')
        ->addRule('income', '>=', 3000)
        ->onFail(function ($entity, $result) use (&$executed) {
            $executed = true;
        })
        ->evaluate($applicant);

    expect($executed)->toBeTrue();
});
```

## Testing Models

### Criteria Model

```php
use CleaniqueCoders\Eligify\Models\Criteria;

test('criteria can be stored', function () {
    $criteria = Criteria::create([
        'name' => 'loan_approval',
        'description' => 'Loan approval criteria',
        'rules' => [
            ['field' => 'income', 'operator' => '>=', 'value' => 3000],
        ],
    ]);

    expect($criteria->name)->toBe('loan_approval');
    expect($criteria->rules)->toHaveCount(1);
});
```

### Audit Model

```php
use CleaniqueCoders\Eligify\Models\Audit;

test('audit log is created', function () {
    $applicant = User::factory()->create(['income' => 5000]);

    Eligify::criteria('test')
        ->addRule('income', '>=', 3000)
        ->evaluate($applicant);

    expect(Audit::count())->toBeGreaterThan(0);

    $audit = Audit::latest()->first();
    expect($audit->criteria_name)->toBe('test');
    expect($audit->passed)->toBeTrue();
});
```

## Test Helpers

### Factory Usage

```php
use CleaniqueCoders\Eligify\Models\Criteria;

test('uses criteria factory', function () {
    $criteria = Criteria::factory()
        ->withRules([
            ['field' => 'income', 'operator' => '>=', 'value' => 3000],
            ['field' => 'credit_score', 'operator' => '>=', 'value' => 650],
        ])
        ->create();

    expect($criteria->rules)->toHaveCount(2);
});
```

### Custom Assertions

```php
// Create custom expectations
expect()->extend('toPassEligibility', function () {
    return $this->value->passed() === true;
});

test('uses custom expectation', function () {
    $result = Eligify::criteria('test')
        ->addRule('income', '>=', 3000)
        ->evaluate(User::factory()->create(['income' => 5000]));

    expect($result)->toPassEligibility();
});
```

## Mocking & Spying

### Mock External Dependencies

```php
use Illuminate\Support\Facades\Http;

test('mocks external API', function () {
    Http::fake([
        'api.example.com/credit-score' => Http::response(['score' => 750], 200),
    ]);

    $applicant = User::factory()->create();

    $result = Eligify::criteria('loan')
        ->addRule('external_credit_score', '>=', 650)
        ->evaluate($applicant);

    Http::assertSent(function ($request) {
        return $request->url() === 'api.example.com/credit-score';
    });
});
```

### Spy on Events

```php
use Illuminate\Support\Facades\Event;
use CleaniqueCoders\Eligify\Events\CriteriaEvaluated;

test('fires evaluation event', function () {
    Event::fake([CriteriaEvaluated::class]);

    Eligify::criteria('test')
        ->addRule('income', '>=', 3000)
        ->evaluate(User::factory()->create(['income' => 5000]));

    Event::assertDispatched(CriteriaEvaluated::class);
});
```

## Best Practices

### Arrange-Act-Assert Pattern

```php
test('follows AAA pattern', function () {
    // Arrange
    $applicant = User::factory()->create(['income' => 5000]);
    $criteria = Eligify::criteria('loan')
        ->addRule('income', '>=', 3000);

    // Act
    $result = $criteria->evaluate($applicant);

    // Assert
    expect($result->passed())->toBeTrue();
});
```

### Descriptive Test Names

```php
test('loan approval passes when income exceeds minimum threshold', function () {
    // ...
});

test('loan approval fails when credit score below 650', function () {
    // ...
});
```

### Test Data Builders

```php
function qualifiedApplicant(): User
{
    return User::factory()->create([
        'income' => 5000,
        'credit_score' => 750,
        'employment_months' => 12,
    ]);
}

function unqualifiedApplicant(): User
{
    return User::factory()->create([
        'income' => 2000,
        'credit_score' => 550,
        'employment_months' => 3,
    ]);
}

test('qualified applicant passes', function () {
    $result = evaluateLoanCriteria(qualifiedApplicant());
    expect($result->passed())->toBeTrue();
});
```

## Related Documentation

- [Integration Testing](integration-testing.md)
- [Test Helpers](test-helpers.md)
- [Performance Testing](performance-testing.md)
