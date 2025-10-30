# API Reference

Complete API documentation for all Eligify classes, methods, and interfaces.

## Overview

Comprehensive reference for developers integrating Eligify.

## Documentation in this Section

### API Documentation

- **[Eligify Facade](api/eligify-facade.md)** - Main facade interface
- **[Criteria Builder](api/criteria-builder.md)** - Fluent builder API
- **[Evaluation Result](api/evaluation-result.md)** - Result object structure
- **[Models](api/models.md)** - Eloquent model APIs

### Reference Guides

- **[Operators](operators.md)** - All available operators
- **[Enums](enums.md)** - All enum values
- **[Events](events.md)** - Event system reference
- **[Database Schema](database-schema.md)** - Complete schema documentation

## Quick Reference

### Eligify Facade

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

// Create criteria
Eligify::criteria(string $name): CriteriaBuilder

// Create snapshot
Eligify::snapshot(Model $model, string $context): Snapshot

// Register operator
Eligify::registerOperator(string $name, callable $callback): void

// Cache management
Eligify::clearCache(?string $criteria = null): void
```

### Criteria Builder

```php
$builder = Eligify::criteria('name')
    ->addRule(string $field, string $operator, mixed $value, ?float $weight = null)
    ->scoringMethod(string $method)
    ->passingThreshold(int $threshold)
    ->onPass(callable $callback)
    ->onFail(callable $callback)
    ->cache(?int $ttl = null)
    ->evaluate(Model|array $entity);
```

### Evaluation Result

```php
$result = $criteria->evaluate($entity);

$result->passed(): bool
$result->failed(): bool
$result->score(): float|int
$result->failedRules(): array
$result->passedRules(): array
$result->allRules(): array
$result->decision(): string
```

## Available Operators

| Operator | Symbol | Description |
|----------|--------|-------------|
| equals | `==` | Equal to |
| not_equals | `!=` | Not equal to |
| greater_than | `>` | Greater than |
| greater_than_or_equal | `>=` | Greater than or equal |
| less_than | `<` | Less than |
| less_than_or_equal | `<=` | Less than or equal |
| in | `in` | Value in array |
| not_in | `not_in` | Value not in array |
| contains | `contains` | String/Array contains |
| starts_with | `starts_with` | String starts with |
| ends_with | `ends_with` | String ends with |
| between | `between` | Value between range |
| regex | `regex` | Matches regex pattern |

## Enums

### ScoringMethod

- `WEIGHTED` - Weighted average scoring
- `PASS_FAIL` - Binary pass/fail
- `SUM` - Sum of all rule scores
- `AVERAGE` - Average of all rule scores

### Decision

- `APPROVED` - Eligibility approved
- `REJECTED` - Eligibility rejected
- `PENDING` - Requires manual review
- `CONDITIONAL` - Conditionally approved

## Events

All events are in the `CleaniqueCoders\Eligify\Events` namespace:

- `CriteriaCreated` - New criteria defined
- `CriteriaUpdated` - Criteria modified
- `EvaluationStarted` - Before evaluation
- `EvaluationCompleted` - After evaluation
- `EvaluationPassed` - Evaluation passed
- `EvaluationFailed` - Evaluation failed
- `RuleEvaluated` - Individual rule evaluated
- `SnapshotCreated` - Snapshot created
- `CacheHit` - Cache hit occurred
- `CacheMiss` - Cache miss occurred

## Database Schema

### `eligify_criteria` Table

```sql
id, name, description, rules, scoring_method,
passing_threshold, created_at, updated_at
```

### `eligify_evaluations` Table

```sql
id, criteria_id, entity_type, entity_id, result,
score, passed, failed_rules, created_at
```

### `eligify_snapshots` Table

```sql
id, entity_type, entity_id, context, data,
created_at, updated_at
```

### `eligify_audit_logs` Table

```sql
id, evaluation_id, user_id, action, changes,
metadata, created_at
```

## Related Sections

- [Core Features](../03-core-features/) - Using the API
- [Examples](../13-examples/) - API usage examples
- [Advanced Features](../07-advanced-features/) - Advanced API patterns
