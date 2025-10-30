<?php

use CleaniqueCoders\Eligify\Data\Snapshot;

test('can create snapshot instance', function () {
    $data = ['income' => 50000, 'credit_score' => 720];
    $snapshot = new Snapshot($data);

    expect($snapshot)->toBeInstanceOf(Snapshot::class)
        ->and($snapshot->toArray())->toBe($data);
});

test('can access data via property syntax', function () {
    $snapshot = new Snapshot([
        'income' => 50000,
        'credit_score' => 720,
    ]);

    expect($snapshot->income)->toBe(50000)
        ->and($snapshot->credit_score)->toBe(720);
});

test('can access data via array syntax', function () {
    $snapshot = new Snapshot([
        'income' => 50000,
        'credit_score' => 720,
    ]);

    expect($snapshot['income'])->toBe(50000)
        ->and($snapshot['credit_score'])->toBe(720);
});

test('can get data with default value', function () {
    $snapshot = new Snapshot(['income' => 50000]);

    expect($snapshot->get('credit_score', 650))->toBe(650)
        ->and($snapshot->get('income', 0))->toBe(50000);
});

test('can check if key exists', function () {
    $snapshot = new Snapshot(['income' => 50000]);

    expect($snapshot->has('income'))->toBeTrue()
        ->and($snapshot->has('credit_score'))->toBeFalse()
        ->and(isset($snapshot->income))->toBeTrue()
        ->and(isset($snapshot->credit_score))->toBeFalse();
});

test('can get all data', function () {
    $data = ['income' => 50000, 'credit_score' => 720];
    $snapshot = new Snapshot($data);

    expect($snapshot->all())->toBe($data);
});

test('can get only specified keys', function () {
    $snapshot = new Snapshot([
        'income' => 50000,
        'credit_score' => 720,
        'age' => 30,
        'name' => 'John',
    ]);

    $subset = $snapshot->only(['income', 'credit_score']);

    expect($subset)->toBeInstanceOf(Snapshot::class)
        ->and($subset->toArray())->toBe([
            'income' => 50000,
            'credit_score' => 720,
        ]);
});

test('can get all except specified keys', function () {
    $snapshot = new Snapshot([
        'income' => 50000,
        'credit_score' => 720,
        'ssn' => '123-45-6789',
        'account_number' => 'ACC123',
    ]);

    $safe = $snapshot->except(['ssn', 'account_number']);

    expect($safe->toArray())->toBe([
        'income' => 50000,
        'credit_score' => 720,
    ]);
});

test('can filter data with callback', function () {
    $snapshot = new Snapshot([
        'income' => 50000,
        'credit_score' => 720,
        'age' => 30,
        'name' => 'John',
    ]);

    $numeric = $snapshot->filter(fn ($value) => is_numeric($value));

    expect($numeric->toArray())->toBe([
        'income' => 50000,
        'credit_score' => 720,
        'age' => 30,
    ]);
});

test('can transform data with callback', function () {
    $snapshot = new Snapshot([
        'income' => 50000.4,
        'credit_score' => 720.6,
    ]);

    $rounded = $snapshot->transform(fn ($value) => is_numeric($value) ? (int) round($value) : $value);

    expect($rounded->toArray())->toBe([
        'income' => 50000,
        'credit_score' => 721,
    ]);
});

test('can merge additional data', function () {
    $snapshot = new Snapshot(['income' => 50000]);
    $merged = $snapshot->merge(['credit_score' => 720]);

    expect($merged->toArray())->toBe([
        'income' => 50000,
        'credit_score' => 720,
    ]);
});

test('has metadata tracking', function () {
    $snapshot = new Snapshot(
        ['income' => 50000],
        ['model_class' => 'App\Models\User']
    );

    expect($snapshot->metadata('model_class'))->toBe('App\Models\User')
        ->and($snapshot->metadata('field_count'))->toBe(1)
        ->and($snapshot->metadata('captured_at'))->toBeString();
});

test('can get fields matching pattern', function () {
    $snapshot = new Snapshot([
        'loans_count' => 2,
        'loans_total' => 10000,
        'income' => 50000,
        'credit_score' => 720,
    ]);

    $loanFields = $snapshot->whereKeyMatches('/^loans_/');

    expect($loanFields->toArray())->toBe([
        'loans_count' => 2,
        'loans_total' => 10000,
    ]);
});

