# Integration Testing

Test complete workflows and interactions between Eligify components.

## Overview

Integration tests verify that different parts of your eligibility system work correctly together, including database interactions, event handling, and external integrations.

## Full Workflow Testing

### Complete Evaluation Flow

```php
use CleaniqueCoders\Eligify\Facades\Eligify;
use CleaniqueCoders\Eligify\Models\Audit;

test('complete loan approval workflow', function () {
    // Create applicant
    $applicant = User::factory()->create([
        'name' => 'John Doe',
        'income' => 5000,
        'credit_score' => 750,
    ]);

    // Create snapshot
    $snapshot = Eligify::snapshot($applicant, 'loan_application');

    // Evaluate eligibility
    $result = Eligify::criteria('loan_approval')
        ->addRule('income', '>=', 3000, 0.4)
        ->addRule('credit_score', '>=', 650, 0.6)
        ->scoringMethod('weighted')
        ->onPass(function ($entity) {
            $entity->notify(new LoanApprovedNotification());
        })
        ->evaluate($applicant);

    // Verify results
    expect($result->passed())->toBeTrue();
    expect($result->score)->toBe(100.0);

    // Verify snapshot was created
    expect($snapshot->id)->not->toBeNull();

    // Verify audit log
    $audit = Audit::where('criteria_name', 'loan_approval')
        ->where('entity_type', User::class)
        ->latest()
        ->first();

    expect($audit)->not->toBeNull();
    expect($audit->passed)->toBeTrue();
    expect($audit->score)->toBe(100.0);

    // Verify notification
    Notification::assertSentTo($applicant, LoanApprovedNotification::class);
});
```

### Multi-Stage Workflow

```php
test('multi-stage application process', function () {
    $applicant = User::factory()->create([
        'income' => 5000,
        'credit_score' => 750,
        'employment_months' => 12,
    ]);

    // Stage 1: Basic eligibility
    $basic = Eligify::criteria('basic_eligibility')
        ->addRule('age', '>=', 18)
        ->addRule('citizenship', '==', 'US')
        ->evaluate($applicant);

    expect($basic->passed())->toBeTrue();

    // Stage 2: Financial assessment
    $financial = Eligify::criteria('financial_assessment')
        ->addRule('income', '>=', 3000)
        ->addRule('debt_ratio', '<=', 0.4)
        ->evaluate($applicant);

    expect($financial->passed())->toBeTrue();

    // Stage 3: Credit check
    $credit = Eligify::criteria('credit_check')
        ->addRule('credit_score', '>=', 650)
        ->addRule('delinquencies', '==', 0)
        ->evaluate($applicant);

    expect($credit->passed())->toBeTrue();

    // Final approval
    $allPassed = $basic->passed() && $financial->passed() && $credit->passed();
    expect($allPassed)->toBeTrue();
});
```

## Database Integration

### Testing with Database Transactions

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('criteria stored in database', function () {
    $criteria = Criteria::create([
        'name' => 'loan_approval',
        'description' => 'Loan approval criteria',
        'rules' => [
            ['field' => 'income', 'operator' => '>=', 'value' => 3000, 'weight' => 0.4],
            ['field' => 'credit_score', 'operator' => '>=', 'value' => 650, 'weight' => 0.6],
        ],
        'scoring_method' => 'weighted',
        'is_active' => true,
    ]);

    $this->assertDatabaseHas('criteria', [
        'name' => 'loan_approval',
        'is_active' => true,
    ]);

    // Load and evaluate
    $applicant = User::factory()->create([
        'income' => 5000,
        'credit_score' => 750,
    ]);

    $result = Eligify::criteria($criteria->name)
        ->loadFromDatabase($criteria)
        ->evaluate($applicant);

    expect($result->passed())->toBeTrue();
});
```

### Audit Trail Verification

```php
test('audit trail is complete', function () {
    $user = User::factory()->create();
    $applicant = User::factory()->create(['income' => 5000]);

    $this->actingAs($user);

    $result = Eligify::criteria('test')
        ->addRule('income', '>=', 3000)
        ->evaluate($applicant);

    $audit = Audit::latest()->first();

    expect($audit->criteria_name)->toBe('test');
    expect($audit->entity_type)->toBe(User::class);
    expect($audit->entity_id)->toBe($applicant->id);
    expect($audit->user_id)->toBe($user->id);
    expect($audit->passed)->toBeTrue();
    expect($audit->snapshot)->toBeArray();
});
```

## Event Integration

### Testing Event Listeners

```php
use Illuminate\Support\Facades\Event;
use CleaniqueCoders\Eligify\Events\CriteriaEvaluated;
use CleaniqueCoders\Eligify\Events\RulePassed;
use CleaniqueCoders\Eligify\Events\RuleFailed;

