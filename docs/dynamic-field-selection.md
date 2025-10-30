# Dynamic Field Selection in Rule Editor

This guide explains how to use the enhanced RuleEditor component that provides dynamic field selection based on model mapping classes.

## Overview

The RuleEditor now supports two ways to select fields for your rules:

1. **Mapping-Based Selection** - Choose a model mapping first, then select from its available fields
2. **Manual Input** - Type the field name directly (for advanced users or custom fields)

This enhancement provides:

- **Field Discovery** - See all available fields from your model mappings
- **Type Information** - Automatic field type detection
- **Descriptions** - Helpful descriptions for each field
- **Category Grouping** - Fields organized by attributes, computed fields, and relationships
- **Validation** - Operators automatically filtered based on field type

### Important Note

**Only fields explicitly defined in your mapping will appear in the dropdown.** You must add fields to one of these arrays:

- `$fieldMappings` - For model attributes (including direct inclusions)
- `$computedFields` - For calculated values
- `$relationshipMappings` - For related model fields

Example: To include `name` and `email` directly:

```php
protected array $fieldMappings = [
    'name' => 'name',    // Include unchanged
    'email' => 'email',  // Include unchanged
];
```

## Quick Start

### Creating a Rule with Mapping Selection

1. **Select a Mapping**: Choose from available model mappings (e.g., "User")
2. **Select a Field**: Pick from categorized fields with descriptions
3. **Configure Rule**: Operator and value input automatically adapt to field type
4. **Save**: Rule is created with correct types and metadata

### Example Flow

```php
// In your view
Route: /eligify/criteria/{criteriaId}/rules/create

Step 1: Select Mapping
└─ "User (App\Models\User)"
   Description: Standard mapping for User models...

Step 2: Select Field
├─ Model Attributes
│  ├─ email_verified_timestamp (date) - When the email was verified
│  └─ registration_date (date) - Account creation date
├─ Computed Fields
│  └─ is_verified (boolean) - Whether the user has verified their email
└─ Relationships
   └─ ... (if any)

Step 3: Configure Rule
├─ Operator: Automatically filtered for boolean type
└─ Value: True/False selection
```

## Creating Model Mappings

### Basic Mapping

```php
<?php

namespace App\Mappings;

use CleaniqueCoders\Eligify\Data\Mappings\AbstractModelMapping;

class CustomerMapping extends AbstractModelMapping
{
    protected array $fieldDescriptions = [
        'account_balance' => 'Current account balance in default currency',
        'credit_score' => 'Credit score from 300-850',
        'is_premium' => 'Whether customer has premium membership',
        'total_purchases' => 'Total number of purchases made',
    ];

    protected array $fieldTypes = [
        'account_balance' => 'numeric',
        'credit_score' => 'integer',
        'is_premium' => 'boolean',
        'total_purchases' => 'integer',
    ];

    public function getModelClass(): string
    {
        return \App\Models\Customer::class;
    }

    public function getName(): string
    {
        return 'Customer Account';
    }

    public function getDescription(): string
    {
        return 'Mapping for customer accounts including financial data and purchase history';
    }

    public function __construct()
    {
        $this->computedFields = [
            'is_premium' => fn($model) => $model->subscription_tier === 'premium',
            'total_purchases' => fn($model) => $model->orders()->count(),
        ];
    }
}
```

### Mapping with Relationships

```php
protected array $relationshipMappings = [
    'subscription' => [
        'tier' => 'subscription_tier',
        'expires_at' => 'subscription_expires',
    ],
];

protected array $fieldDescriptions = [
    'subscription_tier' => 'Current subscription level (basic/premium/enterprise)',
    'subscription_expires' => 'When the subscription expires',
];

protected array $fieldTypes = [
    'subscription_tier' => 'string',
    'subscription_expires' => 'date',
];
```

### Understanding Field Discovery

The `getAvailableFields()` method discovers fields from three sources:

1. **Field Mappings** (`$fieldMappings`)
   - Maps original model attributes to renamed fields
   - Example: `'created_at' => 'registration_date'`
   - Category: `attribute`

2. **Computed Fields** (`$computedFields`)
   - Dynamically calculated values using closures
   - Example: `'is_verified' => fn($model) => !is_null($model->email_verified_at)`
   - Category: `computed`

