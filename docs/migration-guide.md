# Migration Guide

This guide explains the database structure and migration process for Eligify.

## Table of Contents

- [Overview](#overview)
- [Publishing Migrations](#publishing-migrations)
- [Database Schema](#database-schema)
- [Indexes and Performance](#indexes-and-performance)
- [Customizing Migrations](#customizing-migrations)
- [Multi-Tenant Setup](#multi-tenant-setup)
- [Data Migration](#data-migration)

## Overview

Eligify creates four main tables:

1. **eligify_criteria** - Stores criteria definitions
2. **eligify_rules** - Stores rules within criteria
3. **eligify_evaluations** - Records evaluation results
4. **eligify_audit_logs** - Comprehensive audit trail

## Publishing Migrations

### Standard Installation

```bash
# Publish migrations
php artisan vendor:publish --tag="eligify-migrations"

# Run migrations
php artisan migrate
```

### Fresh Installation

```bash
# Migrate fresh (WARNING: destroys all data)
php artisan migrate:fresh

# Migrate with seeding
php artisan migrate --seed
```

### Rollback

```bash
# Rollback last migration
php artisan migrate:rollback

# Rollback all Eligify migrations
php artisan migrate:rollback --path=database/migrations/*_create_eligify_table.php
```

## Database Schema

### Criteria Table

Stores eligibility criteria definitions.

```sql
CREATE TABLE eligify_criteria (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    uuid CHAR(36) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT NULL,
    is_active BOOLEAN DEFAULT true NOT NULL,
    meta JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_uuid (uuid),
    INDEX idx_slug (slug),
    INDEX idx_name_active (name, is_active)
);
```

**Columns:**

- `id` - Primary key
- `uuid` - Unique identifier for external references
- `name` - Display name (e.g., "Loan Approval")
- `slug` - URL-friendly identifier (e.g., "loan-approval")
- `description` - Detailed explanation
- `is_active` - Enable/disable criteria
- `meta` - Additional JSON metadata
- `created_at`, `updated_at` - Timestamps

**Sample Data:**

```sql
INSERT INTO eligify_criteria (uuid, name, slug, description, is_active, created_at, updated_at)
VALUES (
    UUID(),
    'Personal Loan Approval',
    'personal-loan-approval',
    'Standard criteria for personal loans up to $50,000',
    true,
    NOW(),
    NOW()
);
```

### Rules Table

Stores individual rules within criteria.

```sql
CREATE TABLE eligify_rules (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    uuid CHAR(36) UNIQUE NOT NULL,
    criteria_id BIGINT UNSIGNED NOT NULL,
    field VARCHAR(255) NOT NULL,
    operator VARCHAR(50) NOT NULL,
    value JSON NULL,
    weight INT DEFAULT 1 NOT NULL,
    order INT DEFAULT 0 NOT NULL,
    is_active BOOLEAN DEFAULT true NOT NULL,
    meta JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (criteria_id) REFERENCES eligify_criteria(id) ON DELETE CASCADE,

    INDEX idx_uuid (uuid),
    INDEX idx_criteria_active (criteria_id, is_active),
    INDEX idx_field_operator (field, operator)
);
```

**Columns:**

- `id` - Primary key
- `uuid` - Unique identifier
- `criteria_id` - Foreign key to criteria
- `field` - Field name to evaluate
- `operator` - Comparison operator (>=, <=, ==, in, etc.)
- `value` - Expected value (stored as JSON)
- `weight` - Rule importance (1-10)
- `order` - Execution order
- `is_active` - Enable/disable rule
- `meta` - Additional JSON metadata
- Foreign key cascades on delete

**Sample Data:**

```sql
INSERT INTO eligify_rules (uuid, criteria_id, field, operator, value, weight, `order`, is_active, created_at, updated_at)
VALUES
    (UUID(), 1, 'credit_score', '>=', '650', 8, 1, true, NOW(), NOW()),
    (UUID(), 1, 'income', '>=', '30000', 7, 2, true, NOW(), NOW()),
    (UUID(), 1, 'employment_status', 'in', '["employed","self-employed"]', 6, 3, true, NOW(), NOW());
```

### Evaluations Table

Records evaluation results and history.

```sql
CREATE TABLE eligify_evaluations (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    uuid CHAR(36) UNIQUE NOT NULL,
    criteria_id BIGINT UNSIGNED NOT NULL,
    evaluable_type VARCHAR(255) NULL,
    evaluable_id BIGINT UNSIGNED NULL,
    passed BOOLEAN NOT NULL,
    score DECIMAL(8,2) DEFAULT 0 NOT NULL,
    failed_rules JSON NULL,
    rule_results JSON NULL,
    decision VARCHAR(255) NULL,
    context JSON NULL,
    meta JSON NULL,
    evaluated_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (criteria_id) REFERENCES eligify_criteria(id),

    INDEX idx_uuid (uuid),
    INDEX idx_evaluable (evaluable_type, evaluable_id),
    INDEX idx_passed_evaluated (passed, evaluated_at),
    INDEX idx_criteria_passed (criteria_id, passed)
);
```

**Columns:**

- `id` - Primary key
- `uuid` - Unique identifier
- `criteria_id` - Foreign key to criteria
- `evaluable_type` - Polymorphic type (User, Application, etc.)
- `evaluable_id` - Polymorphic ID
- `passed` - Boolean evaluation result
- `score` - Calculated score (0-100)
- `failed_rules` - JSON array of failed rule IDs
- `rule_results` - Detailed per-rule results
- `decision` - Human-readable decision
- `context` - Input data used for evaluation
- `meta` - Additional metadata
- `evaluated_at` - When evaluation occurred

**Sample Data:**

```sql
INSERT INTO eligify_evaluations (
    uuid, criteria_id, evaluable_type, evaluable_id,
    passed, score, failed_rules, decision, context, evaluated_at, created_at, updated_at
)
VALUES (
    UUID(),
    1,
    'App\\Models\\User',
    101,
    true,
    85.50,
    NULL,
    'Approved',
    '{"credit_score":720,"income":55000,"employment_status":"employed"}',
    NOW(),
    NOW(),
    NOW()
);
```

### Audit Logs Table

Comprehensive audit trail for compliance.

```sql
CREATE TABLE eligify_audit_logs (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    uuid CHAR(36) UNIQUE NOT NULL,
    event VARCHAR(255) NOT NULL,
    auditable_type VARCHAR(255) NOT NULL,
    auditable_id BIGINT UNSIGNED NOT NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    context JSON NULL,
    user_type VARCHAR(255) NULL,
    user_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    meta JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_uuid (uuid),
    INDEX idx_auditable (auditable_type, auditable_id),
    INDEX idx_event_created (event, created_at),
    INDEX idx_user (user_type, user_id)
);
```

**Columns:**

- `id` - Primary key
- `uuid` - Unique identifier
- `event` - Event type (evaluation_completed, rule_created, etc.)
- `auditable_type` - Polymorphic type
- `auditable_id` - Polymorphic ID
- `old_values` - State before change
- `new_values` - State after change
- `context` - Additional context
- `user_type`, `user_id` - User who triggered action
- `ip_address` - Client IP address
- `user_agent` - Browser/client info
- `meta` - Additional metadata

**Sample Data:**

```sql
INSERT INTO eligify_audit_logs (
    uuid, event, auditable_type, auditable_id,
    new_values, user_type, user_id, ip_address, created_at, updated_at
)
VALUES (
    UUID(),
    'evaluation_completed',
    'CleaniqueCoders\\Eligify\\Models\\Evaluation',
    1,
    '{"passed":true,"score":85.5}',
    'App\\Models\\User',
    1,
    '192.168.1.100',
    NOW(),
    NOW()
);
```

## Indexes and Performance

### Primary Indexes

All tables include:

- **Primary key** on `id`
- **Unique index** on `uuid`

### Composite Indexes

Optimized for common queries:

**Criteria:**

```sql
INDEX idx_name_active (name, is_active)
```

**Rules:**

```sql
INDEX idx_criteria_active (criteria_id, is_active)
INDEX idx_field_operator (field, operator)
```

**Evaluations:**

```sql
INDEX idx_evaluable (evaluable_type, evaluable_id)
INDEX idx_passed_evaluated (passed, evaluated_at)
INDEX idx_criteria_passed (criteria_id, passed)
```

**Audit Logs:**

```sql
INDEX idx_auditable (auditable_type, auditable_id)
INDEX idx_event_created (event, created_at)
INDEX idx_user (user_type, user_id)
```

### Adding Custom Indexes

```php
// In a new migration
Schema::table('eligify_evaluations', function (Blueprint $table) {
    $table->index('score');  // Index scores for queries
    $table->index('decision');  // Index decisions
});
```

## Customizing Migrations

### Change Table Prefix

```php
// In config/eligify.php
'database' => [
    'prefix' => 'custom_prefix_',
],

// Then in migration
Schema::create(config('eligify.database.prefix') . 'criteria', function (Blueprint $table) {
    // ...
});
```

### Add Custom Columns

```php
// Create new migration
php artisan make:migration add_custom_fields_to_eligify_criteria

// In migration
public function up()
{
    Schema::table('eligify_criteria', function (Blueprint $table) {
        $table->string('category')->nullable();
        $table->integer('version')->default(1);
        $table->timestamp('expires_at')->nullable();
    });
}
```

### Change Database Connection

```php
// In config/eligify.php
'database' => [
    'connection' => 'secondary_db',
],

// In migration
public function up()
{
    $connection = config('eligify.database.connection');

    Schema::connection($connection)->create('eligify_criteria', function (Blueprint $table) {
        // ...
    });
}
```

### Enable Soft Deletes

```php
// In migration
Schema::table('eligify_criteria', function (Blueprint $table) {
    $table->softDeletes();
});

// In Model
use Illuminate\Database\Eloquent\SoftDeletes;

class Criteria extends Model
{
    use SoftDeletes;
}
```

## Multi-Tenant Setup

### Separate Databases

```php
// In config/eligify.php
'database' => [
    'connection' => fn() => auth()->user()->tenant->database_connection,
],
```

### Tenant Column

```php
// Add tenant_id to all tables
Schema::table('eligify_criteria', function (Blueprint $table) {
    $table->unsignedBigInteger('tenant_id')->after('id');
    $table->foreign('tenant_id')->references('id')->on('tenants');

    // Add composite index
    $table->index(['tenant_id', 'slug']);
});

// Global scope in model
class Criteria extends Model
{
    protected static function booted()
    {
        static::addGlobalScope('tenant', function ($query) {
            $query->where('tenant_id', auth()->user()->tenant_id);
        });
    }
}
```

### Separate Schemas (PostgreSQL)

```php
// In migration
public function up()
{
    $schema = auth()->user()->tenant->database_schema;

    DB::statement("CREATE SCHEMA IF NOT EXISTS {$schema}");

    Schema::create("{$schema}.eligify_criteria", function (Blueprint $table) {
        // ...
    });
}
```

## Data Migration

### Migrating from Legacy System

```php
// Create migration command
php artisan make:command MigrateLegacyEligibilityData

// In command
public function handle()
{
    DB::table('old_criteria')->chunk(100, function ($oldCriteria) {
        foreach ($oldCriteria as $old) {
            $criteria = Criteria::create([
                'name' => $old->name,
                'description' => $old->description,
                'is_active' => $old->status === 'active',
                'created_at' => $old->created_at,
            ]);

            // Migrate rules
            foreach ($old->rules as $oldRule) {
                $criteria->rules()->create([
                    'field' => $oldRule->field_name,
                    'operator' => $this->mapOperator($oldRule->operator),
                    'value' => $oldRule->value,
                    'weight' => $oldRule->priority,
                ]);
            }
        }
    });

    $this->info('Migration complete!');
}
```

### Seeding Test Data

```php
// database/seeders/EligifySeeder.php
class EligifySeeder extends Seeder
{
    public function run()
    {
        $criteria = Criteria::factory()
            ->count(10)
            ->create();

        $criteria->each(function ($c) {
            Rule::factory()
                ->count(5)
                ->for($c)
                ->create();

            Evaluation::factory()
                ->count(20)
                ->for($c)
                ->create();
        });
    }
}

// Run seeder
php artisan db:seed --class=EligifySeeder
```

### Exporting Data

```php
// Export to JSON
php artisan eligify:export criteria.json

// Export to CSV
php artisan eligify:export evaluations.csv --type=evaluations --format=csv

// Export specific criteria
php artisan eligify:export loan-data.json --criteria=loan-approval
```

### Importing Data

```php
// Import from JSON
php artisan eligify:import criteria.json

// Import with validation
php artisan eligify:import data.json --validate

// Dry run
php artisan eligify:import data.json --dry-run
```

## Database Maintenance

### Optimize Tables

```bash
# Optimize all Eligify tables
php artisan db:optimize eligify_criteria eligify_rules eligify_evaluations eligify_audit_logs
```

### Clean Old Data

```bash
# Clean evaluations older than 1 year
php artisan eligify:cleanup-evaluations --days=365

# Clean audit logs
php artisan eligify:cleanup-audit --days=730
```

### Backup

```bash
# Backup Eligify tables
mysqldump -u root -p database_name \
    eligify_criteria \
    eligify_rules \
    eligify_evaluations \
    eligify_audit_logs \
    > eligify_backup.sql

# Restore
mysql -u root -p database_name < eligify_backup.sql
```

### Vacuum (PostgreSQL)

```sql
VACUUM ANALYZE eligify_criteria;
VACUUM ANALYZE eligify_rules;
VACUUM ANALYZE eligify_evaluations;
VACUUM ANALYZE eligify_audit_logs;
```

## Troubleshooting

### Migration Fails

```bash
# Check migration status
php artisan migrate:status

# Reset and re-run
php artisan migrate:reset
php artisan migrate

# Force migration (production)
php artisan migrate --force
```

### Foreign Key Constraints

```bash
# Disable foreign key checks (MySQL)
SET FOREIGN_KEY_CHECKS=0;

# Re-enable
SET FOREIGN_KEY_CHECKS=1;
```

### Table Already Exists

```bash
# Drop tables manually
php artisan tinker
>>> Schema::dropIfExists('eligify_audit_logs');
>>> Schema::dropIfExists('eligify_evaluations');
>>> Schema::dropIfExists('eligify_rules');
>>> Schema::dropIfExists('eligify_criteria');

# Re-run migration
php artisan migrate
```

## Next Steps

- [Configuration Guide](configuration.md)
- [Usage Guide](usage-guide.md)
- [Best Practices](best-practices.md)
