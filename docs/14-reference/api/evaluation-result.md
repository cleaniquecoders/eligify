# Evaluation Result API

Result object returned from eligibility evaluation.

## Properties

### `passed: bool`

Whether the evaluation passed.

```php
if ($result->passed) {
    // Approved
}
```

### `score: float`

Numerical score (0-100).

```php
echo "Score: {$result->score}%";
```

### `passedRules: array`

Array of rules that passed.

```php
foreach ($result->passedRules as $rule) {
    echo "{$rule['field']} passed";
}
```

### `failedRules: array`

Array of rules that failed.

```php
foreach ($result->failedRules as $rule) {
    echo "{$rule['field']} failed";
}
```

### `snapshot: array`

Entity data snapshot.

```php
$originalIncome = $result->snapshot['income'];
```

## Methods

### `passed(): bool`

Check if evaluation passed.

```php
if ($result->passed()) {
    // Approved
}
```

### `failed(): bool`

Check if evaluation failed.

```php
if ($result->failed()) {
    // Rejected
}
```

### `toArray(): array`

Convert to array.

```php
return response()->json($result->toArray());
```

### `toJson(): string`

Convert to JSON.

```php
$json = $result->toJson();
```

## Related

- [Eligify Facade API](eligify-facade.md)
- [Criteria Builder API](criteria-builder.md)
