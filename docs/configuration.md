# Configuration Guide

This guide explains all configuration options available in Eligify.

## Configuration File

After publishing the config file:

```bash
php artisan vendor:publish --tag="eligify-config"
```

You'll find it at `config/eligify.php`.

## Configuration Sections

### Scoring Configuration

Controls how eligibility scores are calculated:

```php
'scoring' => [
    // Default minimum score required to pass (0-100)
    'pass_threshold' => 65,

    // Maximum possible score
    'max_score' => 100,

    // Minimum possible score
    'min_score' => 0,

    // Default scoring method
    'method' => ScoringMethod::WEIGHTED->value,

    // Penalty subtracted for each failed rule
    'failure_penalty' => 5,

    // Bonus added for exceptional performance
    'excellence_bonus' => 10,
],
```

**Usage Example:**

```php
// Override per criteria
$criteria = Eligify::criteria('strict_approval')
    ->passThreshold(85)  // Overrides config default
    ->scoringMethod(ScoringMethod::PASS_FAIL)
    ->save();
```

### Scoring Methods

Available scoring methods:

#### WEIGHTED (Default)

Calculates weighted average based on rule weights:

```php
'method' => ScoringMethod::WEIGHTED->value,
```

**Formula:** `(sum of passed rule weights / total weights) * 100`

**Example:**

```php
Rule 1: weight 40, passed ✅
Rule 2: weight 30, passed ✅
Rule 3: weight 20, failed ❌
Rule 4: weight 10, passed ✅

Score = ((40 + 30 + 10) / 100) * 100 = 80
```

#### PASS_FAIL

Binary scoring - either 100 or 0:

```php
'method' => ScoringMethod::PASS_FAIL->value,
```

- **100** if all rules pass
- **0** if any rule fails

**Use case:** Critical compliance where all rules must pass.

#### SUM

Sum of weights for passed rules:

```php
'method' => ScoringMethod::SUM->value,
```

**Formula:** `sum of passed rule weights`

**Example:**

```php
Rule 1: weight 8, passed ✅
Rule 2: weight 6, passed ✅
Rule 3: weight 4, failed ❌

Score = 8 + 6 = 14
```

#### AVERAGE

Simple average - all rules have equal weight:

```php
'method' => ScoringMethod::AVERAGE->value,
```

**Formula:** `(passed rules / total rules) * 100`

#### PERCENTAGE

Percentage of passed rules:

```php
'method' => ScoringMethod::PERCENTAGE->value,
```

**Formula:** `(passed rules / total rules) * 100`

### Operators Configuration

Defines all available comparison operators:

```php
'operators' => [
    RuleOperator::EQUAL->value => [
        'name' => 'Equal',
        'description' => 'Field value must equal the specified value',
        'types' => ['numeric', 'string', 'boolean'],
        'example' => "addRule('status', '==', 'active')",
    ],
    // ... more operators
],
```

Each operator includes:

- **name** - Display name
- **description** - What it does
- **types** - Compatible field types
- **example** - Usage example

### Field Types Configuration

Defines supported data types:

```php
'field_types' => [
    FieldType::NUMERIC->value => [
        'label' => 'Numeric',
        'description' => 'Floating point numbers',
        'validation' => 'numeric',
        'cast' => 'float',
        'operators' => ['==', '!=', '>', '>=', '<', '<=', 'between', 'in'],
    ],
    FieldType::INTEGER->value => [
        'label' => 'Integer',
        'description' => 'Whole numbers',
        'validation' => 'integer',
        'cast' => 'integer',
        'operators' => ['==', '!=', '>', '>=', '<', '<=', 'between', 'in'],
    ],
    // ... more types
],
```

**Available Types:**

- `numeric` - Floating point numbers
- `integer` - Whole numbers
- `string` - Text values
- `boolean` - True/false
- `date` - Date/time values
- `array` - Lists/arrays

### Audit Configuration

Controls audit logging:

```php
'audit' => [
    // Enable/disable audit logging
    'enabled' => true,

    // Events to log
    'events' => [
        'evaluation_completed',
        'rule_created',
        'rule_modified',
        'rule_deleted',
        'criteria_created',
        'criteria_activated',
        'criteria_deactivated',
        'criteria_deleted',
    ],

    // Automatically clean up old logs
    'auto_cleanup' => true,

    // How long to keep logs (days)
    'retention_days' => 365,

    // Cleanup frequency
    'cleanup_schedule' => 'daily',

    // Store IP addresses
    'log_ip_address' => true,

    // Store user agent strings
    'log_user_agent' => true,
],
```

