<?php

use CleaniqueCoders\Eligify\Eligify;

test('advanced rule engine supports AND/OR logic groups', function () {
    // Create criteria with mixed logic groups
    $criteria = Eligify::criteria('complex_loan_approval')
        ->startGroup('AND')
        ->addRule('income', '>=', 3000)
        ->addRule('employment_status', '==', 'employed')
        ->startGroup('OR')
        ->addRule('credit_score', '>=', 750)
        ->addRule('cosigner_available', '==', true)
        ->setGroupCombinationLogic('AND')
        ->useAdvancedEngine(true)
        ->save();

    $eligifyInstance = app(Eligify::class);

    // Test case that passes both groups (AND then OR)
    $applicant1 = [
        'income' => 5000,
        'employment_status' => 'employed',
        'credit_score' => 780,
        'cosigner_available' => false,
    ];

    $result1 = $eligifyInstance->evaluate($criteria->getCriteria(), $applicant1);
    expect($result1['passed'])->toBeTrue();
    expect($result1)->toHaveKey('group_results');

    // Test case that fails first group
    $applicant2 = [
        'income' => 2000,
        'employment_status' => 'unemployed',
        'credit_score' => 780,
        'cosigner_available' => false,
    ];

    $result2 = $eligifyInstance->evaluate($criteria->getCriteria(), $applicant2);
    expect($result2['passed'])->toBeFalse();

    // Test case that passes first group but fails second group
    $applicant3 = [
        'income' => 5000,
        'employment_status' => 'employed',
        'credit_score' => 600,
        'cosigner_available' => false,
    ];

    $result3 = $eligifyInstance->evaluate($criteria->getCriteria(), $applicant3);
    expect($result3['passed'])->toBeFalse();
});

test('advanced rule engine supports rule dependencies', function () {
    // Create criteria with rule dependencies
    $criteria = Eligify::criteria('dependent_rules')
        ->addRule('age', '>=', 18)
        ->addRuleDependency('alcohol_purchase', '==', true, 'age', '>=', 21)
        ->addRuleDependency('luxury_item', '==', true, 'income', '>=', 30000)
        ->useAdvancedEngine(true)
        ->save();

    $eligifyInstance = app(Eligify::class);

    // Test case where dependency is met
    $data1 = [
        'age' => 25,
        'income' => 40000,
        'alcohol_purchase' => true,
        'luxury_item' => true,
    ];

    $result1 = $eligifyInstance->evaluate($criteria->getCriteria(), $data1);
    expect($result1['passed'])->toBeTrue();

    // Test case where dependency is not met (under 21, can't buy alcohol)
    $data2 = [
        'age' => 19,
        'income' => 40000,
        'alcohol_purchase' => true,
        'luxury_item' => true,
    ];

    $result2 = $eligifyInstance->evaluate($criteria->getCriteria(), $data2);
    // Should still pass because the alcohol purchase rule should be skipped
    expect($result2['passed'])->toBeTrue();
});

test('advanced rule engine supports threshold-based decisions', function () {
    // Create criteria with custom decision thresholds
    $criteria = Eligify::criteria('graded_approval')
        ->addRule('score', '>=', 0) // Always passes, just for scoring
        ->setDecisionThresholds([
            90 => 'Premium Approved',
            75 => 'Standard Approved',
            60 => 'Conditional Approval',
            0 => 'Under Review',
        ])
        ->useAdvancedEngine(true)
        ->save();

    $eligifyInstance = app(Eligify::class);

    // Test premium threshold
    $data1 = ['score' => 95];
    $result1 = $eligifyInstance->evaluate($criteria->getCriteria(), $data1);
    expect($result1['decision'])->toBe('Premium Approved');
    expect($result1)->toHaveKey('threshold_applied');

    // Test standard threshold
    $data2 = ['score' => 80];
    $result2 = $eligifyInstance->evaluate($criteria->getCriteria(), $data2);
    expect($result2['decision'])->toBe('Standard Approved');

    // Test conditional threshold
    $data3 = ['score' => 65];
    $result3 = $eligifyInstance->evaluate($criteria->getCriteria(), $data3);
    expect($result3['decision'])->toBe('Conditional Approval');

    // Test under review threshold
    $data4 = ['score' => 50];
    $result4 = $eligifyInstance->evaluate($criteria->getCriteria(), $data4);
    expect($result4['decision'])->toBe('Under Review');
});

