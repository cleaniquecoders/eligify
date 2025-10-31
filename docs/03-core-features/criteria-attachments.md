# Criteria Attachments

Attach criteria to any Eloquent model using a polymorphic many-to-many pivot.

## Overview

Eligify provides a pivot table `eligify_criteriables` and a reusable trait `HasCriteria` so your models can have many criteria (e.g., subscriptions, features, policies).

## Setup

```php
use CleaniqueCoders\Eligify\Concerns\HasCriteria;

class User extends Model
{
    use HasCriteria;
}
```

## Attaching

```php
// Attach by criteria IDs (sync without detaching)
$user->attachCriteria([$criteriaId1, $criteriaId2]);

// Replace the set
$user->syncCriteria([$criteriaId3]);

// Detach
$user->detachCriteria([$criteriaId1]);
```

## Querying

```php
// All criteria attached to the model
$user->criteria()->get();

// Filter by type/category/group
$user->criteriaOfType(['subscription', 'feature'])->get();
$user->criteriaInGroup('billing')->get();
$user->criteriaInCategory('premium')->get();

// Filter by tags (AND semantics)
$user->criteriaTagged(['beta'])->get();
```

## Eager Loading

```php
$users = User::with('criteria')->get();
```

## Notes

- Pivot table: `eligify_criteriables` with columns `criteria_id`, `criteriable_type`, `criteriable_id`.
- Uses Laravel's standard polymorphic many-to-many relation, so it works with any model.
- Consider extending the pivot in the future with `active`, `starts_at`, `ends_at`, `meta` if you need lifecycle control.

## Related

- [Database Schema](../../14-reference/database-schema.md)
- [Models API](../../14-reference/api/models.md)
- [Criteria Builder](criteria-builder.md)
