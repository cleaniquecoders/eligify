# Migration & Upgrades

This section covers upgrading Eligify and migrating between versions.

## Overview

Guidance for safely upgrading Eligify versions and migrating data.

## Upgrading to criteria attachments and classification fields

The release introducing criteria classification (`type`, `group`, `category`, `tags`) and the polymorphic pivot (`eligify_criteriables`) requires new migrations.

### Steps

```bash
php artisan vendor:publish --tag="eligify-migrations"
php artisan migrate
```

### Backfill (optional)

- You can backfill existing criteria with `type`, `group`, `category`, and `tags` values according to your domain. Example:

```php
DB::table('eligify_criteria')
    ->whereNull('type')
    ->update(['type' => 'policy']);
```

### Notes

- `tags` is stored as JSON; use `whereJsonContains('tags', 'beta')` for lookups.
- The pivot `eligify_criteriables` uses standard Laravel polymorphic keys: `criteriable_type`, `criteriable_id`.
- Consider adding extra pivot fields (e.g., `starts_at`, `ends_at`, `active`) in a follow-up migration if you need lifecycle control.

## Version Support

| Version | PHP | Laravel | Status |
|---------|-----|---------|--------|
| 1.x | 8.4+ | 11.x, 12.x | Active |

## Related Sections

- [Configuration](../06-configuration/) - New configuration options
- [Testing](../09-testing/) - Testing after migration
- [Deployment](../10-deployment/) - Production migration strategy