**Disabling Audit Logging:**

```php
'audit' => [
    'enabled' => false,
],
```

**Custom Retention:**

```php
'audit' => [
    'retention_days' => 90,  // Keep for 90 days
    'cleanup_schedule' => 'weekly',  // Clean weekly
],
```

### Performance Configuration

Optimize package performance:

```php
'performance' => [
    // Enable query optimization
    'optimize_queries' => true,

    // Batch processing size
    'batch_size' => 100,

    // Pre-compile rules for faster evaluation
    'compile_rules' => true,

    // Compiled rule cache TTL (minutes)
    'compilation_cache_ttl' => 1440, // 24 hours

    // Cache driver (null = default)
    'cache_driver' => null,

    // Cache prefix
    'cache_prefix' => 'eligify',
],
```

**Production Settings:**

```php
'performance' => [
    'optimize_queries' => true,
    'compile_rules' => true,
    'compilation_cache_ttl' => 2880, // 48 hours
    'batch_size' => 500,
],
```

**Development Settings:**

```php
'performance' => [
    'optimize_queries' => false,
    'compile_rules' => false,
    'compilation_cache_ttl' => 60, // 1 hour
    'batch_size' => 50,
],
```

### Workflow Configuration

Control workflow behavior:

```php
'workflow' => [
    // Maximum workflow steps before timeout
    'max_steps' => 50,

    // Workflow timeout (seconds)
    'timeout' => 30,

    // Retry failed workflows
    'retry_on_failure' => false,

    // Number of retry attempts
    'max_retries' => 3,

    // Fail entire evaluation if callback throws
    'fail_on_callback_error' => true,

    // Log callback errors
    'log_callback_errors' => true,

    // Async workflow queue
    'queue' => 'default',

    // Async workflow connection
    'connection' => null,
],
```

**Async Workflow:**

```php
'workflow' => [
    'queue' => 'eligibility',
    'connection' => 'redis',
    'retry_on_failure' => true,
    'max_retries' => 3,
],
```

### Validation Configuration

Control validation behavior:

```php
'validation' => [
    // Validate rule operators
    'validate_operators' => true,

    // Validate field types
    'validate_field_types' => true,

    // Strict type checking
    'strict_types' => true,

    // Allow custom operators
    'allow_custom_operators' => false,
],
```

### Notification Configuration

Configure notifications:

```php
'notifications' => [
    // Enable notifications
    'enabled' => true,

    // Notification channels
    'channels' => ['mail', 'database'],

    // Notification queue
    'queue' => 'default',

    // Events that trigger notifications
    'events' => [
        'evaluation_completed' => true,
        'criteria_created' => false,
    ],
],
```

### Database Configuration

Database-related settings:

```php
'database' => [
    // Table name prefix
    'prefix' => 'eligify_',

    // Connection (null = default)
    'connection' => null,

    // Use UUIDs
    'use_uuid' => true,

    // Soft deletes
    'soft_deletes' => false,
],
```

### Presets Configuration

Pre-configured criteria templates:

```php
'presets' => [
    'loan_approval' => [
        'name' => 'Loan Approval',
        'description' => 'Standard loan approval criteria',
        'pass_threshold' => 70,
        'rules' => [
            [
                'field' => 'credit_score',
                'operator' => '>=',
                'value' => 650,
                'weight' => 8,
            ],
            [
                'field' => 'income',
                'operator' => '>=',
                'value' => 30000,
                'weight' => 7,
            ],
            // ... more rules
        ],
    ],

    'scholarship_eligibility' => [
        'name' => 'Scholarship Eligibility',
        'description' => 'Merit-based scholarship qualification',
        'pass_threshold' => 75,
        'rules' => [
            [
                'field' => 'gpa',
                'operator' => '>=',
                'value' => 3.5,
                'weight' => 10,
            ],
            // ... more rules
        ],
    ],
],
```

**Using Presets:**

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

// Load a preset
$criteria = Eligify::fromPreset('loan_approval');

// Customize after loading
$criteria->addRule('custom_field', '>', 100)
         ->passThreshold(80)
         ->save();
