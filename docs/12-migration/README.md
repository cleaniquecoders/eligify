# Migration & Upgrades

This section covers upgrading Eligify and migrating between versions.

## Overview

Guidance for safely upgrading Eligify versions and migrating data.

## Documentation in this Section

- **[Upgrade Guide](upgrade-guide.md)** - Version-to-version upgrade instructions
- **[Model Mapping Migration](model-mapping-migration.md)** - Migrating model mappers
- **[Breaking Changes](breaking-changes.md)** - API changes between versions
- **[API Stability](api-stability.md)** - Stability guarantees

## Version Support

| Version | PHP | Laravel | Status |
|---------|-----|---------|--------|
| 1.x | 8.4+ | 11.x, 12.x | Active |

## Upgrade Process

### 1. Review Breaking Changes

Check the [Breaking Changes](breaking-changes.md) document for your target version.

### 2. Update Composer

```bash
composer require cleaniquecoders/eligify:^2.0
```

### 3. Run Migrations

```bash
php artisan migrate
```

### 4. Update Configuration

```bash
# Backup current config
cp config/eligify.php config/eligify.php.backup

# Publish new config
php artisan vendor:publish --tag="eligify-config" --force
```

### 5. Test Thoroughly

```bash
composer test
```

## Common Migration Scenarios

### From 1.x to 2.x

Major changes:

- Model mapping patterns updated
- New snapshot system
- Caching layer redesigned
- UI components rewritten with Livewire 3

See [Upgrade Guide](upgrade-guide.md) for detailed instructions.

### Model Mapper Migration

If you're using custom model mappers:

```bash
# Generate new-style mappers
php artisan eligify:make:mapper UserMapper --from-legacy
```

See [Model Mapping Migration](model-mapping-migration.md) for details.

## Deprecation Policy

- Deprecated features receive warnings for 2 minor versions
- Breaking changes only in major versions
- Security fixes backported for 1 year

## Rolling Back

If you need to rollback:

```bash
# Rollback composer
composer require cleaniquecoders/eligify:^1.0

# Rollback migrations
php artisan migrate:rollback

# Restore config
cp config/eligify.php.backup config/eligify.php
```

## Related Sections

- [Configuration](../06-configuration/) - New configuration options
- [Testing](../09-testing/) - Testing after migration
- [Deployment](../10-deployment/) - Production migration strategy
