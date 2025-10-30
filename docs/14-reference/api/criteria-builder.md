# Criteria Builder API

Fluent builder API for defining eligibility criteria.

## Methods

### `addRule(string $field, string $operator, mixed $value, ?float $weight = null): self`

Add a rule to the criteria.

```php
$builder->addRule('income', '>=', 3000, 0.4);
```

**Parameters:**

- `$field` (string): Field name to evaluate
- `$operator` (string): Comparison operator
- `$value` (mixed): Value to compare against
- `$weight` (float|null): Optional weight for scoring (0-1)

**Returns:** Builder instance for chaining

### `scoringMethod(string $method): self`

Set the scoring method.

```php
$builder->scoringMethod('weighted');
```

**Parameters:**

- `$method` (string): `weighted`, `pass_fail`, or `percentage`

**Returns:** Builder instance

### `threshold(float $score): self`

Set minimum passing score.

```php
$builder->threshold(75.0);
```

**Parameters:**

- `$score` (float): Minimum score to pass (0-100)

**Returns:** Builder instance

### `cacheFor(int $seconds): self`

Enable result caching.

```php
$builder->cacheFor(3600); // 1 hour
```

**Parameters:**

- `$seconds` (int): Cache TTL in seconds

**Returns:** Builder instance

### `onPass(callable $callback): self`

Register callback for passed evaluation.

```php
$builder->onPass(function ($entity) {
    $entity->approve();
});
```

**Parameters:**

- `$callback` (callable): Function to execute on pass

**Returns:** Builder instance

### `onFail(callable $callback): self`

Register callback for failed evaluation.

```php
$builder->onFail(function ($entity, $result) {
    $entity->reject($result);
});
```

**Parameters:**

- `$callback` (callable): Function to execute on failure

**Returns:** Builder instance

### `evaluate(Model $entity): EvaluationResult`

Evaluate the criteria against an entity.

```php
$result = $builder->evaluate($applicant);
```

**Parameters:**

- `$entity` (Model): Entity to evaluate

**Returns:** `EvaluationResult` instance

## Related

- [Eligify Facade API](eligify-facade.md)
- [Evaluation Result API](evaluation-result.md)
