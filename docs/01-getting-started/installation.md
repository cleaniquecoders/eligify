# Installation & Setup

This guide walks you through installing and setting up Eligify in your Laravel application.

## Requirements

- **PHP**: 8.4 or higher
- **Laravel**: 11.x or 12.x
- **Database**: MySQL 8.0+, PostgreSQL 12+, or SQLite 3.35+

## Installation Steps

### 1. Install via Composer

```bash
composer require cleaniquecoders/eligify
```

### 2. Publish Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag="eligify-config"
```

This creates `config/eligify.php` in your application.

### 3. Publish and Run Migrations

Publish the migrations:

```bash
php artisan vendor:publish --tag="eligify-migrations"
```

Run the migrations:

```bash
php artisan migrate
```

This creates the following tables:

- `eligify_criteria` - Stores eligibility criteria definitions
- `eligify_rules` - Stores individual rules within criteria
- `eligify_evaluations` - Evaluation results and audit trail
- `eligify_audit_logs` - Comprehensive audit trail for all events
- `eligify_criteriables` - Polymorphic pivot to attach criteria to any model

Note: Recent versions add classification fields on `eligify_criteria` (`type`, `group`, `category`, `tags`) to organize and query criteria easily.

### 4. Publish Assets (Optional)

If you plan to use the web UI:

```bash
php artisan vendor:publish --tag="eligify-views"
php artisan vendor:publish --tag="eligify-assets"
```

## Configuration

### Basic Configuration

Edit `config/eligify.php` to customize:

```php
return [
    // Default scoring method
    'scoring' => [
        'default_method' => 'weighted',
        'passing_threshold' => 70,
    ],

    // Available operators
    'operators' => [
        'equals' => '==',
        'not_equals' => '!=',
        'greater_than' => '>',
        'less_than' => '<',
        // ... more operators
    ],

    // Audit logging
    'audit' => [
        'enabled' => true,
        'retention_days' => 90,
    ],

    // UI settings
    'ui' => [
        'enabled' => true,
        'route_prefix' => 'eligify',
        'middleware' => ['web', 'auth'],
    ],
];
```

### Environment Variables

Add to your `.env` file:

```env
ELIGIFY_CACHE_ENABLED=true
ELIGIFY_CACHE_TTL=3600
ELIGIFY_AUDIT_ENABLED=true
ELIGIFY_UI_ENABLED=true
```

## Verification

Verify the installation:

```bash
php artisan eligify:install --verify
```

Test with a simple evaluation:

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

$result = Eligify::criteria('Test')
    ->addRule('age', '>=', 18)
    ->evaluate(['age' => 25]);

dump($result->passed()); // true
```

## Next Steps

- [Quick Start Guide](quick-start.md) - Create your first criteria
- [Usage Guide](usage-guide.md) - Learn the API
- [Core Concepts](core-concepts.md) - Understand the fundamentals
