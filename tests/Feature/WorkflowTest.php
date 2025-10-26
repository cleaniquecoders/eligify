<?php

use CleaniqueCoders\Eligify\Eligify;
use CleaniqueCoders\Eligify\Enums\RulePriority;
use CleaniqueCoders\Eligify\Events\EvaluationCompleted;
use Illuminate\Support\Facades\Event;

it('can use enhanced workflow callbacks', function () {
    Event::fake();

    $beforeExecuted = false;
    $afterExecuted = false;
    $excellentExecuted = false;
    $goodExecuted = false;
    $conditionalExecuted = false;

    $builder = Eligify::criteria('enhanced_workflow_test')
        ->description('Enhanced workflow test criteria')
        ->addRule('score', '>=', 80, null, RulePriority::HIGH)
        ->beforeEvaluation(function ($data, $result, $context) use (&$beforeExecuted) {
            $beforeExecuted = true;
        })
        ->afterEvaluation(function ($data, $result, $context) use (&$afterExecuted) {
            $afterExecuted = true;
        })
        ->onExcellent(function ($data, $result, $context) use (&$excellentExecuted) {
            $excellentExecuted = true;
        })
        ->onGood(function ($data, $result, $context) use (&$goodExecuted) {
            $goodExecuted = true;
        })
        ->onCondition(['min_score' => 85], function ($data, $result, $context) use (&$conditionalExecuted) {
            $conditionalExecuted = true;
        });

    $eligify = new Eligify;

    // Test excellent score (90+)
    $excellentData = ['score' => 95];
    $eligify->evaluateWithCallbacks($builder, $excellentData);

    expect($beforeExecuted)->toBeTrue();
    expect($afterExecuted)->toBeTrue();
    expect($excellentExecuted)->toBeTrue();
    expect($goodExecuted)->toBeFalse(); // Should not trigger good when excellent triggers
    expect($conditionalExecuted)->toBeTrue();

    // Assert event was dispatched
    Event::assertDispatched(EvaluationCompleted::class);
});

it('can use score range callbacks', function () {
    $rangeExecuted = false;

    $builder = Eligify::criteria('score_range_test')
        ->addRule('score', '>=', 70)
        ->onScoreRange(75, 85, function ($data, $result, $context) use (&$rangeExecuted) {
            $rangeExecuted = true;
        });

    $eligify = new Eligify;

    // Test score within range
    $result = $eligify->evaluateWithCallbacks($builder, ['score' => 80]);
    expect($rangeExecuted)->toBeTrue();
    expect($result['passed'])->toBeTrue();

    // Reset and test score outside range
    $rangeExecuted = false;
    $eligify->evaluateWithCallbacks($builder, ['score' => 90]);
    expect($rangeExecuted)->toBeFalse(); // Should not execute for score 90
});

it('can handle conditional callbacks with complex conditions', function () {
    $fieldEqualsExecuted = false;
    $scoreRangeExecuted = false;
    $customConditionExecuted = false;

    $builder = Eligify::criteria('complex_conditions_test')
        ->addRule('score', '>=', 60)
        ->addRule('status', '==', 'active')
        ->onCondition(['field_equals' => ['status', 'active']], function ($data, $result, $context) use (&$fieldEqualsExecuted) {
            $fieldEqualsExecuted = true;
        })
        ->onCondition(['score_range' => [70, 80]], function ($data, $result, $context) use (&$scoreRangeExecuted) {
            $scoreRangeExecuted = true;
        })
        ->onCondition(['custom' => function ($context) {
            return ($context['data']['score'] ?? 0) > 85;
        }], function ($data, $result, $context) use (&$customConditionExecuted) {
            $customConditionExecuted = true;
        });

    $eligify = new Eligify;

    // Test data that meets field_equals and score_range conditions
    $testData = ['score' => 75, 'status' => 'active'];
    $eligify->evaluateWithCallbacks($builder, $testData);

    expect($fieldEqualsExecuted)->toBeTrue();
    expect($scoreRangeExecuted)->toBeTrue();
    expect($customConditionExecuted)->toBeFalse();

    // Reset and test custom condition
    $fieldEqualsExecuted = false;
    $scoreRangeExecuted = false;
    $customConditionExecuted = false;

    $testData = ['score' => 90, 'status' => 'active'];
    $eligify->evaluateWithCallbacks($builder, $testData);

    expect($fieldEqualsExecuted)->toBeTrue();
    expect($scoreRangeExecuted)->toBeFalse(); // Score 90 is outside 70-80 range
    expect($customConditionExecuted)->toBeTrue(); // Score 90 > 85
});

