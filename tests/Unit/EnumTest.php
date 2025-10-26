<?php

use CleaniqueCoders\Eligify\Enums\FieldType;
use CleaniqueCoders\Eligify\Enums\RuleOperator;
use CleaniqueCoders\Eligify\Enums\RulePriority;
use CleaniqueCoders\Eligify\Enums\ScoringMethod;

it('can get label from RuleOperator enum', function () {
    expect(RuleOperator::EQUAL->label())->toBe('Equal To');
    expect(RuleOperator::GREATER_THAN->label())->toBe('Greater Than');
    expect(RuleOperator::CONTAINS->label())->toBe('Contains');
});

it('can get description from RuleOperator enum', function () {
    expect(RuleOperator::EQUAL->description())
        ->toBe('Value must be exactly equal');
    expect(RuleOperator::GREATER_THAN->description())
        ->toBe('Value must be greater than');
});

it('can get values from RuleOperator enum using Traitify', function () {
    $values = RuleOperator::values();
    expect($values)->toBeArray();
    expect($values)->toContain('==', '>', 'contains');
});

it('can get labels from RuleOperator enum using Traitify', function () {
    $labels = RuleOperator::labels();
    expect($labels)->toBeArray();
    expect($labels)->toContain('Equal To', 'Greater Than', 'Contains');
});

it('can get options from RuleOperator enum using Traitify', function () {
    $options = RuleOperator::options();
    expect($options)->toBeArray();
    expect($options)->toHaveCount(16); // Should have 16 operators

    // Check first few options structure
    expect($options[0])->toHaveKeys(['value', 'label', 'description']);
    expect($options[0]['value'])->toBe('==');
    expect($options[0]['label'])->toBe('Equal To');
    expect($options[0]['description'])->toBe('Value must be exactly equal');

    expect($options[2]['value'])->toBe('>');
    expect($options[2]['label'])->toBe('Greater Than');
});

it('can get label from FieldType enum', function () {
    expect(FieldType::NUMERIC->label())->toBe('Numeric');
    expect(FieldType::STRING->label())->toBe('String');
    expect(FieldType::BOOLEAN->label())->toBe('Boolean');
});

it('can get description from FieldType enum', function () {
    expect(FieldType::NUMERIC->description())
        ->toBe('Field type for Numeric values');
    expect(FieldType::STRING->description())
        ->toBe('Field type for String values');
});

it('can get label from RulePriority enum', function () {
    expect(RulePriority::CRITICAL->label())->toBe('Critical');
    expect(RulePriority::HIGH->label())->toBe('High');
    expect(RulePriority::MEDIUM->label())->toBe('Medium');
});

it('can get description from RulePriority enum', function () {
    expect(RulePriority::CRITICAL->description())
        ->toBe('Must pass for eligibility');
    expect(RulePriority::HIGH->description())
        ->toBe('Very important for eligibility');
});

it('can get label from ScoringMethod enum', function () {
    expect(ScoringMethod::WEIGHTED->label())->toBe('Weighted');
    expect(ScoringMethod::AVERAGE->label())->toBe('Average');
    expect(ScoringMethod::PERCENTAGE->label())->toBe('Percentage');
});

it('can get description from ScoringMethod enum', function () {
    expect(ScoringMethod::WEIGHTED->description())
        ->toBe('Score based on rule weights and importance');
    expect(ScoringMethod::AVERAGE->description())
        ->toBe('Simple average of all rule scores');
});
