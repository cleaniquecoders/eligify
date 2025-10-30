<?php

use CleaniqueCoders\Eligify\Support\ExtractedModelData;

test('can create extracted model data instance', function () {
    $data = ['income' => 50000, 'credit_score' => 720];
    $extracted = new ExtractedModelData($data);

    expect($extracted)->toBeInstanceOf(ExtractedModelData::class)
        ->and($extracted->toArray())->toBe($data);
});

test('can access data via property syntax', function () {
    $extracted = new ExtractedModelData([
        'income' => 50000,
        'credit_score' => 720,
    ]);

    expect($extracted->income)->toBe(50000)
        ->and($extracted->credit_score)->toBe(720);
});

test('can access data via array syntax', function () {
    $extracted = new ExtractedModelData([
        'income' => 50000,
        'credit_score' => 720,
    ]);

    expect($extracted['income'])->toBe(50000)
        ->and($extracted['credit_score'])->toBe(720);
});

test('can get data with default value', function () {
    $extracted = new ExtractedModelData(['income' => 50000]);

    expect($extracted->get('credit_score', 650))->toBe(650)
        ->and($extracted->get('income', 0))->toBe(50000);
});

test('can check if key exists', function () {
    $extracted = new ExtractedModelData(['income' => 50000]);

    expect($extracted->has('income'))->toBeTrue()
        ->and($extracted->has('credit_score'))->toBeFalse()
        ->and(isset($extracted->income))->toBeTrue()
        ->and(isset($extracted->credit_score))->toBeFalse();
});

test('can get all data', function () {
    $data = ['income' => 50000, 'credit_score' => 720];
    $extracted = new ExtractedModelData($data);

    expect($extracted->all())->toBe($data);
});

test('can get only specified keys', function () {
    $extracted = new ExtractedModelData([
        'income' => 50000,
        'credit_score' => 720,
        'age' => 30,
        'name' => 'John',
    ]);

    $subset = $extracted->only(['income', 'credit_score']);

    expect($subset)->toBeInstanceOf(ExtractedModelData::class)
        ->and($subset->toArray())->toBe([
            'income' => 50000,
            'credit_score' => 720,
        ]);
});

test('can get all except specified keys', function () {
    $extracted = new ExtractedModelData([
        'income' => 50000,
        'credit_score' => 720,
        'ssn' => '123-45-6789',
        'account_number' => 'ACC123',
    ]);

    $safe = $extracted->except(['ssn', 'account_number']);

    expect($safe->toArray())->toBe([
        'income' => 50000,
        'credit_score' => 720,
    ]);
});

test('can filter data with callback', function () {
    $extracted = new ExtractedModelData([
        'income' => 50000,
        'credit_score' => 720,
        'age' => 30,
        'name' => 'John',
    ]);

    $numeric = $extracted->filter(fn ($value) => is_numeric($value));

    expect($numeric->toArray())->toBe([
        'income' => 50000,
        'credit_score' => 720,
        'age' => 30,
    ]);
});

test('can transform data with callback', function () {
    $extracted = new ExtractedModelData([
        'income' => 50000.4,
        'credit_score' => 720.6,
    ]);

    $rounded = $extracted->transform(fn ($value) => is_numeric($value) ? (int) round($value) : $value);

    expect($rounded->toArray())->toBe([
        'income' => 50000,
        'credit_score' => 721,
    ]);
});

test('can merge additional data', function () {
    $extracted = new ExtractedModelData(['income' => 50000]);
    $merged = $extracted->merge(['credit_score' => 720]);

    expect($merged->toArray())->toBe([
        'income' => 50000,
        'credit_score' => 720,
    ]);
});

test('has metadata tracking', function () {
    $extracted = new ExtractedModelData(
        ['income' => 50000],
        ['model_class' => 'App\Models\User']
    );

    expect($extracted->metadata('model_class'))->toBe('App\Models\User')
        ->and($extracted->metadata('field_count'))->toBe(1)
        ->and($extracted->metadata('extracted_at'))->toBeString();
});

test('can get fields matching pattern', function () {
    $extracted = new ExtractedModelData([
        'loans_count' => 2,
        'loans_total' => 10000,
        'income' => 50000,
        'credit_score' => 720,
    ]);

    $loanFields = $extracted->whereKeyMatches('/^loans_/');

    expect($loanFields->toArray())->toBe([
        'loans_count' => 2,
        'loans_total' => 10000,
    ]);
});

