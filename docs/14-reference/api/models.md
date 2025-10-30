# Models API

Eloquent model APIs for Eligify.

## Criteria Model

### Properties

- `name` (string): Unique criteria name
- `description` (string): Description
- `rules` (array): Array of rule definitions
- `scoring_method` (string): Scoring method
- `threshold` (float): Minimum passing score
- `is_active` (bool): Active status

### Methods

#### `activate(): void`

Activate the criteria.

```php
$criteria->activate();
```

#### `deactivate(): void`

Deactivate the criteria.

```php
$criteria->deactivate();
```

## Audit Model

### Properties

- `criteria_name` (string): Evaluated criteria
- `entity_type` (string): Entity class name
- `entity_id` (int): Entity ID
- `passed` (bool): Evaluation result
- `score` (float): Score achieved
- `snapshot` (array): Entity snapshot
- `user_id` (int): User who triggered evaluation

### Relationships

#### `user(): BelongsTo`

User who performed evaluation.

```php
$audit->user->name;
```

#### `entity(): MorphTo`

Entity that was evaluated.

```php
$audit->entity; // User, Applicant, etc.
```

## Snapshot Model

### Properties

- `entity_type` (string): Model class
- `entity_id` (int): Model ID
- `context` (string): Snapshot context
- `data` (array): Captured data

### Methods

#### `restore(): Model`

Restore entity from snapshot.

```php
$restoredUser = $snapshot->restore();
```

## Related

- [Database Schema](../database-schema.md)
