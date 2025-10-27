<?php

use CleaniqueCoders\Eligify\Concerns\HasEligibility;
use CleaniqueCoders\Eligify\Eligify;
use CleaniqueCoders\Eligify\Models\Criteria;
use Illuminate\Database\Eloquent\Model;

class TestPolicy
{
    use HasEligibility;

    // Make trait methods public for testing
    public function publicHasEligibility(Model $model, string $criteriaName): bool
    {
        return $this->hasEligibility($model, $criteriaName);
    }

    public function publicCheckEligibility(Model $model, string $criteriaName): array
    {
        return $this->checkEligibility($model, $criteriaName);
    }

    public function publicCheckCriteria(Model $model, \Closure $criteriaBuilder): array
    {
        return $this->checkCriteria($model, $criteriaBuilder);
    }

    public function publicEvaluateModel(Model $model, Criteria $criteria): array
    {
        return $this->evaluateModel($model, $criteria);
    }

    public function publicHasMinimumScore(Model $model, string $criteriaName, int $minimumScore): bool
    {
        return $this->hasMinimumScore($model, $criteriaName, $minimumScore);
    }

    public function publicCheckBatchEligibility(array $models, string $criteriaName): array
    {
        return $this->checkBatchEligibility($models, $criteriaName);
    }

    public function publicGetEligibilityStatus(Model $model, string $criteriaName): array
    {
        return $this->getEligibilityStatus($model, $criteriaName);
    }

    public function publicPassesAnyCriteria(Model $model, array $criteriaNames): bool
    {
        return $this->passesAnyCriteria($model, $criteriaNames);
    }

    public function publicPassesAllCriteria(Model $model, array $criteriaNames): bool
    {
        return $this->passesAllCriteria($model, $criteriaNames);
    }

    public function publicCheckMultipleCriteria(Model $model, array $criteriaNames): array
    {
        return $this->checkMultipleCriteria($model, $criteriaNames);
    }
}

class TestUser extends Model
{
    protected $table = 'test_users'; // Add explicit table name

    public $timestamps = false; // Disable timestamps for testing

    protected $fillable = [
        'name',
        'age',
        'income',
        'credit_score',
        'active_loans',
        'tenure_months',
        'performance_score',
        'gpa',
        'experience_years',
        'certifications',
        'performance_rating',
        'debt_ratio',
        'loyalty_points',
        'purchase_amount',
    ];

    protected $casts = [
        'age' => 'integer',
        'income' => 'integer',
        'credit_score' => 'integer',
        'active_loans' => 'integer',
        'tenure_months' => 'integer',
        'performance_score' => 'integer',
        'gpa' => 'float',
        'experience_years' => 'integer',
        'certifications' => 'integer',
        'performance_rating' => 'float',
        'debt_ratio' => 'float',
        'loyalty_points' => 'integer',
        'purchase_amount' => 'float',
    ];
}

test('policy trait can check basic eligibility', function () {
    // Create a test criteria
    Eligify::criteria('loan_approval')
        ->addRule('income', '>=', 3000)
        ->addRule('credit_score', '>=', 650)
        ->addRule('active_loans', '<=', 2)
        ->save();

    $policy = new TestPolicy;

    // Test eligible user
    $eligibleUser = new TestUser([
        'name' => 'John Doe',
        'age' => 30,
        'income' => 5000,
        'credit_score' => 750,
        'active_loans' => 1,
    ]);

    expect($policy->publicHasEligibility($eligibleUser, 'loan_approval'))->toBeTrue();

    // Test ineligible user
    $ineligibleUser = new TestUser([
        'name' => 'Jane Doe',
        'age' => 25,
        'income' => 2000,
        'credit_score' => 600,
        'active_loans' => 3,
    ]);

    expect($policy->publicHasEligibility($ineligibleUser, 'loan_approval'))->toBeFalse();
});

test('policy trait can get detailed eligibility results', function () {
    // Create a test criteria
    Eligify::criteria('scholarship_eligibility')
        ->addRule('age', '<=', 25)
        ->addRule('gpa', '>=', 3.5)
        ->save();

    $policy = new TestPolicy;

    $student = new TestUser([
        'name' => 'Student',
        'age' => 23,
        'gpa' => 3.8,
    ]);

    $result = $policy->publicCheckEligibility($student, 'scholarship_eligibility');

    expect($result)->toHaveKey('passed');
    expect($result)->toHaveKey('score');
    expect($result)->toHaveKey('decision');
    expect($result)->toHaveKey('failed_rules');
    expect($result['passed'])->toBeTrue();
});

test('policy trait can use custom criteria builder', function () {
    $policy = new TestPolicy;

    $applicant = new TestUser([
        'name' => 'Test User',
        'experience_years' => 5,
        'certifications' => 3,
        'performance_rating' => 4.2,
    ]);

    $result = $policy->publicCheckCriteria($applicant, function ($criteria) {
        $criteria->addRule('experience_years', '>=', 3)
            ->addRule('certifications', '>=', 2)
            ->addRule('performance_rating', '>=', 4.0);
    });

    expect($result['passed'])->toBeTrue();
    expect($result)->toHaveKey('score');
});