3. **Relationship Mappings** (`$relationshipMappings`)
   - Fields from related models
   - Example: `'subscription' => ['tier' => 'subscription_tier']`
   - Category: `relationship`

**Important:** Only fields defined in these arrays will appear in the field selection dropdown. If you want to include a model attribute directly (like `name` or `email`), you must add it to `$fieldMappings`:

```php
protected array $fieldMappings = [
    'name' => 'name',        // Include as-is
    'email' => 'email',      // Include as-is
    'created_at' => 'registration_date',  // Rename
];
```

### Mapping Auto-Discovery

Mappings are automatically discovered from:

1. **Config File** - `config/eligify.php`:
   ```php
   'model_mappings' => [
       \App\Models\User::class => \App\Mappings\UserMapping::class,
       \App\Models\Customer::class => \App\Mappings\CustomerMapping::class,
   ],
   ```

2. **Package Directory** - `src/Mappings/*.php`
3. **App Directory** - `app/Mappings/*.php`

### Real-World Example: UserModelMapping

Here's the actual implementation included in the package:

```php
class UserModelMapping extends AbstractModelMapping
{
    public function getModelClass(): string
    {
        return 'App\Models\User';
    }

    // Only mapping renamed fields
    protected array $fieldMappings = [
        'email_verified_at' => 'email_verified_timestamp',
        'created_at' => 'registration_date',
    ];

    // Computed field
    protected array $computedFields = [
        'is_verified' => null,
    ];

    public function __construct()
    {
        $this->computedFields = [
            'is_verified' => fn ($model) => !is_null($model->email_verified_at ?? null),
        ];

        // Describe all available fields
        $this->fieldDescriptions = [
            'email_verified_timestamp' => 'When the email was verified (nullable)',
            'registration_date' => 'Account creation date',
            'is_verified' => 'Whether the user has verified their email',
        ];

        // Define field types
        $this->fieldTypes = [
            'email_verified_timestamp' => 'date',
            'registration_date' => 'date',
            'is_verified' => 'boolean',
        ];
    }

    public function getName(): string
    {
        return 'User';
    }

    public function getDescription(): string
    {
        return 'Standard mapping for User models including profile data, verification status, and registration information';
    }
}
```

**Result:** This mapping exposes 3 fields in the UI:
- `email_verified_timestamp` (attribute - renamed from `email_verified_at`)
- `registration_date` (attribute - renamed from `created_at`)
- `is_verified` (computed field)

## Using MappingRegistry

The `MappingRegistry` service provides programmatic access to mappings:

### Get All Mappings

```php
use CleaniqueCoders\Eligify\Support\MappingRegistry;

// Get all with metadata
$mappings = MappingRegistry::all();
// [
//     'App\Mappings\UserMapping' => [
//         'class' => 'App\Mappings\UserMapping',
//         'name' => 'User',
//         'description' => 'Standard mapping for User models...',
//         'model' => 'App\Models\User',
//     ],
//     ...
// ]

// Get just class names
$classes = MappingRegistry::classes();
// ['App\Mappings\UserMapping', 'App\Mappings\CustomerMapping', ...]
```

### Get Mapping Instance

```php
// Get a specific mapping
$mapping = MappingRegistry::get(UserMapping::class);

$name = $mapping->getName(); // "User"
$description = $mapping->getDescription();
$modelClass = $mapping->getModelClass(); // "App\Models\User"
```

### Get Fields from Mapping

```php
// Get all fields with metadata
$fields = MappingRegistry::getFields(UserMapping::class);
// [
//     'email_verified_timestamp' => [
//         'original' => 'email_verified_at',
//         'type' => 'date',
//         'description' => 'When the email was verified (nullable)',
//         'category' => 'attribute',
//     ],
//     'registration_date' => [
//         'original' => 'created_at',
//         'type' => 'date',
//         'description' => 'Account creation date',
//         'category' => 'attribute',
//     ],
//     'is_verified' => [
//         'type' => 'boolean',
//         'description' => 'Whether the user has verified their email',
//         'category' => 'computed',
//     ],
// ]

// Get field metadata
$meta = MappingRegistry::getMeta(UserMapping::class);
// [
//     'class' => 'App\Mappings\UserMapping',
//     'name' => 'User',
//     'description' => '...',
//     'model' => 'App\Models\User',
//     'fields_count' => 3,
// ]
```

