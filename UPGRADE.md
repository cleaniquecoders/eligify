# Upgrade Guide

## Upgrading to 1.7.0 from 1.6.x

### Impact: None (Backward Compatible)

This release introduces multi-driver storage and fixes a column truncation issue. **No changes are required for existing users.** The default storage driver is `database`, which uses the exact same Eloquent operations as before.

### Step 1: Update the Package

```bash
composer update cleaniquecoders/eligify
```

### Step 2: Run Migrations

A new migration changes the `user_agent` column in `eligify_audit_logs` from `VARCHAR(255)` to `TEXT`. This prevents truncation errors on SQL Server with long User-Agent strings.

```bash
php artisan migrate
```

### Step 3: Publish Updated Config (Optional)

If you've published the config file previously, you may want to merge the new `storage` block. You can either:

**Option A**: Re-publish and diff:

```bash
php artisan vendor:publish --tag="eligify-config" --force
```

**Option B**: Manually add the `storage` key to your `config/eligify.php`:

```php
'storage' => [
    'driver' => env('ELIGIFY_STORAGE_DRIVER', 'database'),

    'file' => [
        'disk' => env('ELIGIFY_STORAGE_DISK', 'local'),
        'path' => env('ELIGIFY_STORAGE_PATH', 'eligify'),
    ],

    's3' => [
        'disk' => env('ELIGIFY_STORAGE_S3_DISK', 's3'),
        'path' => env('ELIGIFY_STORAGE_S3_PATH', 'eligify'),
    ],

    'cache' => [
        'enabled' => env('ELIGIFY_STORAGE_CACHE_ENABLED', true),
        'ttl' => env('ELIGIFY_STORAGE_CACHE_TTL', 1440),
        'prefix' => 'eligify_storage',
    ],
],
```

> If you skip this step, Eligify defaults to database storage with no config changes needed.

### Using Multi-Driver Storage (Optional)

If you want to switch to file-based or S3 storage:

#### Migrating from Database to File Storage

1. Export your existing criteria to JSON files:

```bash
php artisan eligify:storage-export
```

This writes each criteria as a JSON file to `storage/app/eligify/{slug}.json`.

2. Switch the driver in your `.env`:

```env
ELIGIFY_STORAGE_DRIVER=file
```

3. Your application now reads criteria from files instead of the database. Files are cached on first access.

#### Migrating from Database to S3

1. Export criteria to S3:

```bash
php artisan eligify:storage-export --disk=s3 --path=eligify
```

2. Switch the driver:

```env
ELIGIFY_STORAGE_DRIVER=s3
ELIGIFY_STORAGE_S3_DISK=s3
ELIGIFY_STORAGE_S3_PATH=eligify
```

#### Migrating from File/S3 Back to Database

```bash
php artisan eligify:storage-import
```

Then switch back:

```env
ELIGIFY_STORAGE_DRIVER=database
```

### What Stays in the Database Regardless of Driver

No matter which storage driver you use, the following always use the database:

- **Evaluations** (`eligify_evaluations` table)
- **Audit logs** (`eligify_audit_logs` table)
- **Snapshots** (`eligify_snapshots` table)
- **Criteria versions** (`eligify_criteria_versions` table)

Only **criteria**, **rules**, and **rule groups** are affected by the storage driver.

---

## Upgrading to 1.6.0 from 1.5.x

### Impact: None

- Added Laravel 13 support
- Updated dev dependencies for PHPUnit 12 and Pest 4
- No code changes required

```bash
composer update cleaniquecoders/eligify
```

---

## Upgrading to 1.5.0 from 1.4.x

### Impact: Low

- New migrations for snapshots and criteria versions
- Run migrations after updating:

```bash
composer update cleaniquecoders/eligify
php artisan migrate
```
