<?php

use CleaniqueCoders\Eligify\Mappings\UserModelMapping;
use CleaniqueCoders\Eligify\Support\MappingRegistry;

describe('MappingRegistry', function () {
    beforeEach(function () {
        // Clear cache before each test
        MappingRegistry::clearCache();
    });

    test('it can discover all available mappings', function () {
        $mappings = MappingRegistry::all();

        expect($mappings)->toBeArray()
            ->and($mappings)->not->toBeEmpty()
            ->and($mappings)->toHaveKey(UserModelMapping::class);
    });

    test('it returns mapping with correct metadata structure', function () {
        $mappings = MappingRegistry::all();
        $userMapping = $mappings[UserModelMapping::class];

        expect($userMapping)->toBeArray()
            ->and($userMapping)->toHaveKeys(['class', 'name', 'description', 'model'])
            ->and($userMapping['class'])->toBe(UserModelMapping::class)
            ->and($userMapping['name'])->toBe('User')
            ->and($userMapping['model'])->toBe('App\Models\User');
    });

    test('it can get a specific mapping instance', function () {
        $mapping = MappingRegistry::get(UserModelMapping::class);

        expect($mapping)->toBeInstanceOf(UserModelMapping::class)
            ->and($mapping->getName())->toBe('User')
            ->and($mapping->getDescription())->toBeString();
    });

    test('it returns null for non-existent mapping', function () {
        $mapping = MappingRegistry::get('NonExistentMapping');

        expect($mapping)->toBeNull();
    });

    test('it can get all fields for a mapping', function () {
        $fields = MappingRegistry::getFields(UserModelMapping::class);

        expect($fields)->toBeArray()
            ->and($fields)->not->toBeEmpty()
            ->and($fields)->toHaveKey('email_verified_timestamp')
            ->and($fields)->toHaveKey('registration_date')
            ->and($fields)->toHaveKey('is_verified');
    });

    test('field metadata includes correct structure', function () {
        $fields = MappingRegistry::getFields(UserModelMapping::class);
        $emailVerifiedField = $fields['email_verified_timestamp'];

        expect($emailVerifiedField)->toBeArray()
            ->and($emailVerifiedField)->toHaveKeys(['type', 'description', 'category', 'original'])
            ->and($emailVerifiedField['type'])->toBe('date')
            ->and($emailVerifiedField['category'])->toBe('attribute')
            ->and($emailVerifiedField['original'])->toBe('email_verified_at');
    });

    test('it can get metadata for a specific mapping', function () {
        $meta = MappingRegistry::getMeta(UserModelMapping::class);

        expect($meta)->toBeArray()
            ->and($meta)->toHaveKeys(['class', 'name', 'description', 'model', 'fields_count'])
            ->and($meta['fields_count'])->toBeInt()
            ->and($meta['fields_count'])->toBeGreaterThan(0);
    });

    test('it returns empty array for invalid mapping metadata', function () {
        $meta = MappingRegistry::getMeta('InvalidMapping');

        expect($meta)->toBeArray()
            ->and($meta)->toBeEmpty();
    });

    test('it can check if mapping is registered', function () {
        expect(MappingRegistry::has(UserModelMapping::class))->toBeTrue()
            ->and(MappingRegistry::has('NonExistentMapping'))->toBeFalse();
    });

    test('it can get all mapping classes', function () {
        $classes = MappingRegistry::classes();

        expect($classes)->toBeArray()
            ->and($classes)->not->toBeEmpty()
            ->and($classes)->toContain(UserModelMapping::class);
    });

    test('it can group mappings by model', function () {
        $byModel = MappingRegistry::byModel();

        expect($byModel)->toBeArray()
            ->and($byModel)->toHaveKey('App\Models\User')
            ->and($byModel['App\Models\User'])->toBeArray()
            ->and($byModel['App\Models\User'])->not->toBeEmpty();
    });

    test('it caches discovered mappings', function () {
        // First call should populate cache
        $first = MappingRegistry::all();

        // Second call should use cache (same reference)
        $second = MappingRegistry::all();

        expect($first)->toBe($second);
    });

    test('clearing cache forces rediscovery', function () {
        $first = MappingRegistry::all();
        $firstCount = count($first);

        MappingRegistry::clearCache();
        $second = MappingRegistry::all();

        // After clearing cache, we should still get the same results
        expect($second)->toBeArray()
            ->and(count($second))->toBe($firstCount)
            ->and($second)->toEqual($first);
    });

    test('field descriptions are available', function () {
        $mapping = MappingRegistry::get(UserModelMapping::class);

        expect($mapping->getFieldDescription('email_verified_timestamp'))->toContain('verified')
            ->and($mapping->getFieldDescription('registration_date'))->toContain('date')
            ->and($mapping->getFieldDescription('is_verified'))->toContain('verified');
    });

    test('field types are available', function () {
        $mapping = MappingRegistry::get(UserModelMapping::class);

        expect($mapping->getFieldType('email_verified_timestamp'))->toBe('date')
            ->and($mapping->getFieldType('registration_date'))->toBe('date')
            ->and($mapping->getFieldType('is_verified'))->toBe('boolean');
    });

    test('computed fields are included in available fields', function () {
        $fields = MappingRegistry::getFields(UserModelMapping::class);

        expect($fields)->toHaveKey('is_verified')
            ->and($fields['is_verified']['category'])->toBe('computed')
            ->and($fields['is_verified']['type'])->toBe('boolean');
    });

    test('fields are sorted by category', function () {
        $fields = MappingRegistry::getFields(UserModelMapping::class);
        $categories = array_map(fn ($field) => $field['category'], $fields);

        // Get unique categories in order
        $uniqueCategories = array_values(array_unique($categories));

        // Attributes should come before computed fields
        $attributeIndex = array_search('attribute', $uniqueCategories);
        $computedIndex = array_search('computed', $uniqueCategories);

        if ($attributeIndex !== false && $computedIndex !== false) {
            expect($attributeIndex)->toBeLessThan($computedIndex);
        }
    });
});
