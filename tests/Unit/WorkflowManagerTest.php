<?php

use CleaniqueCoders\Eligify\Workflow\WorkflowManager;

it('can create workflow manager', function () {
    $workflowManager = new WorkflowManager;

    expect($workflowManager)->toBeInstanceOf(WorkflowManager::class);
});

it('can add and execute simple callbacks', function () {
    $workflowManager = new WorkflowManager;
    $executed = false;

    $workflowManager->addCallback('test_event', function ($data, $result, $context) use (&$executed) {
        $executed = true;
    });

    $workflowManager->executeCallbacks('test_event', [
        'data' => ['test' => true],
        'result' => ['passed' => true],
    ]);

    expect($executed)->toBeTrue();
});

it('can add conditional callbacks', function () {
    $workflowManager = new WorkflowManager;
    $executed = false;

    $workflowManager->addConditionalCallback(
        function ($data, $result, $context) use (&$executed) {
            $executed = true;
        },
        ['min_score' => 80]
    );

    // Should execute when score >= 80
    $workflowManager->executeCallbacks('on_condition', [
        'data' => ['score' => 85],
        'result' => ['score' => 85, 'passed' => true],
    ]);

    expect($executed)->toBeTrue();

    // Reset and test with low score
    $executed = false;
    $workflowManager->executeCallbacks('on_condition', [
        'data' => ['score' => 70],
        'result' => ['score' => 70, 'passed' => true],
    ]);

    expect($executed)->toBeFalse();
});

it('can add score range callbacks', function () {
    $workflowManager = new WorkflowManager;
    $executed = false;

    $workflowManager->addScoreRangeCallback(70, 90, function ($data, $result, $context) use (&$executed) {
        $executed = true;
    });

    // Should execute when score is in range 70-90
    $workflowManager->executeCallbacks('on_condition', [
        'data' => ['score' => 75],
        'result' => ['score' => 75, 'passed' => true],
    ]);

    expect($executed)->toBeTrue();
});

it('respects workflow enabled configuration', function () {
    config(['eligify.workflow.enabled' => false]);

    $workflowManager = new WorkflowManager;
    $executed = false;

    $workflowManager->addCallback('test_event', function ($data, $result, $context) use (&$executed) {
        $executed = true;
    });

    $workflowManager->executeCallbacks('test_event', [
        'data' => ['test' => true],
        'result' => ['passed' => true],
    ]);

    expect($executed)->toBeFalse();

    // Re-enable for other tests
    config(['eligify.workflow.enabled' => true]);
});