test('can filter numeric fields only', function () {
    $extracted = new ExtractedModelData([
        'income' => 50000,
        'name' => 'John',
        'age' => 30,
        'is_verified' => true,
    ]);

    $numeric = $extracted->numericFields();

    expect($numeric->toArray())->toBe([
        'income' => 50000,
        'age' => 30,
    ]);
});

test('can filter string fields only', function () {
    $extracted = new ExtractedModelData([
        'income' => 50000,
        'name' => 'John',
        'email' => 'john@example.com',
        'is_verified' => true,
    ]);

    $strings = $extracted->stringFields();

    expect($strings->toArray())->toBe([
        'name' => 'John',
        'email' => 'john@example.com',
    ]);
});

test('can filter boolean fields only', function () {
    $extracted = new ExtractedModelData([
        'income' => 50000,
        'is_verified' => true,
        'has_loan' => false,
        'name' => 'John',
    ]);

    $booleans = $extracted->booleanFields();

    expect($booleans->toArray())->toBe([
        'is_verified' => true,
        'has_loan' => false,
    ]);
});

test('can convert to json', function () {
    $extracted = new ExtractedModelData(['income' => 50000]);
    $json = $extracted->toJson();

    expect($json)->toBeString()
        ->and(json_decode($json, true))->toHaveKey('data')
        ->and(json_decode($json, true))->toHaveKey('metadata');
});

test('can count fields', function () {
    $extracted = new ExtractedModelData([
        'income' => 50000,
        'credit_score' => 720,
        'age' => 30,
    ]);

    expect($extracted->count())->toBe(3)
        ->and(count($extracted))->toBe(3);
});

test('is immutable when setting properties', function () {
    $extracted = new ExtractedModelData(['income' => 50000]);

    expect(fn () => $extracted->income = 60000)
        ->toThrow(\BadMethodCallException::class);
});

test('is immutable when setting array offsets', function () {
    $extracted = new ExtractedModelData(['income' => 50000]);

    expect(fn () => $extracted['income'] = 60000)
        ->toThrow(\BadMethodCallException::class);
});

test('is immutable when unsetting array offsets', function () {
    $extracted = new ExtractedModelData(['income' => 50000]);

    try {
        unset($extracted['income']);
        expect(false)->toBeTrue('Expected exception was not thrown');
    } catch (\BadMethodCallException $e) {
        expect($e)->toBeInstanceOf(\BadMethodCallException::class);
    }
});

test('can convert to string', function () {
    $extracted = new ExtractedModelData(['income' => 50000]);
    $string = (string) $extracted;

    expect($string)->toBeString()
        ->and($string)->toContain('income')
        ->and($string)->toContain('50000');
});

test('implements arrayable interface', function () {
    $extracted = new ExtractedModelData(['income' => 50000]);

    expect($extracted)->toBeInstanceOf(\Illuminate\Contracts\Support\Arrayable::class);
});

test('implements jsonable interface', function () {
    $extracted = new ExtractedModelData(['income' => 50000]);

    expect($extracted)->toBeInstanceOf(\Illuminate\Contracts\Support\Jsonable::class);
});

test('implements json serializable', function () {
    $extracted = new ExtractedModelData(['income' => 50000]);

    expect($extracted)->toBeInstanceOf(\JsonSerializable::class);
});

test('implements array access', function () {
    $extracted = new ExtractedModelData(['income' => 50000]);

    expect($extracted)->toBeInstanceOf(\ArrayAccess::class);
});

test('implements countable', function () {
    $extracted = new ExtractedModelData(['income' => 50000]);

    expect($extracted)->toBeInstanceOf(\Countable::class);
});

test('can chain multiple operations', function () {
    $extracted = new ExtractedModelData([
        'income' => 50000.4,
        'credit_score' => 720.3,
        'age' => 30,
        'name' => 'John',
        'ssn' => '123-45-6789',
    ]);

    $result = $extracted
        ->except(['ssn'])
        ->numericFields()
        ->transform(fn ($value) => (int) round($value));

    expect($result->toArray())->toBe([
        'income' => 50000,
        'credit_score' => 720,
        'age' => 30,
    ]);
});

test('preserves metadata through transformations', function () {
    $extracted = new ExtractedModelData(
        ['income' => 50000],
        ['model_class' => 'App\Models\User']
    );

    $filtered = $extracted->only(['income']);

    expect($filtered->metadata('model_class'))->toBe('App\Models\User');
});
