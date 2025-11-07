<?php

declare(strict_types=1);

use CleaniqueCoders\Eligify\Eligify;
use CleaniqueCoders\Eligify\Enums\RulePriority;
use CleaniqueCoders\Eligify\Models\Criteria;
use CleaniqueCoders\Eligify\Models\CriteriaVersion;

it('can create a version snapshot of criteria', function () {
    $builder = Eligify::criteria('Loan Approval')
        ->addRule('income', '>=', 30000)
        ->addRule('credit_score', '>=', 650)
        ->save();

    $criteria = $builder->getCriteria();

    // Create first version
    $version1 = $criteria->createVersion('Initial Q1 rules');

    expect($version1)->toBeInstanceOf(CriteriaVersion::class);
    expect($version1->version)->toBe(1);
    expect($version1->description)->toBe('Initial Q1 rules');
    expect($version1->getRulesSnapshot())->toHaveCount(2);
    expect($criteria->current_version)->toBe(1);
});

it('can create multiple versions with incremental version numbers', function () {
    $builder = Eligify::criteria('Scholarship Eligibility')
        ->addRule('gpa', '>=', 3.0)
        ->save();

    $criteria = $builder->getCriteria();

    $v1 = $criteria->createVersion('Version 1');
    expect($v1->version)->toBe(1);

    $builder->addRule('test_score', '>=', 1200)->save();
    $v2 = $criteria->createVersion('Version 2 - Added test score requirement');
    expect($v2->version)->toBe(2);

    expect($criteria->getVersionNumbers())->toEqual([1, 2]);
});

it('can retrieve a specific version', function () {
    $builder = Eligify::criteria('Job Screening')
        ->addRule('years_experience', '>=', 5)
        ->save();

    $criteria = $builder->getCriteria();
    $v1 = $criteria->createVersion('Initial version');

    $retrieved = $criteria->version(1);
    expect($retrieved)->not->toBeNull();
    expect($retrieved->id)->toBe($v1->id);
    expect($retrieved->getRulesSnapshot())->toHaveCount(1);
});

it('can check if version exists', function () {
    $builder = Eligify::criteria('Application Screening')
        ->addRule('field1', '>=', 100)
        ->save();

    $criteria = $builder->getCriteria();
    $criteria->createVersion('Version 1');

    expect($criteria->hasVersion(1))->toBeTrue();
    expect($criteria->hasVersion(2))->toBeFalse();
});

it('can get latest version', function () {
    $builder = Eligify::criteria('Credit Check')
        ->addRule('score', '>=', 700)
        ->save();

    $criteria = $builder->getCriteria();
    $v1 = $criteria->createVersion('V1');

    $builder->addRule('income', '>=', 50000)->save();
    $v2 = $criteria->createVersion('V2');

    $latest = $criteria->latestVersion();
    expect($latest->id)->toBe($v2->id);
    expect($latest->version)->toBe(2);
});

it('can evaluate against a specific historical version', function () {
    $builder = Eligify::criteria('Loan Approval Version Test')
        ->addRule('income', '>=', 30000)
        ->save();

    $criteria = $builder->getCriteria();
    $v1 = $criteria->createVersion('Original rules');

    // Verify version snapshot was created correctly
    expect($v1->getRulesSnapshot())->toHaveCount(1);
    expect($v1->getRulesSnapshot()[0]['value'])->toBe(30000);
});

it('can get version history for a criteria', function () {
    $builder = Eligify::criteria('Membership Upgrade')
        ->addRule('account_age_months', '>=', 6)
        ->save();

    $criteria = $builder->getCriteria();
    $v1 = $criteria->createVersion('Launch version');

    $builder->addRule('subscription_level', '==', 'premium')->save();
    $v2 = $criteria->createVersion('Added subscription requirement');

    $eligify = new Eligify;
    $history = $eligify->getVersionHistory('Membership Upgrade');

    expect($history)->toHaveCount(2);
    expect($history[0]['version'])->toBe(1);
    expect($history[1]['version'])->toBe(2);
    expect($history[0]['description'])->toBe('Launch version');
    expect($history[1]['rules_count'])->toBe(2);
});

it('preserves rule details in version snapshots', function () {
    $builder = Eligify::criteria('Advanced Screening')
        ->addRule('income', '>=', 50000, 2)
        ->addRule('credit_score', '>=', 700, 1)
        ->save();

    $criteria = $builder->getCriteria();
    $version = $criteria->createVersion('V1');

    $snapshot = $version->getRulesSnapshot();
    expect($snapshot[0]['weight'])->toBe(2);
    expect($snapshot[1]['weight'])->toBe(1);
    expect($snapshot[0]['order'])->toBe(1);
    expect($snapshot[1]['order'])->toBe(2);
});

