# Model Mapping Complete Guide

**Complete guide to creating and using model mappings in Eligify, based on 4 proven patterns.**

## Table of Contents

- [Quick Start](#quick-start)
- [Pattern 1: Direct Field Reference](#pattern-1-direct-field-reference)
- [Pattern 1B: Spread Operator (Dynamic)](#pattern-1b-spread-operator-dynamic)
- [Pattern 2: Merge with Prefix](#pattern-2-merge-with-prefix)
- [Pattern 3: Computed Fields with Relationships](#pattern-3-computed-fields-with-relationships)
- [Pattern 4: Nested Multi-Level Relationships](#pattern-4-nested-multi-level-relationships)
- [Complete Working Example](#complete-working-example)
- [Best Practices](#best-practices)

---

## Quick Start

### Creating Your First Mapping

```bash
# Generate mapping class
php artisan eligify:make-mapping "App\Models\User"
```

### Basic Structure

```php
use CleaniqueCoders\Eligify\Data\Mappings\AbstractModelMapping;
use CleaniqueCoders\Eligify\Data\Extractor;

class UserMapping extends AbstractModelMapping
{
    protected ?string $prefix = 'user';

    protected array $fieldMappings = [
        'email' => 'email_address',  // DB column -> Output field
    ];

    public function getModelClass(): string
    {
        return User::class;
    }

    public function getName(): string
    {
        return 'User';
    }

    public function getDescription(): string
    {
        return 'User account data';
    }
}
```

### Register in Config

```php
// config/eligify.php
'model_extraction' => [
    'model_mappings' => [
        User::class => UserMapping::class,
    ],
],
```

---

## Pattern 1: Direct Field Reference

**Use Case:** Include specific fields from a related model

### Step 1: Define the Related Mapping

```php
// ProfileMapping.php
class ProfileMapping extends AbstractModelMapping
{
    protected ?string $prefix = 'profile';

    protected array $fieldMappings = [
        'bio' => 'biography',                    // DB -> Output
        'employment_status' => 'employed',       // DB -> Output
    ];

    public function getModelClass(): string
    {
        return Profile::class;
    }
}
```

### Step 2: Reference OUTPUT Fields in Parent Mapping

```php
// UserMapping.php
class UserMapping extends AbstractModelMapping
{
    protected ?string $prefix = 'user';

    protected array $fieldMappings = [
        'email' => 'email_address',
    ];

    public function configure(Extractor $extractor): Extractor
    {
        $extractor = parent::configure($extractor);

        // ✅ Reference ProfileMapping's OUTPUT fields
        $extractor->setRelationshipMappings([
            'profile' => [
                'biography' => 'user_bio',     // ProfileMapping outputs 'biography'
                'employed' => 'is_employed',   // ProfileMapping outputs 'employed'
            ],
        ]);

        return $extractor;
    }
}
```

### Result

```php
$data = Extractor::forModel(User::class)->extract($user);

// Output:
[
    'user.email_address' => 'john@example.com',
    'user_bio' => 'Software engineer...',    // From ProfileMapping
    'is_employed' => true,                    // From ProfileMapping
]
```

### Data Flow

```
Database: profile.bio
    ↓
ProfileMapping: biography
    ↓
UserMapping: user_bio
```

---

## Pattern 1B: Spread Operator (Dynamic)

**Use Case:** Include ALL fields from related mapping automatically

### Implementation

```php
class UserMapping extends AbstractModelMapping
{
    public function configure(Extractor $extractor): Extractor
    {
        $extractor = parent::configure($extractor);

        // Get ProfileMapping instance
        $profileMapping = app(ProfileMapping::class);

        // Use ALL fields dynamically
        $extractor->setRelationshipMappings([
            'profile' => $profileMapping->getFieldMappings(),
        ]);

        return $extractor;
    }
}
```

### Benefits

- ✅ Automatic updates when ProfileMapping changes
- ✅ No manual field listing needed
- ✅ DRY (Don't Repeat Yourself)

### Result

```php
// Automatically includes ALL ProfileMapping fields:
[
    'user.email_address' => 'john@example.com',
    'biography' => 'Software engineer...',   // From ProfileMapping
    'employed' => true,                      // From ProfileMapping
    // Any future ProfileMapping fields appear here automatically
]
```

---

## Pattern 2: Merge with Prefix

**Use Case:** Include related fields with custom prefix

### Implementation

```php
class ApplicantMapping extends AbstractModelMapping
{
    protected ?string $prefix = 'applicant';

    public function configure(Extractor $extractor): Extractor
    {
        $extractor = parent::configure($extractor);

        // Get AddressMapping
        $addressMapping = app(AddressMapping::class);
        $addressFields = $addressMapping->getFieldMappings();

        // Remap with custom prefix
        $remappedFields = [];
        foreach ($addressFields as $original => $mapped) {
            $remappedFields[$mapped] = 'applicant_' . $original;
        }

        $extractor->setRelationshipMappings([
            'address' => $remappedFields,
        ]);

        return $extractor;
    }
}
```

### Result

```php
// AddressMapping has: 'street' => 'street_address', 'city' => 'city_name'
// Result after remapping:
[
    'applicant.name' => 'John Doe',
    'applicant_street' => '123 Main St',     // Prefixed with applicant_
    'applicant_city' => 'New York',          // Prefixed with applicant_
    'applicant_postal_code' => '10001',      // Prefixed with applicant_
]
```

---

## Pattern 3: Computed Fields with Relationships

**Use Case:** Calculate values based on related model data

### Implementation

```php
class EmployeeMapping extends AbstractModelMapping
{
    protected array $computedFields = [];

    public function __construct()
    {
        $this->computedFields = [
            'works_at_large_company' => function ($model) {
                if (!$model->relationLoaded('company')) {
                    return false;
                }
                // Access raw model property
                return ($model->company->employee_count ?? 0) > 100;
            },
        ];
    }

    public function configure(Extractor $extractor): Extractor
    {
        $extractor = parent::configure($extractor);

        // Get CompanyMapping
        $companyMapping = app(CompanyMapping::class);
        $companyFields = $companyMapping->getFieldMappings();

        // Remap company fields
        $remappedFields = [];
        foreach ($companyFields as $original => $mapped) {
            if ($mapped === 'company_name') {
                $remappedFields[$mapped] = 'employer_name';
            } elseif ($mapped === 'employees') {
                $remappedFields[$mapped] = 'employer_size';
            }
        }

        $extractor->setRelationshipMappings([
            'company' => $remappedFields,
        ]);

        return $extractor;
    }
}
```

### Key Points

- **Computed fields** access **raw model** properties (`$model->company->employee_count`)
- **Relationship mappings** reference **output** field names (`company_name`, `employees`)

### Result

```php
[
    'employee.name' => 'John Doe',
    'employer_name' => 'Acme Corp',           // From CompanyMapping output 'company_name'
    'employer_size' => 500,                   // From CompanyMapping output 'employees'
    'works_at_large_company' => true,         // Computed from raw model
]
```

---

## Pattern 4: Nested Multi-Level Relationships

**Use Case:** Access relationships through other relationships (Order → Customer → Address)

### Implementation

```php
class OrderMapping extends AbstractModelMapping
{
    protected ?string $prefix = 'order';

    public function configure(Extractor $extractor): Extractor
    {
        $extractor = parent::configure($extractor);

        $extractor->setRelationshipMappings([
            // Level 1: Direct relationship
            'customer' => [
                'email_address' => 'customer_email',  // From UserMapping
            ],
            // Level 2: Nested through customer
            'customer.address' => [
                'street_address' => 'shipping_street',  // From AddressMapping
                'city_name' => 'shipping_city',         // From AddressMapping
                'zip_code' => 'shipping_zip',           // From AddressMapping
            ],
        ]);

        return $extractor;
    }
}
```

### Result

```php
[
    'order.order_total' => 99.99,
    'customer_email' => 'jane@example.com',     // From UserMapping
    'shipping_street' => '123 Main St',         // From AddressMapping via customer
    'shipping_city' => 'New York',              // From AddressMapping via customer
    'shipping_zip' => '10001',                  // From AddressMapping via customer
]
```

### Data Flow

```
Order
  ↓
customer relationship → UserMapping → 'email_address' → 'customer_email'
  ↓
customer.address relationship → AddressMapping → 'street_address' → 'shipping_street'
```

---

## Complete Working Example

See `examples/17-relationship-mapping-usage.php` for complete implementation of all 4 patterns.

```bash
php examples/17-relationship-mapping-usage.php
```

---

## Best Practices

### ✅ Do

1. **Reference OUTPUT field names**

   ```php
   // ProfileMapping outputs 'biography'
   'profile' => ['biography' => 'user_bio']  // ✅
   ```

2. **Use spread operator for dynamic inclusion**

   ```php
   $extractor->setRelationshipMappings([
       'profile' => $profileMapping->getFieldMappings(),
   ]);
   ```

3. **Eager load relationships**

   ```php
   $user = User::with('profile', 'company')->find($id);
   ```

4. **Use helper methods in computed fields**

   ```php
   'total_orders' => fn($m) => $this->safeRelationshipCount($m, 'orders')
   ```

### ❌ Don't

1. **Don't reference database columns**

   ```php
   'profile' => ['bio' => 'user_bio']  // ❌ 'bio' is DB column
   ```

2. **Don't duplicate mapping logic**

   ```php
   // ❌ Bad: Remapping what ProfileMapping already does
   protected array $fieldMappings = [
       'profile_bio' => 'biography',
   ];
   ```

3. **Don't forget to check relationship loaded**

   ```php
   // ❌ Bad: Can cause errors
   'has_company' => fn($m) => $m->company->exists()

   // ✅ Good: Safe check
   'has_company' => fn($m) => $m->relationLoaded('company') && $m->company !== null
   ```

---

## Summary

| Pattern | Use Case | Key Feature |
|---------|----------|-------------|
| **Pattern 1** | Specific fields | Manual field selection |
| **Pattern 1B** | All fields | Dynamic with spread operator |
| **Pattern 2** | Custom prefix | Merge and rename fields |
| **Pattern 3** | Computed values | Use relationship data in calculations |
| **Pattern 4** | Nested data | Multi-level relationship access |

---

## Related Documentation

- [Relationship Mapping Cheatsheet](relationship-mapping-cheatsheet.md) - Quick reference
- [Mapper Generation Guide](mapper-generation-guide.md) - Auto-generate mappings
- [Configuration Guide](configuration.md) - Setup and config

---

**Remember:** Always reference OUTPUT field names (right side of `$fieldMappings`), not database columns!
