# Models API

Eloquent models provided by Eligify, with fields, relationships, and useful scopes.

## Criteria

Represents an eligibility criteria set.

### Attributes (Criteria)

- `uuid` (string, uuid)
- `name` (string)
- `slug` (string)
- `description` (string|null)
- `is_active` (bool)
- `type` (string|null)
- `group` (string|null)
- `category` (string|null)
- `tags` (array|null)
- `meta` (array|null)

### Relationships (Criteria)

- `rules(): HasMany`
- `evaluations(): HasMany`

### Scopes (Criteria)

- `active()`
- `type(string|array $type)`
- `group(string|array $group)`
- `category(string|array $category)`
- `tagged(string|array $tags)` — AND semantics across provided tags

### Example

```php
use CleaniqueCoders\\Eligify\\Models\\Criteria;

$criteria = Criteria::query()
    ->active()
    ->type(['subscription', 'feature'])
    ->tagged(['beta'])
    ->get();
```

---

## Rule

Individual rule belonging to a criteria.

### Attributes (Rule)

- `uuid` (string, uuid)
- `criteria_id` (int)
- `field` (string)
- `operator` (string)
- `value` (mixed|null, json)
- `weight` (int)
- `order` (int)
- `is_active` (bool)
- `meta` (array|null)

### Relationships (Rule)

- `criteria(): BelongsTo`

### Scopes (Rule)

- `active()`
- `ordered()`
- `byField(string $field)`
- `byOperator(string $operator)`

---

## Evaluation

Stored evaluation results with context and scoring details.

### Attributes (Evaluation)

- `uuid` (string, uuid)
- `criteria_id` (int)
- `evaluable_type` (string|null)
- `evaluable_id` (int|null)
- `passed` (bool)
- `score` (decimal)
- `failed_rules` (array|null)
- `rule_results` (array|null)
- `decision` (string|null)
- `context` (array|null)
- `meta` (array|null)
- `evaluated_at` (datetime)

### Relationships (Evaluation)

- `criteria(): BelongsTo`
- `evaluable(): MorphTo`

### Helpers

- `getSummary(): array`
- `getFailedRuleIds(): Collection`
- `ruleFailedById(int $ruleId): bool`

---

## AuditLog

Comprehensive audit log entries for Eligify events.

### Attributes (AuditLog)

- `uuid` (string, uuid)
- `event` (string)
- `auditable_type` (string)
- `auditable_id` (int)
- `old_values` (array|null)
- `new_values` (array|null)
- `context` (array|null)
- `user_type` (string|null)
- `user_id` (int|null)
- `ip_address` (string|null)
- `user_agent` (string|null)
- `meta` (array|null)

### Relationships

- `auditable(): MorphTo`

### Scopes/Helpers

- `byEvent(string $event)`
- `byDateRange($start, $end)`
- `byUser(string $userType, int $userId)`
- `byIpAddress(string $ip)`
- `getChanges(): array`
- `getDescription(): string`

---

## Attaching Criteria to Models

Use the `HasCriteria` trait to attach criteria to any Eloquent model via the `eligify_criteriables` pivot.

```php
use CleaniqueCoders\\Eligify\\Concerns\\HasCriteria;

class User extends Model {
    use HasCriteria;
}

$user->attachCriteria([$criteriaId1, $criteriaId2]);
$criteria = $user->criteriaOfType(['subscription', 'feature'])->get();
```

See also: [Core Features: Criteria Attachments](../../03-core-features/criteria-attachments.md)

## Related

- [Database Schema](../database-schema.md)
