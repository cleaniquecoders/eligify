# Criteria Builder API

Fluent builder API for defining eligibility criteria.

## Methods

### `type(string $type): self`

Set the high-level classification for the criteria.

```php
$builder->type('subscription'); // or 'feature', 'policy', ...
```

Returns: Builder instance for chaining

---

### `group(string $group): self`

Set the functional area grouping.

```php
$builder->group('billing'); // e.g., 'billing', 'access-control', 'risk'
```

Returns: Builder instance for chaining

---

### `category(string $category): self`

Set the tier/variant classification.

```php
$builder->category('premium'); // e.g., 'basic', 'premium', 'enterprise'
```

Returns: Builder instance for chaining

---

### `tags(array $tags): self`

Replace the tag list entirely (stored as JSON array).

```php
$builder->tags(['beta', 'internal']);
```

Notes: Tags are normalized (trimmed, lowercased, unique)

Returns: Builder instance for chaining

---

### `addTags(string|array ...$tags): self`

Add one or more tags, preserving existing ones (unique after merge).

```php
$builder->addTags('early-access', ['beta']);
```

Returns: Builder instance for chaining

---

### `removeTags(string|array ...$tags): self`

Remove one or more tags; missing tags are ignored.

```php
$builder->removeTags('internal', ['deprecated']);
```

Returns: Builder instance for chaining

---

### `clearTags(): self`

Remove all tags.

```php
$builder->clearTags();
```

Returns: Builder instance for chaining

---

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
- [Models](models.md)