```

## Environment-Specific Configuration

### Production

```php
// config/eligify.php
return [
    'scoring' => [
        'pass_threshold' => env('ELIGIFY_PASS_THRESHOLD', 75),
    ],

    'audit' => [
        'enabled' => env('ELIGIFY_AUDIT_ENABLED', true),
        'retention_days' => env('ELIGIFY_AUDIT_RETENTION', 365),
    ],

    'performance' => [
        'compile_rules' => true,
        'compilation_cache_ttl' => 2880,
        'batch_size' => 500,
    ],

    'workflow' => [
        'fail_on_callback_error' => true,
        'log_callback_errors' => true,
    ],
];
```

**.env:**

```bash
ELIGIFY_PASS_THRESHOLD=80
ELIGIFY_AUDIT_ENABLED=true
ELIGIFY_AUDIT_RETENTION=730
```

### Development

```php
'performance' => [
    'compile_rules' => false,
    'compilation_cache_ttl' => 60,
    'batch_size' => 50,
],

'audit' => [
    'enabled' => true,
    'retention_days' => 30,
],

'workflow' => [
    'fail_on_callback_error' => false,
    'log_callback_errors' => true,
],
```

### Testing

```php
'performance' => [
    'compile_rules' => false,
    'optimize_queries' => false,
],

'audit' => [
    'enabled' => false,
],

'workflow' => [
    'timeout' => 10,
    'fail_on_callback_error' => true,
],
```

## Custom Configuration

### Adding Custom Operators

```php
// config/eligify.php
'operators' => [
    // ... existing operators

    'divisible_by' => [
        'name' => 'Divisible By',
        'description' => 'Check if field is divisible by value',
        'types' => ['integer', 'numeric'],
        'example' => "addRule('quantity', 'divisible_by', 5)",
    ],
],
```

**Implementing Custom Operator:**

```php
namespace App\Rules;

use CleaniqueCoders\Eligify\Engine\RuleEngine;

class CustomRuleEngine extends RuleEngine
{
    protected function evaluateDivisibleBy($fieldValue, $expectedValue): bool
    {
        return $fieldValue % $expectedValue === 0;
    }
}

// Bind in AppServiceProvider
app()->bind(RuleEngine::class, CustomRuleEngine::class);
```

### Custom Scoring Method

```php
namespace App\Scoring;

use CleaniqueCoders\Eligify\Engine\RuleEngine;

class CustomScoringEngine extends RuleEngine
{
    protected function calculateScore(array $ruleResults): int
    {
        // Custom logic
        $baseScore = parent::calculateScore($ruleResults);

        // Add bonus for consecutive passes
        $consecutivePasses = 0;
        foreach ($ruleResults as $result) {
            if ($result['passed']) {
                $consecutivePasses++;
            } else {
                $consecutivePasses = 0;
            }
        }

        $bonus = floor($consecutivePasses / 3) * 5;

        return min($baseScore + $bonus, 100);
    }
}
```

## Configuration Tips

### 1. Start Conservative

```php
'scoring' => [
    'pass_threshold' => 75,  // Start higher
    'failure_penalty' => 10, // Be strict
],
```

Adjust based on evaluation data.

### 2. Enable Audit in Production

```php
'audit' => [
    'enabled' => true,
    'retention_days' => 730,  // 2 years for compliance
],
```

### 3. Optimize for Scale

```php
'performance' => [
    'optimize_queries' => true,
    'compile_rules' => true,
    'batch_size' => 1000,
    'compilation_cache_ttl' => 10080, // 1 week
],
```

### 4. Use Environment Variables

```php
'scoring' => [
    'pass_threshold' => env('ELIGIFY_PASS_THRESHOLD', 65),
],

'audit' => [
    'enabled' => env('ELIGIFY_AUDIT_ENABLED', true),
],
```

### 5. Separate Presets by Environment

```php
'presets' => env('APP_ENV') === 'production'
    ? require __DIR__.'/eligify-presets-production.php'
    : require __DIR__.'/eligify-presets-development.php',
```

## Troubleshooting Configuration

### Issue: Changes not taking effect

**Solution:** Clear config cache

```bash
php artisan config:clear
php artisan config:cache
```

### Issue: Performance problems

**Solution:** Enable optimization

```php
'performance' => [
    'optimize_queries' => true,
    'compile_rules' => true,
    'compilation_cache_ttl' => 2880,
],
```

### Issue: Too many audit logs

**Solution:** Reduce retention and filter events

```php
'audit' => [
    'retention_days' => 90,
    'events' => [
        'evaluation_completed',  // Keep only critical events
    ],
],
```

## Next Steps

- [Usage Guide](usage-guide.md)
- [Advanced Features](advanced-features.md)
- [CLI Commands](cli-commands.md)
