# Configuration Presets

Pre-configured settings for common use cases.

## Overview

Eligify provides configuration presets that you can use as starting points for common scenarios. Presets include operator selections, scoring methods, and default rules.

## Available Presets

### Financial Services

Optimized for loan approvals, credit checks, and financial eligibility:

```php
// config/eligify.php
'preset' => 'financial',
```

**Includes:**

- Weighted scoring by default
- High threshold (80%)
- Financial-specific operators
- Audit logging enabled
- Extended retention (7 years)

**Configuration:**

```php
[
    'scoring' => [
        'default_method' => 'weighted',
        'passing_threshold' => 80,
    ],
    'operators' => [
        'greater_than',
        'less_than',
        'between',
        'in',
    ],
    'audit' => [
        'enabled' => true,
        'retention_days' => 2555, // 7 years
    ],
]
```

### Healthcare

For patient eligibility, insurance approval:

```php
'preset' => 'healthcare',
```

**Includes:**

- Pass/fail scoring
- Strict requirements
- HIPAA-compliant audit logging
- Longer retention period

### Education

For scholarships, admissions, program eligibility:

```php
'preset' => 'education',
```

**Includes:**

- Weighted scoring
- Moderate threshold (70%)
- Grade and achievement operators

### E-commerce

For discount eligibility, loyalty programs:

```php
'preset' => 'ecommerce',
```

**Includes:**

- Sum scoring
- Points-based rules
- Short retention period

### Government

For benefit programs, aid qualification:

```php
'preset' => 'government',
```

**Includes:**

- Pass/fail scoring
- Comprehensive audit trail
- Long retention
- Strict compliance

## Custom Presets

Create your own presets:

```php
// config/eligify-presets.php
return [
    'custom_preset' => [
        'scoring' => [
            'default_method' => 'weighted',
            'passing_threshold' => 75,
        ],
        'operators' => [
            'equals',
            'greater_than',
            'in',
        ],
        'audit' => [
            'enabled' => true,
            'retention_days' => 365,
        ],
        'ui' => [
            'enabled' => true,
            'theme' => 'light',
        ],
    ],
];
```

Use custom preset:

```php
// config/eligify.php
'preset' => 'custom_preset',
```

## Related Documentation

- [Configuration Reference](reference.md) - All options
- [Environment Variables](environment-variables.md) - ENV configuration
- [Operators](operators.md) - Available operators