test('advanced rule engine supports complex group combinations', function () {
    // Create criteria with OR combination of groups
    $criteria = Eligify::criteria('flexible_membership')
        ->startGroup('AND')
        ->addRule('age', '>=', 65)
        ->addRule('retired', '==', true)
        ->startGroup('AND')
        ->addRule('student', '==', true)
        ->addRule('age', '<=', 25)
        ->startGroup('AND')
        ->addRule('income', '>=', 100000)
        ->addRule('premium_member', '==', true)
        ->setGroupCombinationLogic('OR') // Any group can pass
        ->useAdvancedEngine(true)
        ->save();

    $eligifyInstance = app(Eligify::class);

    // Test senior citizen path
    $senior = [
        'age' => 70,
        'retired' => true,
        'student' => false,
        'income' => 30000,
        'premium_member' => false,
    ];

    $result1 = $eligifyInstance->evaluate($criteria->getCriteria(), $senior);
    expect($result1['passed'])->toBeTrue();

    // Test student path
    $student = [
        'age' => 20,
        'retired' => false,
        'student' => true,
        'income' => 15000,
        'premium_member' => false,
    ];

    $result2 = $eligifyInstance->evaluate($criteria->getCriteria(), $student);
    expect($result2['passed'])->toBeTrue();

    // Test premium path
    $premium = [
        'age' => 35,
        'retired' => false,
        'student' => false,
        'income' => 150000,
        'premium_member' => true,
    ];

    $result3 = $eligifyInstance->evaluate($criteria->getCriteria(), $premium);
    expect($result3['passed'])->toBeTrue();

    // Test case that doesn't meet any group
    $none = [
        'age' => 35,
        'retired' => false,
        'student' => false,
        'income' => 50000,
        'premium_member' => false,
    ];

    $result4 = $eligifyInstance->evaluate($criteria->getCriteria(), $none);
    expect($result4['passed'])->toBeFalse();
});

test('advanced rule engine provides execution plans', function () {
    $criteria = Eligify::criteria('planning_test')
        ->startGroup('AND')
        ->addRule('field1', '>=', 10)
        ->addRule('field2', '==', 'value')
        ->startGroup('OR')
        ->addRule('field3', 'in', ['a', 'b', 'c'])
        ->addRule('field4', '!=', null)
        ->setGroupCombinationLogic('AND')
        ->setDecisionThresholds([80 => 'Good', 60 => 'Fair', 0 => 'Poor'])
        ->useAdvancedEngine(true)
        ->save();

    $engine = app(Eligify::class)->getAdvancedRuleEngine();
    $plan = $engine->getExecutionPlan($criteria->getCriteria());

    expect($plan)->toHaveKey('criteria');
    expect($plan)->toHaveKey('execution_plan');
    expect($plan)->toHaveKey('group_combination_logic');
    expect($plan)->toHaveKey('decision_thresholds');

    expect($plan['criteria'])->toBe('planning_test');
    expect($plan['group_combination_logic'])->toBe('AND');
    expect($plan['execution_plan'])->toHaveCount(2); // Two groups
    expect($plan['decision_thresholds'])->toHaveKey(80);
});

test('criteria automatically uses advanced engine when needed', function () {
    // Create criteria with features that require advanced engine
    $criteria = Eligify::criteria('auto_advanced')
        ->addRule('basic_field', '>=', 1)
        ->setDecisionThresholds([90 => 'Excellent'])
        ->save(); // Don't explicitly enable advanced engine

    $eligifyInstance = app(Eligify::class);

    $data = ['basic_field' => 2];
    $result = $eligifyInstance->evaluate($criteria->getCriteria(), $data);

    // Should use advanced engine due to decision thresholds
    expect($result)->toHaveKey('threshold_applied');
    expect($result['decision'])->toBe('Excellent');
});

test('advanced rule engine handles NAND, NOR, XOR logic', function () {
    // Test NAND logic (not all must pass)
    $nandCriteria = Eligify::criteria('nand_test')
        ->startGroup('NAND')
        ->addRule('field1', '==', true)
        ->addRule('field2', '==', true)
        ->addRule('field3', '==', true)
        ->useAdvancedEngine(true)
        ->save();

    $eligifyInstance = app(Eligify::class);

    // All true should fail NAND
    $allTrue = ['field1' => true, 'field2' => true, 'field3' => true];
    $nandResult1 = $eligifyInstance->evaluate($nandCriteria->getCriteria(), $allTrue);
    expect($nandResult1['passed'])->toBeFalse();

    // Some false should pass NAND
    $someTrue = ['field1' => true, 'field2' => false, 'field3' => true];
    $nandResult2 = $eligifyInstance->evaluate($nandCriteria->getCriteria(), $someTrue);
    expect($nandResult2['passed'])->toBeTrue();

    // Test XOR logic (exactly one must pass)
    $xorCriteria = Eligify::criteria('xor_test')
        ->startGroup('XOR')
        ->addRule('option1', '==', true)
        ->addRule('option2', '==', true)
        ->useAdvancedEngine(true)
        ->save();

    // Exactly one true should pass XOR
    $oneTrue = ['option1' => true, 'option2' => false];
    $xorResult1 = $eligifyInstance->evaluate($xorCriteria->getCriteria(), $oneTrue);
    expect($xorResult1['passed'])->toBeTrue();

    // Both true should fail XOR
    $bothTrue = ['option1' => true, 'option2' => true];
    $xorResult2 = $eligifyInstance->evaluate($xorCriteria->getCriteria(), $bothTrue);
    expect($xorResult2['passed'])->toBeFalse();

    // Neither true should fail XOR
    $neitherTrue = ['option1' => false, 'option2' => false];
    $xorResult3 = $eligifyInstance->evaluate($xorCriteria->getCriteria(), $neitherTrue);
    expect($xorResult3['passed'])->toBeFalse();
});