test('can filter numeric fields only', function () {
    $snapshot = new Snapshot([
        'income' => 50000,
        'name' => 'John',
        'age' => 30,
        'is_verified' => true,
    ]);

    $numeric = $snapshot->numericFields();

    expect($numeric->toArray())->toBe([
        'income' => 50000,
        'age' => 30,
    ]);
});

test('can filter string fields only', function () {
    $snapshot = new Snapshot([
        'income' => 50000,
        'name' => 'John',
        'email' => 'john@example.com',
        'is_verified' => true,
    ]);

    $strings = $snapshot->stringFields();

    expect($strings->toArray())->toBe([
        'name' => 'John',
        'email' => 'john@example.com',
    ]);
});

test('can filter boolean fields only', function () {
    $snapshot = new Snapshot([
        'income' => 50000,
        'is_verified' => true,
        'has_loan' => false,
        'name' => 'John',
    ]);

    $booleans = $snapshot->booleanFields();

    expect($booleans->toArray())->toBe([
        'is_verified' => true,
        'has_loan' => false,
    ]);
});

test('can convert to json', function () {
    $snapshot = new Snapshot(['income' => 50000]);
    $json = $snapshot->toJson();

    expect($json)->toBeString()
        ->and(json_decode($json, true))->toHaveKey('data')
        ->and(json_decode($json, true))->toHaveKey('metadata');
});

test('can count fields', function () {
    $snapshot = new Snapshot([
        'income' => 50000,
        'credit_score' => 720,
        'age' => 30,
    ]);

    expect($snapshot->count())->toBe(3)
        ->and(count($snapshot))->toBe(3);
});

test('is immutable when setting properties', function () {
    $snapshot = new Snapshot(['income' => 50000]);

    expect(fn () => $snapshot->income = 60000)
        ->toThrow(\BadMethodCallException::class);
});

test('is immutable when setting array offsets', function () {
    $snapshot = new Snapshot(['income' => 50000]);

    expect(fn () => $snapshot['income'] = 60000)
        ->toThrow(\BadMethodCallException::class);
});

test('is immutable when unsetting array offsets', function () {
    $snapshot = new Snapshot(['income' => 50000]);

    try {
        unset($snapshot['income']);
        expect(false)->toBeTrue('Expected exception was not thrown');
    } catch (\BadMethodCallException $e) {
        expect($e)->toBeInstanceOf(\BadMethodCallException::class);
    }
});

test('can convert to string', function () {
    $snapshot = new Snapshot(['income' => 50000]);
    $string = (string) $snapshot;

    expect($string)->toBeString()
        ->and($string)->toContain('income')
        ->and($string)->toContain('50000');
});

test('implements arrayable interface', function () {
    $snapshot = new Snapshot(['income' => 50000]);

    expect($snapshot)->toBeInstanceOf(\Illuminate\Contracts\Support\Arrayable::class);
});

test('implements jsonable interface', function () {
    $snapshot = new Snapshot(['income' => 50000]);

    expect($snapshot)->toBeInstanceOf(\Illuminate\Contracts\Support\Jsonable::class);
});

test('implements json serializable', function () {
    $snapshot = new Snapshot(['income' => 50000]);

    expect($snapshot)->toBeInstanceOf(\JsonSerializable::class);
});

test('implements array access', function () {
    $snapshot = new Snapshot(['income' => 50000]);

    expect($snapshot)->toBeInstanceOf(\ArrayAccess::class);
});

test('implements countable', function () {
    $snapshot = new Snapshot(['income' => 50000]);

    expect($snapshot)->toBeInstanceOf(\Countable::class);
});

test('can chain multiple operations', function () {
    $snapshot = new Snapshot([
        'income' => 50000.4,
        'credit_score' => 720.3,
        'age' => 30,
        'name' => 'John',
        'ssn' => '123-45-6789',
    ]);

    $result = $snapshot
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
    $snapshot = new Snapshot(
        ['income' => 50000],
        ['model_class' => 'App\Models\User']
    );

    $filtered = $snapshot->only(['income']);

    expect($filtered->metadata('model_class'))->toBe('App\Models\User');
});