test('policy trait can evaluate against existing criteria model', function () {
    // Create a simple criteria with basic rules
    $builder = Eligify::criteria('simple_test')
        ->addRule('age', '>=', 18);

    $builder->save();
    $criteria = $builder->getCriteria();

    $policy = new TestPolicy;

    $person = new TestUser;
    $person->age = 25; // Should pass

    $result = $policy->publicEvaluateModel($person, $criteria);

    // Check what we got back
    expect($result)->toBeArray();
    expect($result)->toHaveKey('passed');
    expect($result['passed'])->toBeTrue();
});

test('policy trait can check minimum score threshold', function () {
    // Create criteria
    Eligify::criteria('credit_check')
        ->addRule('credit_score', '>=', 600)
        ->addRule('debt_ratio', '<=', 0.3)
        ->save();

    $policy = new TestPolicy;

    // Test with passing applicant (should get 100 score)
    $applicant = new TestUser([
        'credit_score' => 720,
        'debt_ratio' => 0.25,
    ]);

    expect($policy->publicHasMinimumScore($applicant, 'credit_check', 80))->toBeTrue();
    expect($policy->publicHasMinimumScore($applicant, 'credit_check', 100))->toBeTrue();

    // Test with partially failing applicant (should get 50 score - 1 of 2 rules pass)
    $partialApplicant = new TestUser([
        'credit_score' => 500, // Fails this rule
        'debt_ratio' => 0.25,  // Passes this rule
    ]);

    expect($policy->publicHasMinimumScore($partialApplicant, 'credit_check', 40))->toBeTrue();
    expect($policy->publicHasMinimumScore($partialApplicant, 'credit_check', 60))->toBeFalse();
});

test('policy trait can check batch eligibility', function () {
    // Create criteria
    Eligify::criteria('membership_eligibility')
        ->addRule('age', '>=', 18)
        ->addRule('income', '>=', 25000)
        ->save();

    $policy = new TestPolicy;

    $users = [
        new TestUser(['name' => 'User1', 'age' => 25, 'income' => 30000]),
        new TestUser(['name' => 'User2', 'age' => 17, 'income' => 20000]),
        new TestUser(['name' => 'User3', 'age' => 30, 'income' => 35000]),
    ];

    $results = $policy->publicCheckBatchEligibility($users, 'membership_eligibility');

    expect($results)->toHaveCount(3);
    expect($results[0]['passed'])->toBeTrue();  // User1 eligible
    expect($results[1]['passed'])->toBeFalse(); // User2 not eligible
    expect($results[2]['passed'])->toBeTrue();  // User3 eligible
});

test('policy trait can get eligibility status with message', function () {
    // Create criteria
    Eligify::criteria('discount_eligibility')
        ->addRule('loyalty_points', '>=', 1000)
        ->addRule('purchase_amount', '>=', 100)
        ->save();

    $policy = new TestPolicy;

    $customer = new TestUser([
        'loyalty_points' => 1500,
        'purchase_amount' => 150,
    ]);

    $status = $policy->publicGetEligibilityStatus($customer, 'discount_eligibility');

    expect($status)->toHaveKey('eligible');
    expect($status)->toHaveKey('message');
    expect($status['eligible'])->toBeTrue();
    expect($status['message'])->toContain('Eligible');
});

test('policy trait can check multiple criteria', function () {
    // Create multiple criteria
    Eligify::criteria('basic_check')
        ->addRule('age', '>=', 18)
        ->save();

    Eligify::criteria('premium_check')
        ->addRule('age', '>=', 21)
        ->addRule('income', '>=', 50000)
        ->save();

    $policy = new TestPolicy;

    $user = new TestUser([
        'age' => 25,
        'income' => 60000,
    ]);

    expect($policy->publicPassesAnyCriteria($user, ['basic_check', 'premium_check']))->toBeTrue();
    expect($policy->publicPassesAllCriteria($user, ['basic_check', 'premium_check']))->toBeTrue();

    $youngUser = new TestUser([
        'age' => 19,
        'income' => 30000,
    ]);

    expect($policy->publicPassesAnyCriteria($youngUser, ['basic_check', 'premium_check']))->toBeTrue();
    expect($policy->publicPassesAllCriteria($youngUser, ['basic_check', 'premium_check']))->toBeFalse();

    $results = $policy->publicCheckMultipleCriteria($user, ['basic_check', 'premium_check']);
    expect($results)->toHaveKey('basic_check');
    expect($results)->toHaveKey('premium_check');
    expect($results['basic_check']['passed'])->toBeTrue();
    expect($results['premium_check']['passed'])->toBeTrue();
});

test('policy trait handles errors gracefully', function () {
    $policy = new TestPolicy;

    $user = new TestUser([
        'name' => 'Test User',
    ]);

    // Non-existent criteria should return false
    expect($policy->publicHasEligibility($user, 'non_existent_criteria'))->toBeFalse();

    // Detailed check should return error structure
    $result = $policy->publicCheckEligibility($user, 'non_existent_criteria');
    expect($result['passed'])->toBeFalse();
    expect($result['score'])->toBe(0);
    expect($result['decision'])->toContain('Error');
});
