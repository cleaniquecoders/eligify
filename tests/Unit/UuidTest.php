<?php

use CleaniqueCoders\Eligify\Models\Criteria;

it('can create criteria with uuid', function () {
    $criteria = Criteria::create([
        'uuid' => (string) str()->uuid(),
        'name' => 'Test Criteria',
        'slug' => 'test-criteria',
        'description' => 'Test description',
        'is_active' => true,
    ]);

    expect($criteria->uuid)->not->toBeNull();
    expect($criteria->name)->toBe('Test Criteria');
    expect($criteria->slug)->toBe('test-criteria');
});

it('can use criteria builder without uuid errors', function () {
    $builder = \CleaniqueCoders\Eligify\Eligify::criteria('test-uuid-fix');

    expect($builder)->toBeInstanceOf(\CleaniqueCoders\Eligify\Builder\CriteriaBuilder::class);
    expect($builder->getCriteria())->toBeInstanceOf(Criteria::class);
    expect($builder->getCriteria()->uuid)->not->toBeNull();
});