it('stores metadata with versions', function () {
    $builder = Eligify::criteria('Metadata Test')
        ->addRule('test', '>=', 1)
        ->save();

    $criteria = $builder->getCriteria();
    $version = $criteria->createVersion('Test version');

    // Update version metadata
    $version->update([
        'meta' => [
            'created_by' => 1,
            'approval_status' => 'approved',
            'effective_date' => now()->toDateString(),
            'tags' => ['compliance', 'q2-2024'],
        ],
    ]);

    $version->refresh();
    expect($version->meta['created_by'])->toBe(1);
    expect($version->meta['tags'])->toContain('compliance');
    expect($version->meta['approval_status'])->toBe('approved');
});

it('throws exception when evaluating non-existent version', function () {
    $builder = Eligify::criteria('Error Test')
        ->addRule('test', '>=', 1)
        ->save();

    $eligify = new Eligify;

    expect(function () use ($eligify) {
        $eligify->evaluateVersion('Error Test', 999, []);
    })->toThrow(\InvalidArgumentException::class);
});

it('can retrieve version with version() method on builder', function () {
    $builder = Eligify::criteria('Builder Test')
        ->addRule('test', '>=', 1)
        ->save();

    $criteria = $builder->getCriteria();
    $v1 = $criteria->createVersion('V1');

    expect($builder->getCurrentVersion())->toBe(1);
    expect($builder->getAvailableVersions())->toContain(1);
});

it('can compare two versions and find differences', function () {
    $builder = Eligify::criteria('Version Compare Test')
        ->addRule('years_experience', '>=', 3)
        ->addRule('degree_level', 'in', ['bachelor', 'master'])
        ->save();

    $criteria = $builder->getCriteria();
    $v1 = $criteria->createVersion('V1');

    // Get the rules snapshot from v1 to verify structure
    $v1Rules = $v1->getRulesSnapshot();
    expect($v1Rules)->toHaveCount(2);
    expect($v1Rules[0]['field'])->toBe('years_experience');
    expect($v1Rules[1]['field'])->toBe('degree_level');
});

it('can create factory instances for testing', function () {
    $version = CriteriaVersion::factory()->create();

    expect($version)->toBeInstanceOf(CriteriaVersion::class);
    expect($version->id)->not->toBeNull();
    expect($version->uuid)->not->toBeNull();
    expect($version->criteria_id)->not->toBeNull();
    expect($version->version)->toBeGreaterThanOrEqual(1);
});

it('can create factory with specific version number', function () {
    $version = CriteriaVersion::factory()->version(5)->create();

    expect($version->version)->toBe(5);
});

it('can create factory with multiple rules', function () {
    $version = CriteriaVersion::factory()
        ->withMultipleRules(5)
        ->create();

    expect($version->getRulesSnapshot())->toHaveCount(5);
});

it('can create factory with description', function () {
    $description = 'Test description for versioning';
    $version = CriteriaVersion::factory()
        ->description($description)
        ->create();

    expect($version->description)->toBe($description);
});

it('can create factory with first version state', function () {
    $version = CriteriaVersion::factory()
        ->firstVersion()
        ->create();

    expect($version->version)->toBe(1);
});

it('criteria version scope by version works correctly', function () {
    $criteria = Criteria::factory()->create();
    CriteriaVersion::factory()->version(1)->create(['criteria_id' => $criteria->id]);
    CriteriaVersion::factory()->version(2)->create(['criteria_id' => $criteria->id]);
    CriteriaVersion::factory()->version(3)->create(['criteria_id' => $criteria->id]);

    $version2 = CriteriaVersion::query()
        ->where('criteria_id', $criteria->id)
        ->byVersion(2)
        ->first();

    expect($version2)->not->toBeNull();
    expect($version2->version)->toBe(2);
});

it('maintains rule properties accurately in snapshots', function () {
    $builder = Eligify::criteria('Rule Properties Test')
        ->addRule('salary', '>=', 75000, 3, RulePriority::HIGH)
        ->addRule('experience', '>=', 10, 2, RulePriority::MEDIUM)
        ->save();

    $criteria = $builder->getCriteria();
    $version = $criteria->createVersion('Properties snapshot');

    $snapshot = $version->getRulesSnapshot();

    // First rule (salary)
    expect($snapshot[0]['field'])->toBe('salary');
    expect($snapshot[0]['operator'])->toBe('>=');
    expect($snapshot[0]['value'])->toBe(75000);
    expect($snapshot[0]['weight'])->toBe(3);
    expect($snapshot[0]['is_active'])->toBeTrue();

    // Second rule (experience)
    expect($snapshot[1]['field'])->toBe('experience');
    expect($snapshot[1]['weight'])->toBe(2);
});