it('can perform batch evaluation', function () {
    $builder = Eligify::criteria('batch_test')
        ->addRule('score', '>=', 70)
        ->addRule('status', '==', 'active');

    $eligify = new Eligify;
    $builder->save();

    $dataCollection = [
        ['score' => 80, 'status' => 'active'],   // Should pass
        ['score' => 60, 'status' => 'active'],   // Should fail (low score)
        ['score' => 90, 'status' => 'inactive'], // Should fail (wrong status)
        ['score' => 85, 'status' => 'active'],   // Should pass
    ];

    $result = $eligify->evaluateBatch($builder->getCriteria(), $dataCollection);

    expect($result['total_evaluated'])->toBe(4);
    expect($result['total_passed'])->toBe(2);
    expect($result['total_failed'])->toBe(2);
    expect($result['results'])->toHaveCount(4);
    expect($result['criteria'])->toBeArray();

    // Check individual results
    expect($result['results'][0]['passed'])->toBeTrue();
    expect($result['results'][1]['passed'])->toBeFalse();
    expect($result['results'][2]['passed'])->toBeFalse();
    expect($result['results'][3]['passed'])->toBeTrue();
});

it('can perform batch evaluation with callbacks', function () {
    $passCount = 0;
    $failCount = 0;

    $builder = Eligify::criteria('batch_callbacks_test')
        ->addRule('score', '>=', 75)
        ->onPass(function ($data, $result) use (&$passCount) {
            $passCount++;
        })
        ->onFail(function ($data, $result) use (&$failCount) {
            $failCount++;
        });

    $eligify = new Eligify;

    $dataCollection = [
        ['score' => 80], // Pass
        ['score' => 70], // Fail
        ['score' => 90], // Pass
        ['score' => 60], // Fail
        ['score' => 85], // Pass
    ];

    $result = $eligify->evaluateBatchWithCallbacks($builder, $dataCollection);

    expect($result['total_evaluated'])->toBe(5);
    expect($result['total_passed'])->toBe(3);
    expect($result['total_failed'])->toBe(2);
    expect($passCount)->toBe(3);
    expect($failCount)->toBe(2);
});

it('handles callback errors gracefully', function () {
    config(['eligify.workflow.fail_on_callback_error' => false]);

    $builder = Eligify::criteria('error_handling_test')
        ->addRule('score', '>=', 80)
        ->onPass(function ($data, $result) {
            throw new \Exception('Callback error');
        });

    $eligify = new Eligify;

    // Should not throw exception due to callback error
    $result = $eligify->evaluateWithCallbacks($builder, ['score' => 85]);

    expect($result['passed'])->toBeTrue();
    expect($result['score'])->toBeGreaterThan(80);
});

it('can chain multiple callbacks for the same event', function () {
    $callback1Executed = false;
    $callback2Executed = false;

    $builder = Eligify::criteria('multiple_callbacks_test')
        ->addRule('score', '>=', 80);

    // Add multiple callbacks for excellent scores
    $builder->onExcellent(function ($data, $result, $context) use (&$callback1Executed) {
        $callback1Executed = true;
    });

    $builder->onExcellent(function ($data, $result, $context) use (&$callback2Executed) {
        $callback2Executed = true;
    });

    $eligify = new Eligify;

    $result = $eligify->evaluateWithCallbacks($builder, ['score' => 95]);

    expect($callback1Executed)->toBeTrue();
    expect($callback2Executed)->toBeTrue();
});

it('respects workflow configuration settings', function () {
    // Test with workflow disabled
    config(['eligify.workflow.enabled' => false]);

    $callbackExecuted = false;

    $builder = Eligify::criteria('config_test')
        ->addRule('score', '>=', 80)
        ->onExcellent(function ($data, $result, $context) use (&$callbackExecuted) {
            $callbackExecuted = true;
        });

    $eligify = new Eligify;
    $eligify->evaluateWithCallbacks($builder, ['score' => 95]);

    // Callback should not execute when workflow is disabled
    expect($callbackExecuted)->toBeFalse();

    // Re-enable workflow
    config(['eligify.workflow.enabled' => true]);
});
