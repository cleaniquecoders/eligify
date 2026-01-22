# Database Schema

Canonical database schema for Eligify. This reflects the current migration stubs shipped by the package.

## Tables

### eligify_criteria

Defines eligibility criteria sets and their metadata.

#### Columns (eligify_criteria)

| Column | Type | Notes |
|--------|------|-------|
| id | bigint | Primary key |
| uuid | uuid | Unique, indexed |
| name | string(255) | Name (e.g., "Loan Approval") |
| slug | string(255) | Unique slug |
| description | text, nullable | Human-readable description |
| is_active | boolean | Default true |
| type | string, nullable | e.g., subscription, feature, policy |
| group | string, nullable | e.g., billing, access-control |
| category | string, nullable | e.g., basic, premium, enterprise |
| tags | json, nullable | Flexible classification tags |
| meta | json, nullable | Arbitrary metadata |
| created_at/updated_at | timestamps |  |

#### Indexes (eligify_criteria)

- uuid unique index, name+is_active index
- indexes on type, group, category

---

### eligify_rules

Individual rules within a criteria.

#### Columns (eligify_rules)

| Column | Type | Notes |
|--------|------|-------|
| id | bigint | Primary key |
| uuid | uuid | Unique, indexed |
| criteria_id | bigint FK | References eligify_criteria (cascade on delete) |
| field | string | Field to evaluate (e.g., income) |
| operator | string | e.g., >=, <=, ==, in, not_in |
| value | json, nullable | Value to compare against |
| weight | integer | Default 1 |
| order | integer | Execution order, default 0 |
| is_active | boolean | Default true |
| meta | json, nullable | Rule metadata |
| created_at/updated_at | timestamps |  |

#### Indexes (eligify_rules)

- (criteria_id, is_active), (field, operator)

---

### eligify_snapshots

Stores point-in-time data captures for audit trails and historical evaluation.

#### Columns (eligify_snapshots)

| Column | Type | Notes |
|--------|------|-------|
| id | bigint | Primary key |
| uuid | uuid | Unique, indexed |
| snapshotable_type | string | Polymorphic type (e.g., App\Models\User) |
| snapshotable_id | bigint | Polymorphic id |
| data | json | The captured snapshot data |
| checksum | string(64) | SHA-256 hash for data integrity verification |
| meta | json, nullable | Additional metadata |
| captured_at | timestamp | When snapshot was captured |
| created_at/updated_at | timestamps |  |

#### Indexes (eligify_snapshots)

- uuid unique index
- (snapshotable_type, snapshotable_id)
- checksum index
- (checksum, snapshotable_type, snapshotable_id) for deduplication

---

### eligify_evaluations

Stores evaluation results and audit-friendly details.

#### Columns (eligify_evaluations)

| Column | Type | Notes |
|--------|------|-------|
| id | bigint | Primary key |
| uuid | uuid | Unique, indexed |
| criteria_id | bigint FK | References eligify_criteria |
| snapshot_id | bigint FK, nullable | References eligify_snapshots |
| evaluable_type | string, nullable | Polymorphic type |
| evaluable_id | bigint, nullable | Polymorphic id |
| passed | boolean | Final result |
| score | decimal(8,2) | Calculated score |
| failed_rules | json, nullable | Failed rule IDs/details |
| rule_results | json, nullable | Per-rule evaluation details |
| decision | string, nullable | Human-readable decision |
| context | json, nullable | Evaluation context/data |
| meta | json, nullable | Extra metadata |
| evaluated_at | timestamp | Evaluation timestamp |
| created_at/updated_at | timestamps |  |

#### Indexes (eligify_evaluations)

- (evaluable_type, evaluable_id), (passed, evaluated_at), (criteria_id, passed), snapshot_id

---

### eligify_audit_logs

Comprehensive audit trail for criteria and evaluation lifecycle events.

#### Columns (eligify_audit_logs)

| Column | Type | Notes |
|--------|------|-------|
| id | bigint | Primary key |
| uuid | uuid | Unique, indexed |
| event | string | e.g., evaluation_completed, rule_executed |
| auditable_type | string | Polymorphic type |
| auditable_id | bigint | Polymorphic id |
| old_values | json, nullable | Previous state |
| new_values | json, nullable | New state |
| context | json, nullable | Additional context |
| user_type | string, nullable | Actor type |
| user_id | bigint, nullable | Actor id |
| ip_address | string, nullable |  |
| user_agent | string, nullable |  |
| meta | json, nullable | Extra metadata |
| created_at/updated_at | timestamps |  |

#### Indexes (eligify_audit_logs)

- (auditable_type, auditable_id), (event, created_at), (user_type, user_id)

---

### eligify_criteriables

Polymorphic pivot for attaching criteria to any model.

#### Columns (eligify_criteriables)

| Column | Type | Notes |
|--------|------|-------|
| id | bigint | Primary key |
| criteria_id | bigint FK | References eligify_criteria (cascade on delete) |
| criteriable_type | string | Polymorphic type |
| criteriable_id | bigint | Polymorphic id |
| created_at/updated_at | timestamps |  |

#### Indexes

- unique(criteria_id, criteriable_type, criteriable_id)
- index(criteriable_type, criteriable_id)

---

## Relationships

```text
eligify_criteria
    ├─ has many → eligify_rules
    ├─ has many → eligify_evaluations
    └─ morphToMany → criteriable models (via eligify_criteriables)

eligify_rules
    └─ belongs to → eligify_criteria

eligify_snapshots
    ├─ has many → eligify_evaluations
    └─ morphs to → snapshotable (snapshotable_type, snapshotable_id)

eligify_evaluations
    ├─ belongs to → eligify_criteria
    ├─ belongs to → eligify_snapshots (nullable)
    └─ morphs to → evaluable (evaluable_type, evaluable_id)

eligify_audit_logs
    └─ morphs to → auditable (auditable_type, auditable_id)

eligify_criteriables
    ├─ belongs to → eligify_criteria
    └─ morphs to → criteriable (criteriable_type, criteriable_id)
```

## Migration

```bash
php artisan vendor:publish --tag="eligify-migrations"
php artisan migrate
```

## Related

- [Models API](api/models.md)
- [Core Features: Criteria Attachments](../../03-core-features/criteria-attachments.md)