test('evaluation triggers correct events', function () {
    Event::fake([
        CriteriaEvaluated::class,
        RulePassed::class,
        RuleFailed::class,
    ]);

    $applicant = User::factory()->create([
        'income' => 5000,
        'credit_score' => 600, // Fails
    ]);

    Eligify::criteria('test')
        ->addRule('income', '>=', 3000)
        ->addRule('credit_score', '>=', 650)
        ->evaluate($applicant);

    Event::assertDispatched(CriteriaEvaluated::class);
    Event::assertDispatched(RulePassed::class, 1);
    Event::assertDispatched(RuleFailed::class, 1);
});

test('event listener updates statistics', function () {
    $applicant = User::factory()->create(['income' => 5000]);

    Eligify::criteria('test')
        ->addRule('income', '>=', 3000)
        ->evaluate($applicant);

    // Verify listener updated stats
    $stats = CriteriaStatistics::where('criteria_name', 'test')->first();

    expect($stats->total_evaluations)->toBe(1);
    expect($stats->passed_count)->toBe(1);
    expect($stats->failed_count)->toBe(0);
});
```

## Cache Integration

### Testing Cached Evaluations

```php
use Illuminate\Support\Facades\Cache;

test('evaluation results are cached', function () {
    $applicant = User::factory()->create(['income' => 5000]);

    // First evaluation
    $result1 = Eligify::criteria('cacheable')
        ->addRule('income', '>=', 3000)
        ->cacheFor(60)
        ->evaluate($applicant);

    // Verify cached
    expect(Cache::has("eligify:cacheable:{$applicant->id}"))->toBeTrue();

    // Second evaluation should use cache
    $result2 = Eligify::criteria('cacheable')
        ->addRule('income', '>=', 3000)
        ->evaluate($applicant);

    expect($result1->toArray())->toBe($result2->toArray());
});

test('cache invalidation works', function () {
    $applicant = User::factory()->create(['income' => 5000]);

    Eligify::criteria('test')
        ->addRule('income', '>=', 3000)
        ->cacheFor(60)
        ->evaluate($applicant);

    expect(Cache::has("eligify:test:{$applicant->id}"))->toBeTrue();

    // Clear cache
    Eligify::clearCache('test');

    expect(Cache::has("eligify:test:{$applicant->id}"))->toBeFalse();
});
```

## API Integration Testing

### Testing HTTP Endpoints

```php
test('evaluation API endpoint', function () {
    $user = User::factory()->create();
    $applicant = User::factory()->create(['income' => 5000]);

    $response = $this->actingAs($user)
        ->postJson('/api/eligify/evaluate', [
            'criteria' => 'loan_approval',
            'entity_type' => 'App\\Models\\User',
            'entity_id' => $applicant->id,
            'rules' => [
                ['field' => 'income', 'operator' => '>=', 'value' => 3000],
            ],
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'passed' => true,
            'score' => 100,
        ]);
});

test('criteria listing endpoint', function () {
    Criteria::factory()->count(5)->create();

    $response = $this->actingAs(User::factory()->create())
        ->getJson('/api/eligify/criteria');

    $response->assertStatus(200)
        ->assertJsonCount(5, 'data');
});
```

## Queue Integration

### Testing Async Evaluation

```php
use Illuminate\Support\Facades\Queue;
use CleaniqueCoders\Eligify\Jobs\EvaluateCriteriaJob;

test('evaluation can be queued', function () {
    Queue::fake();

    $applicant = User::factory()->create();

    Eligify::criteria('async_test')
        ->addRule('income', '>=', 3000)
        ->evaluateAsync($applicant);

    Queue::assertPushed(EvaluateCriteriaJob::class);
});

test('queued evaluation completes successfully', function () {
    $applicant = User::factory()->create(['income' => 5000]);

    $job = new EvaluateCriteriaJob(
        criteria: 'test',
        entity: $applicant,
        rules: [['field' => 'income', 'operator' => '>=', 'value' => 3000]]
    );

    $job->handle();

    // Verify audit was created
    $audit = Audit::where('criteria_name', 'test')->latest()->first();
    expect($audit->passed)->toBeTrue();
});
```

## Notification Integration

### Testing Workflow Notifications

```php
use Illuminate\Support\Facades\Notification;
use App\Notifications\LoanApprovedNotification;
use App\Notifications\LoanRejectedNotification;