### Check Mapping Availability

```php
// Check if mapping exists
if (MappingRegistry::has(UserMapping::class)) {
    // Mapping is registered
}

// Group by model class
$byModel = MappingRegistry::byModel();
// [
//     'App\Models\User' => [...mappings for User],
//     'App\Models\Customer' => [...mappings for Customer],
// ]
```

### Cache Management

```php
// Mappings are cached automatically for performance
$mappings1 = MappingRegistry::all(); // Queries filesystem
$mappings2 = MappingRegistry::all(); // Returns cached result

// Clear cache to force rediscovery
MappingRegistry::clearCache();
$mappings3 = MappingRegistry::all(); // Queries filesystem again
```

## RuleEditor Livewire Component

### Properties

```php
public ?string $selectedMapping = null;  // Selected mapping class
public bool $useManualInput = false;     // Toggle manual input mode
public string $field = '';               // Field name
public string $operator = '==';          // Comparison operator
public ?string $fieldType = null;        // Field type (auto-populated)
public $value = '';                      // Rule value
```

### Methods

```php
// Get available mappings
$this->mappingClasses; // Computed property

// Get fields for selected mapping
$this->availableFields; // Computed property

// Toggle between mapping and manual input
wire:click="toggleManualInput"

// Handles mapping selection change
updatedSelectedMapping()

// Handles field selection change (auto-populates field type)
updatedField()
```

### Blade Template Usage

```blade
<livewire:eligify-rule-editor
    :mode="'create'"
    :criteriaId="$criteriaId"
/>

<!-- Or for editing -->
<livewire:eligify-rule-editor
    :mode="'edit'"
    :criteriaId="$criteriaId"
    :ruleId="$ruleId"
/>
```

## Field Types & Validation

### Supported Field Types

| Type | Description | Example Values | Operators |
|------|-------------|----------------|-----------|
| `string` | Text values | "active", "pending" | ==, !=, in, not_in, contains, starts_with, ends_with |
| `integer` | Whole numbers | 100, -50, 0 | ==, !=, >, >=, <, <=, between, in |
| `numeric` | Decimals | 99.99, 3.14 | ==, !=, >, >=, <, <=, between |
| `boolean` | True/false | true, false, 1, 0 | ==, != |
| `date` | Date/time | "2024-01-01", "2024-12-31 23:59:59" | ==, !=, >, >=, <, <=, between |
| `array` | Lists | ["a","b","c"] | in, not_in, contains, empty, not_empty |

### Type Auto-Population

When you select a field from a mapping:
1. Field type is automatically populated
2. Available operators are filtered
3. Value input adapts (number, date, checkbox, etc.)
4. Placeholder text provides examples
5. Help text guides input format

## UI Components

### Mapping Selection Dropdown

Shows all available mappings with their names and target models:

```blade
<select wire:model.live="selectedMapping">
    <option value="">-- Select a mapping or use manual input --</option>
    <option value="App\Mappings\UserMapping">User (App\Models\User)</option>
    <option value="App\Mappings\CustomerMapping">Customer Account (App\Models\Customer)</option>
</select>
```

### Field Selection Dropdown

Groups fields by category with type information:

```blade
<select wire:model.live="field">
    <optgroup label="Model Attributes">
        <option value="email_verified_timestamp">email_verified_timestamp (date)</option>
        <option value="registration_date">registration_date (date)</option>
    </optgroup>
    <optgroup label="Computed Fields">
        <option value="is_verified">is_verified (boolean)</option>
    </optgroup>
    <optgroup label="Relationships">
        <option value="subscription_tier">subscription_tier (string)</option>
    </optgroup>
</select>
```

### Manual Input Toggle

Switch between mapping-based and manual input:

```blade
<button wire:click="toggleManualInput">
    {{ $useManualInput ? '✓ Manual Input' : 'Use Manual Input' }}
</button>
```

## Best Practices

### 1. Always Add Descriptions

