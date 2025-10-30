# Eligify Facade API

Complete API reference for the main Eligify facade.

## Methods

### `criteria(string $name): CriteriaBuilder`

Create a new criteria builder instance.

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

$builder = Eligify::criteria('loan_approval');
```

**Parameters:**

- `$name` (string): Unique name for the criteria

**Returns:** `CriteriaBuilder` instance

### `snapshot(Model $model, string $context): Snapshot`

Create a snapshot of model data.

```php
$snapshot = Eligify::snapshot($user, 'loan_application');
```

**Parameters:**

- `$model` (Model): Eloquent model to snapshot
- `$context` (string): Context description

**Returns:** `Snapshot` instance

### `registerOperator(string $name, callable $callback): void`

Register a custom operator.

```php
Eligify::registerOperator('divisible_by', function ($value, $divisor) {
    return $value % $divisor === 0;
});
```

**Parameters:**

- `$name` (string): Operator name
- `$callback` (callable): Evaluation function

### `clearCache(?string $criteria = null): void`

Clear cached evaluation results.

```php
// Clear all cache
Eligify::clearCache();

// Clear specific criteria
Eligify::clearCache('loan_approval');
```

**Parameters:**

- `$criteria` (string|null): Optional criteria name to clear

## Related

- [Criteria Builder API](criteria-builder.md)
- [Evaluation Result API](evaluation-result.md)
