<?php

declare(strict_types=1);

use CleaniqueCoders\Eligify\Eligify;
use CleaniqueCoders\Eligify\Enums\RulePriority;

it('can create criteria with fluent api and evaluate', function () {
    // Create criteria using the fluent API
    $builder = Eligify::criteria('Loan Approval')
        ->description('Basic loan approval criteria')
        ->addRule('credit_score', '>=', 650, null, RulePriority::HIGH)
        ->addRule('income', '>=', 30000, null, RulePriority::HIGH)
        ->addRule('debt_ratio', '<=', 0.4, null, RulePriority::MEDIUM)
        ->passThreshold(70);

    // Save the criteria
    $builder->save();

    // Test data for a good applicant
    $goodApplicant = [
        'credit_score' => 750,
        'income' => 50000,
        'debt_ratio' => 0.3,
    ];

    // Test data for a poor applicant
    $poorApplicant = [
        'credit_score' => 550,
        'income' => 25000,
        'debt_ratio' => 0.6,
    ];

    $eligify = new Eligify;

    // Evaluate good applicant
    $goodResult = $eligify->evaluate($builder->getCriteria(), $goodApplicant);
    expect($goodResult['passed'])->toBeTrue();
    expect($goodResult['score'])->toBeGreaterThan(70);

    // Evaluate poor applicant
    $poorResult = $eligify->evaluate($builder->getCriteria(), $poorApplicant);
    expect($poorResult['passed'])->toBeFalse();
    expect($poorResult['score'])->toBeLessThan(70);
    expect($poorResult['failed_rules'])->not->toBeEmpty();
});

it('can use callbacks with evaluation', function () {
    $passCallbackExecuted = false;
    $failCallbackExecuted = false;

    $builder = Eligify::criteria('test_with_callbacks')
        ->addRule('score', '>=', 80)
        ->onPass(function ($data, $result) use (&$passCallbackExecuted) {
            $passCallbackExecuted = true;
        })
        ->onFail(function ($data, $result) use (&$failCallbackExecuted) {
            $failCallbackExecuted = true;
        });

    $eligify = new Eligify;

    // Test passing case
    $passingData = ['score' => 85];
    $eligify->evaluateWithCallbacks($builder, $passingData);
    expect($passCallbackExecuted)->toBeTrue();
    expect($failCallbackExecuted)->toBeFalse();

    // Reset flags and test failing case
    $passCallbackExecuted = false;
    $failCallbackExecuted = false;

    $failingData = ['score' => 60];
    $eligify->evaluateWithCallbacks($builder, $failingData);
    expect($passCallbackExecuted)->toBeFalse();
    expect($failCallbackExecuted)->toBeTrue();
});

it('can create criteria from preset', function () {
    $builder = Eligify::createFromPreset('loan_approval');

    expect($builder)->not->toBeNull();
    expect($builder->getCriteria()->getAttribute('name'))->toBe('Loan Approval');

    // Save and test evaluation
    $builder->save();

    $testData = [
        'credit_score' => 700,
        'income' => 50000,
        'debt_to_income_ratio' => 0.3,
        'employment_years' => 3,
        'active_bankruptcies' => 0,
    ];

    $eligify = new Eligify;
    $result = $eligify->evaluate($builder->getCriteria(), $testData);

    expect($result['passed'])->toBeTrue();
    expect($result['score'])->toBeGreaterThan(70);
});

it('throws exception when criteria not found', function () {
    $eligify = new Eligify;

    expect(fn () => $eligify->evaluate('nonexistent-criteria', []))
        ->toThrow(InvalidArgumentException::class)
        ->toThrow('Criteria \'nonexistent-criteria\' not found');
});

it('validates input data length for security', function () {
    $builder = Eligify::criteria('Security Test')
        ->addRule('name', '==', 'test');

    $builder->save();

    $eligify = new Eligify;

    // Test field name too long
    $longFieldName = str_repeat('a', 300);
    $dataWithLongField = [$longFieldName => 'value'];

    expect(fn () => $eligify->evaluate($builder->getCriteria(), $dataWithLongField))
        ->toThrow(InvalidArgumentException::class);
});

it('detects suspicious content in input data', function () {
    $builder = Eligify::criteria('XSS Test')
        ->addRule('comment', '==', 'safe');

    $builder->save();

    $eligify = new Eligify;

    // This should not throw but should log a warning
    $suspiciousData = [
        'comment' => '<script>alert("xss")</script>',
    ];

    // The evaluation should still proceed but log the warning
    $result = $eligify->evaluate($builder->getCriteria(), $suspiciousData);

    expect($result)->toBeArray();
    expect($result['passed'])->toBeFalse(); // Will fail because comment != 'safe'
});

it('handles evaluation errors gracefully', function () {
    $builder = Eligify::criteria('Error Test')
        ->addRule('field_that_causes_error', '==', 'value');

    $builder->save();

    $eligify = new Eligify;

    // This might cause an error in evaluation, should be caught and wrapped
    $result = $eligify->evaluate($builder->getCriteria(), ['valid_data' => 'test']);

    // Should return a result even if some rules fail to evaluate
    expect($result)->toBeArray();
    expect($result)->toHaveKey('passed');
});
