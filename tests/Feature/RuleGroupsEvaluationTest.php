<?php

declare(strict_types=1);

use CleaniqueCoders\Eligify\Eligify;
use CleaniqueCoders\Eligify\Enums\GroupCombination;
use CleaniqueCoders\Eligify\Models\Criteria;
use CleaniqueCoders\Eligify\Models\Rule;
use CleaniqueCoders\Eligify\Models\RuleGroup;

describe('Rule Groups Feature Tests', function () {
    describe('Group Evaluation', function () {
        it('evaluates groups with ALL logic', function () {
            $criteria = Criteria::factory()->create(['name' => 'Test ALL']);
            $group = RuleGroup::factory()
                ->for($criteria)
                ->create([
                    'name' => 'identity',
                    'logic_type' => GroupCombination::ALL,
                ]);

            Rule::factory()
                ->active()
                ->create(['field' => 'verified', 'operator' => '==', 'value' => true, 'group_id' => $group->id]);
            Rule::factory()
                ->active()
                ->create(['field' => 'active', 'operator' => '==', 'value' => true, 'group_id' => $group->id]);

            $evaluator = app(Eligify::class)->getAdvancedRuleEngine();
            $results = $evaluator->evaluateGroups($criteria, ['verified' => true, 'active' => true]);

            expect($results['combination_passed'])->toBeTrue();
            expect($results['group_results'][$group->id]['passed'])->toBeTrue();
        });

        it('fails when ALL group has one failing rule', function () {
            $criteria = Criteria::factory()->create(['name' => 'Test ALL Fail']);
            $group = RuleGroup::factory()
                ->for($criteria)
                ->create([
                    'name' => 'identity',
                    'logic_type' => GroupCombination::ALL,
                ]);

            Rule::factory()
                ->active()
                ->create(['field' => 'verified', 'operator' => '==', 'value' => true, 'group_id' => $group->id]);
            Rule::factory()
                ->active()
                ->create(['field' => 'active', 'operator' => '==', 'value' => true, 'group_id' => $group->id]);

            $evaluator = app(Eligify::class)->getAdvancedRuleEngine();
            $results = $evaluator->evaluateGroups($criteria, ['verified' => true, 'active' => false]);

            expect($results['group_results'][$group->id]['passed'])->toBeFalse();
        });

        it('evaluates groups with ANY logic', function () {
            $criteria = Criteria::factory()->create(['name' => 'Test ANY']);
            $group = RuleGroup::factory()
                ->for($criteria)
                ->create([
                    'name' => 'contact',
                    'logic_type' => GroupCombination::ANY,
                ]);

            Rule::factory()
                ->create(['field' => 'email_verified', 'operator' => '==', 'value' => true, 'group_id' => $group->id]);
            Rule::factory()
                ->create(['field' => 'phone_verified', 'operator' => '==', 'value' => true, 'group_id' => $group->id]);

            $evaluator = app(Eligify::class)->getAdvancedRuleEngine();
            $results = $evaluator->evaluateGroups($criteria, ['email_verified' => true, 'phone_verified' => false]);

            expect($results['group_results'][$group->id]['passed'])->toBeTrue();
        });

        it('evaluates groups with MAJORITY logic', function () {
            $criteria = Criteria::factory()->create(['name' => 'Test MAJORITY']);
            $group = RuleGroup::factory()
                ->for($criteria)
                ->create([
                    'name' => 'checks',
                    'logic_type' => GroupCombination::MAJORITY,
                ]);

            Rule::factory()->active()->create(['field' => 'fraud_check', 'operator' => '==', 'value' => true, 'group_id' => $group->id]);
            Rule::factory()->active()->create(['field' => 'identity_match', 'operator' => '==', 'value' => true, 'group_id' => $group->id]);
            Rule::factory()->active()->create(['field' => 'address_match', 'operator' => '==', 'value' => true, 'group_id' => $group->id]);

            $evaluator = app(Eligify::class)->getAdvancedRuleEngine();
            // 2 of 3 pass = majority passes
            $results = $evaluator->evaluateGroups($criteria, [
                'fraud_check' => true,
                'identity_match' => true,
                'address_match' => false,
            ]);

            expect($results['group_results'][$group->id]['passed'])->toBeTrue();
        });

        it('fails MAJORITY logic when less than half pass', function () {
            $criteria = Criteria::factory()->create(['name' => 'Test MAJORITY Fail']);
            $group = RuleGroup::factory()
                ->for($criteria)
                ->create([
                    'name' => 'checks',
                    'logic_type' => GroupCombination::MAJORITY,
                ]);

            Rule::factory()->active()->create(['field' => 'fraud_check', 'operator' => '==', 'value' => true, 'group_id' => $group->id]);
            Rule::factory()->active()->create(['field' => 'identity_match', 'operator' => '==', 'value' => true, 'group_id' => $group->id]);
            Rule::factory()->active()->create(['field' => 'address_match', 'operator' => '==', 'value' => true, 'group_id' => $group->id]);

            $evaluator = app(Eligify::class)->getAdvancedRuleEngine();
            // 1 of 3 pass = does not pass majority
            $results = $evaluator->evaluateGroups($criteria, [
                'fraud_check' => true,
                'identity_match' => false,
                'address_match' => false,
            ]);

            expect($results['group_results'][$group->id]['passed'])->toBeFalse();
        });
    });

    describe('Multiple Groups', function () {
        it('evaluates multiple groups with ALL combination logic', function () {
            $criteria = Criteria::factory()->create(['name' => 'Multi Group ALL']);
            $criteria->update(['meta' => ['group_combination_logic' => 'ALL']]);

            $identityGroup = RuleGroup::factory()
                ->for($criteria)
                ->create([
                    'name' => 'identity',
                    'logic_type' => GroupCombination::ALL,
                    'order' => 1,
                ]);

            Rule::factory()->active()->create(['field' => 'ssn_verified', 'operator' => '==', 'value' => true, 'group_id' => $identityGroup->id]);

            $financialGroup = RuleGroup::factory()
                ->for($criteria)
                ->create([
                    'name' => 'financial',
                    'logic_type' => GroupCombination::ALL,
                    'order' => 2,
                ]);

            Rule::factory()->active()->create(['field' => 'income', 'operator' => '>=', 'value' => 30000, 'group_id' => $financialGroup->id]);

            $evaluator = app(Eligify::class)->getAdvancedRuleEngine();
            $results = $evaluator->evaluateGroups($criteria, [
                'ssn_verified' => true,
                'income' => 50000,
            ]);

            expect($results['combination_passed'])->toBeTrue();
        });

        it('fails with ALL group combination when one group fails', function () {
            $criteria = Criteria::factory()->create(['name' => 'Multi Group ALL Fail']);
            $criteria->update(['meta' => ['group_combination_logic' => 'ALL']]);

            $identityGroup = RuleGroup::factory()
                ->for($criteria)
                ->create([
                    'name' => 'identity',
                    'logic_type' => GroupCombination::ALL,
                    'order' => 1,
                ]);

            Rule::factory()->active()->create(['field' => 'ssn_verified', 'operator' => '==', 'value' => true, 'group_id' => $identityGroup->id]);

            $financialGroup = RuleGroup::factory()
                ->for($criteria)
                ->create([
                    'name' => 'financial',
                    'logic_type' => GroupCombination::ALL,
                    'order' => 2,
                ]);

            Rule::factory()->active()->create(['field' => 'income', 'operator' => '>=', 'value' => 30000, 'group_id' => $financialGroup->id]);

            $evaluator = app(Eligify::class)->getAdvancedRuleEngine();
            $results = $evaluator->evaluateGroups($criteria, [
                'ssn_verified' => false,
                'income' => 50000,
            ]);

            expect($results['combination_passed'])->toBeFalse();
        });

        it('evaluates multiple groups with ANY combination logic', function () {
            $criteria = Criteria::factory()->create(['name' => 'Multi Group ANY']);
            $criteria->update(['meta' => ['group_combination_logic' => 'ANY']]);

            $group1 = RuleGroup::factory()
                ->for($criteria)
                ->create([
                    'name' => 'academic',
                    'logic_type' => GroupCombination::ALL,
                    'order' => 1,
                ]);

            Rule::factory()->active()->create(['field' => 'gpa', 'operator' => '>=', 'value' => 3.5, 'group_id' => $group1->id]);

            $group2 = RuleGroup::factory()
                ->for($criteria)
                ->create([
                    'name' => 'test_scores',
                    'logic_type' => GroupCombination::ALL,
                    'order' => 2,
                ]);

            Rule::factory()->active()->create(['field' => 'sat_score', 'operator' => '>=', 'value' => 1400, 'group_id' => $group2->id]);

            $evaluator = app(Eligify::class)->getAdvancedRuleEngine();
            // First group passes, second fails - with ANY, should pass
            $results = $evaluator->evaluateGroups($criteria, [
                'gpa' => 3.8,
                'sat_score' => 1200,
            ]);

            expect($results['combination_passed'])->toBeTrue();
        });
    });

    describe('Group Scores and Weights', function () {
        it('calculates group scores correctly', function () {
            $criteria = Criteria::factory()->create(['name' => 'Score Test']);
            $group = RuleGroup::factory()
                ->for($criteria)
                ->create(['logic_type' => GroupCombination::ALL]);

            Rule::factory()->active()->create(['field' => 'a', 'operator' => '==', 'value' => true, 'group_id' => $group->id]);
            Rule::factory()->active()->create(['field' => 'b', 'operator' => '==', 'value' => true, 'group_id' => $group->id]);

            $evaluator = app(Eligify::class)->getAdvancedRuleEngine();
            $results = $evaluator->evaluateGroups($criteria, ['a' => true, 'b' => true]);

            expect($results['group_results'][$group->id]['score'])->toBe(100.0);
        });

        it('tracks passed and failed rule counts in groups', function () {
            $criteria = Criteria::factory()->create(['name' => 'Rule Count Test']);
            $group = RuleGroup::factory()
                ->for($criteria)
                ->create(['logic_type' => GroupCombination::ALL]);

            Rule::factory()->active()->create(['field' => 'a', 'operator' => '==', 'value' => true, 'group_id' => $group->id]);
            Rule::factory()->active()->create(['field' => 'b', 'operator' => '==', 'value' => true, 'group_id' => $group->id]);

            $evaluator = app(Eligify::class)->getAdvancedRuleEngine();
            $results = $evaluator->evaluateGroups($criteria, ['a' => true, 'b' => false]);

            expect($results['group_results'][$group->id]['rule_count'])->toBe(2);
            expect($results['group_results'][$group->id]['passed_rules'])->toBe(1);
        });
    });

    describe('Inactive Rules and Groups', function () {
        it('skips inactive rules in groups', function () {
            $criteria = Criteria::factory()->create(['name' => 'Inactive Rules Test']);
            $group = RuleGroup::factory()
                ->for($criteria)
                ->create(['logic_type' => GroupCombination::ALL]);

            Rule::factory()->active()->create(['field' => 'a', 'operator' => '==', 'value' => true, 'is_active' => true, 'group_id' => $group->id]);
            Rule::factory()->active()->create(['field' => 'b', 'operator' => '==', 'value' => true, 'is_active' => false, 'group_id' => $group->id]);

            $evaluator = app(Eligify::class)->getAdvancedRuleEngine();
            $results = $evaluator->evaluateGroups($criteria, ['a' => true, 'b' => false]);

            expect($results['group_results'][$group->id]['rule_count'])->toBe(1);
            expect($results['group_results'][$group->id]['passed'])->toBeTrue();
        });
    });

    describe('Group Metadata', function () {
        it('includes group metadata in results', function () {
            $criteria = Criteria::factory()->create(['name' => 'Metadata Test']);
            $group = RuleGroup::factory()
                ->for($criteria)
                ->create([
                    'name' => 'identity',
                    'description' => 'Identity verification checks',
                    'logic_type' => GroupCombination::ALL,
                    'meta' => ['department' => 'compliance'],
                ]);

            Rule::factory()->active()->create(['field' => 'verified', 'operator' => '==', 'value' => true, 'group_id' => $group->id]);

            $evaluator = app(Eligify::class)->getAdvancedRuleEngine();
            $results = $evaluator->evaluateGroups($criteria, ['verified' => true]);

            expect($results['group_results'][$group->id]['name'])->toBe('identity');
            expect($results['group_results'][$group->id]['description'])->toBe('Identity verification checks');
        });
    });

    describe('Error Handling', function () {
        it('handles empty groups gracefully', function () {
            $criteria = Criteria::factory()->create(['name' => 'Empty Group Test']);
            $group = RuleGroup::factory()
                ->for($criteria)
                ->create(['logic_type' => GroupCombination::ALL]);

            $evaluator = app(Eligify::class)->getAdvancedRuleEngine();
            $results = $evaluator->evaluateGroups($criteria, []);

            expect($results['group_results'][$group->id]['passed'])->toBeTrue();
            expect($results['group_results'][$group->id]['details'])->toBe('No active rules in group');
        });

        it('handles rule evaluation errors', function () {
            $criteria = Criteria::factory()->create(['name' => 'Error Test']);
            $group = RuleGroup::factory()
                ->for($criteria)
                ->create(['logic_type' => GroupCombination::ALL]);

            Rule::factory()->active()->create(['field' => 'nonexistent', 'operator' => '==', 'value' => 'test', 'group_id' => $group->id]);

            $evaluator = app(Eligify::class)->getAdvancedRuleEngine();
            $results = $evaluator->evaluateGroups($criteria, ['other_field' => 'value']);

            // Rule should fail when field is missing
            expect($results['group_results'][$group->id]['passed_rules'])->toBe(0);
        });
    });

    describe('Group Ordering', function () {
        it('respects group order', function () {
            $criteria = Criteria::factory()->create(['name' => 'Order Test']);

            $group2 = RuleGroup::factory()
                ->for($criteria)
                ->create(['name' => 'group2', 'logic_type' => GroupCombination::ALL, 'order' => 2]);

            $group1 = RuleGroup::factory()
                ->for($criteria)
                ->create(['name' => 'group1', 'logic_type' => GroupCombination::ALL, 'order' => 1]);

            Rule::factory()->active()->create(['field' => 'a', 'operator' => '==', 'value' => true, 'group_id' => $group1->id]);
            Rule::factory()->active()->create(['field' => 'b', 'operator' => '==', 'value' => true, 'group_id' => $group2->id]);

            $groups = $criteria->groups()->orderBy('order')->get();
            expect($groups->first()->name)->toBe('group1');
            expect($groups->last()->name)->toBe('group2');
        });
    });
});
