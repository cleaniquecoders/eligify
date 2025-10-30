# Database Schema

Complete database schema documentation for Eligify tables.

## eligify_criteria

Stores criteria definitions.

### Columns

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | string(255) | Unique criteria name |
| description | text | Description |
| rules | json | Array of rule definitions |
| scoring_method | string | Scoring method |
| threshold | decimal(5,2) | Minimum passing score |
| is_active | boolean | Active status |
| created_at | timestamp | Creation timestamp |
| updated_at | timestamp | Update timestamp |

### Indexes

- `PRIMARY KEY (id)`
- `UNIQUE KEY (name)`
- `INDEX (is_active)`

## eligify_audits

Stores evaluation audit logs.

### Columns

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| criteria_name | string(255) | Evaluated criteria |
| entity_type | string(255) | Entity class name |
| entity_id | bigint | Entity ID |
| user_id | bigint | User who triggered |
| passed | boolean | Evaluation result |
| score | decimal(5,2) | Score achieved |
| passed_rules | json | Rules that passed |
| failed_rules | json | Rules that failed |
| snapshot | json | Entity snapshot |
| created_at | timestamp | Evaluation timestamp |

### Indexes

- `PRIMARY KEY (id)`
- `INDEX (criteria_name, created_at)`
- `INDEX (entity_type, entity_id)`
- `INDEX (user_id)`
- `INDEX (passed)`

## eligify_snapshots

Stores entity data snapshots.

### Columns

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| entity_type | string(255) | Model class |
| entity_id | bigint | Model ID |
| context | string(255) | Snapshot context |
| data | json | Captured data |
| created_at | timestamp | Creation timestamp |

### Indexes

- `PRIMARY KEY (id)`
- `INDEX (entity_type, entity_id, context)`
- `INDEX (created_at)`

## Relationships

```
eligify_criteria
    └─ has many → eligify_audits (via criteria_name)

eligify_audits
    ├─ belongs to → users (via user_id)
    └─ morphs to → entity (via entity_type, entity_id)

eligify_snapshots
    └─ morphs to → entity (via entity_type, entity_id)
```

## Migration

```php
php artisan migrate
```

## Related

- [Models API](api/models.md)
- [Data Management](../../04-data-management/README.md)