test('approval notification sent', function () {
    Notification::fake();

    $applicant = User::factory()->create(['income' => 5000]);

    Eligify::criteria('loan')
        ->addRule('income', '>=', 3000)
        ->onPass(fn($entity) => $entity->notify(new LoanApprovedNotification()))
        ->evaluate($applicant);

    Notification::assertSentTo($applicant, LoanApprovedNotification::class);
});

test('rejection notification sent with details', function () {
    Notification::fake();

    $applicant = User::factory()->create(['income' => 2000]);

    Eligify::criteria('loan')
        ->addRule('income', '>=', 3000)
        ->onFail(fn($entity, $result) =>
            $entity->notify(new LoanRejectedNotification($result))
        )
        ->evaluate($applicant);

    Notification::assertSentTo(
        $applicant,
        LoanRejectedNotification::class,
        function ($notification) {
            return $notification->result->passed() === false;
        }
    );
});
```

## Model Relationship Testing

### Testing with Related Models

```php
test('evaluates with relationships', function () {
    $user = User::factory()
        ->has(Profile::factory()->state(['verified' => true]))
        ->has(Employment::factory()->state(['months' => 12]))
        ->create(['income' => 5000]);

    $result = Eligify::criteria('advanced')
        ->addRule('income', '>=', 3000)
        ->addRule('profile.verified', '==', true)
        ->addRule('employment.months', '>=', 6)
        ->evaluate($user);

    expect($result->passed())->toBeTrue();
});
```

## Multi-Tenant Testing

### Testing Tenant Isolation

```php
test('criteria isolated by tenant', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();

    $tenant1->makeCurrent();

    $criteria1 = Criteria::create([
        'name' => 'loan',
        'rules' => [['field' => 'income', 'operator' => '>=', 'value' => 3000]],
    ]);

    $tenant2->makeCurrent();

    $criteria2 = Criteria::create([
        'name' => 'loan',
        'rules' => [['field' => 'income', 'operator' => '>=', 'value' => 5000]],
    ]);

    $tenant1->makeCurrent();
    expect(Criteria::where('name', 'loan')->first()->rules[0]['value'])->toBe(3000);

    $tenant2->makeCurrent();
    expect(Criteria::where('name', 'loan')->first()->rules[0]['value'])->toBe(5000);
});
```

## Error Handling

### Testing Error Scenarios

```php
test('handles missing field gracefully', function () {
    $applicant = User::factory()->create(); // No 'income' field

    expect(fn() =>
        Eligify::criteria('test')
            ->addRule('nonexistent_field', '>=', 3000)
            ->evaluate($applicant)
    )->toThrow(FieldNotFoundException::class);
});

test('handles invalid operator', function () {
    expect(fn() =>
        Eligify::criteria('test')
            ->addRule('income', 'invalid_op', 3000)
    )->toThrow(InvalidOperatorException::class);
});
```

## Performance Integration

### Testing Under Load

```php
test('handles concurrent evaluations', function () {
    $applicants = User::factory()->count(100)->create(['income' => 5000]);

    $results = [];

    foreach ($applicants as $applicant) {
        $results[] = Eligify::criteria('concurrent')
            ->addRule('income', '>=', 3000)
            ->evaluate($applicant);
    }

    expect($results)->toHaveCount(100);
    expect(collect($results)->every(fn($r) => $r->passed()))->toBeTrue();
});
```

## Best Practices

### Setup and Teardown

```php
beforeEach(function () {
    $this->criteria = Eligify::criteria('test')
        ->addRule('income', '>=', 3000)
        ->addRule('credit_score', '>=', 650);
});

afterEach(function () {
    Eligify::clearCache();
    Audit::truncate();
});
```

### Test Data Management

```php
function setupLoanScenario(): array
{
    return [
        'criteria' => Criteria::factory()->create([
            'name' => 'loan_approval',
            'rules' => [
                ['field' => 'income', 'operator' => '>=', 'value' => 3000],
                ['field' => 'credit_score', 'operator' => '>=', 'value' => 650],
            ],
        ]),
        'qualified' => User::factory()->create(['income' => 5000, 'credit_score' => 750]),
        'unqualified' => User::factory()->create(['income' => 2000, 'credit_score' => 550]),
    ];
}
```

## Related Documentation

- [Unit Testing](unit-testing.md)
- [Performance Testing](performance-testing.md)
- [Test Helpers](test-helpers.md)