```php
protected array $fieldDescriptions = [
    'credit_score' => 'Credit score from 300-850 (FICO)', // Good
    'amount' => 'Transaction amount',                      // Too vague
];
```

### 2. Be Specific with Types

```php
protected array $fieldTypes = [
    'age' => 'integer',    // Good - specific
    'price' => 'numeric',  // Good - allows decimals
    'status' => 'string',  // Good - not boolean
];
```

### 3. Organize Field Mappings

```php
// Group related fields together
protected array $fieldMappings = [
    // Personal Info
    'first_name' => 'first_name',
    'last_name' => 'last_name',
    'email' => 'email',

    // Financial Data
    'account_balance' => 'balance',
    'credit_limit' => 'limit',
];
```

### 4. Document Computed Fields

```php
protected array $computedFields = [
    'account_age_days' => null, // Documented below
];

protected array $fieldDescriptions = [
    'account_age_days' => 'Number of days since account creation',
];

public function __construct()
{
    $this->computedFields = [
        'account_age_days' => fn($model) =>
            now()->diffInDays($model->created_at),
    ];
}
```

### 5. Use Meaningful Mapping Names

```php
public function getName(): string
{
    return 'Premium Customer'; // Good - concise and specific
    // Not: 'Premium Customer Account Mapping' (too verbose)
}

public function getDescription(): string
{
    return 'Eligibility mapping for premium customers with advanced metrics including lifetime value, purchase frequency, and subscription status';
}
```

## Testing

### Test Your Mappings

```php
use CleaniqueCoders\Eligify\Support\MappingRegistry;
it('has all required fields in the mapping', function () {
    $fields = MappingRegistry::getFields(CustomerMapping::class);

    expect($fields)->toHaveKey('account_balance')
        ->and($fields)->toHaveKey('credit_score')
        ->and($fields['account_balance']['type'])->toBe('numeric');
});

it('has field descriptions', function () {
    $mapping = MappingRegistry::get(CustomerMapping::class);

    expect($mapping->getFieldDescription('account_balance'))->not->toBeNull()
        ->and(strtolower($mapping->getFieldDescription('account_balance')))->toContain('balance');
});
```

## Troubleshooting

### Mapping Not Appearing

1. **Check namespace**: Ensure mapping class uses correct namespace
2. **Check class name**: Must end in `Mapping` or match discovery pattern
3. **Check config**: Add to `config/eligify.php` if not auto-discovered
4. **Clear cache**: `MappingRegistry::clearCache()`

### Fields Not Showing

1. **Check field mappings**: Ensure fields are defined in `$fieldMappings` or `$computedFields`
2. **Check types**: Verify `$fieldTypes` array includes the field
3. **Check descriptions**: Add to `$fieldDescriptions` for better UX

### Type Mismatch Errors

1. **Verify field type**: Ensure type matches actual data (integer vs numeric)
2. **Check operator**: Some operators only work with certain types
3. **Test with example**: Use playground to test field extraction

## Advanced Usage

### Custom Validation in RuleEditor

```php
// In your custom RuleEditor extension
public function updatedField()
{
    parent::updatedField();

    // Add custom validation
    if ($this->field === 'sensitive_field') {
        $this->addError('field', 'This field requires admin approval');
    }
}
```

### Programmatic Rule Creation

```php
use CleaniqueCoders\Eligify\Support\MappingRegistry;
use CleaniqueCoders\Eligify\Models\Rule;

// Get field metadata
$fields = MappingRegistry::getFields(UserMapping::class);
$fieldMeta = $fields['credit_score'];

// Create rule with metadata
Rule::create([
    'criteria_id' => $criteriaId,
    'field' => 'credit_score',
    'operator' => '>=',
    'value' => 650,
    'weight' => 10,
    'meta' => [
        'field_type' => $fieldMeta['type'],
        'field_category' => $fieldMeta['category'],
        'description' => $fieldMeta['description'],
    ],
]);
```

## See Also

- [Model Mappings Guide](model-mappings.md) - Comprehensive mapping documentation
- [Model Data Extraction](model-data-extraction.md) - How data extraction works
- [Extractor Architecture](extractor-architecture.md) - Technical architecture details
- [Usage Guide](usage-guide.md) - General Eligify usage patterns
