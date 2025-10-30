# Quick Reference: Relationship Mapping with Existing Model Mappers

## TL;DR

When models have relationships, **reuse existing model mappings** instead of duplicating logic:

```php
// In your UserMapping class
public function configure(Extractor $extractor): Extractor
{
    $extractor = parent::configure($extractor);

    // Reuse ProfileMapping for profile relationship
    $extractor->setRelationshipMappings([
        'profile' => [
            'biography' => 'user_bio',
            'employed' => 'is_employed',
        ],
    ]);

    return $extractor;
}
```

## Three Main Patterns

### 1. Direct Relationship Mapping

**Use Case:** Include specific fields from a related model

```php
class UserMapping extends AbstractModelMapping
{
    public function configure(Extractor $extractor): Extractor
    {
        $extractor = parent::configure($extractor);

        // Include profile fields
        $extractor->setRelationshipMappings([
            'profile' => [
                'biography' => 'user_bio',
                'employed' => 'is_employed',
            ],
        ]);

        return $extractor;
    }
}
```

**Generated Data:**
```php
[
    'user.email' => 'john@example.com',
    'user_bio' => 'Software engineer...',  // From ProfileMapping
    'is_employed' => true,                  // From ProfileMapping
]
```

---

### 2. Nested Relationship Mapping

**Use Case:** Access relationships through other relationships

```php
class OrderMapping extends AbstractModelMapping
{
    public function configure(Extractor $extractor): Extractor
    {
        $extractor = parent::configure($extractor);

        $extractor->setRelationshipMappings([
            'customer' => [
                'email' => 'customer_email',
            ],
            'customer.address' => [  // Nested!
                'street' => 'shipping_street',
                'city' => 'shipping_city',
            ],
        ]);

        return $extractor;
    }
}
```

**Generated Data:**
```php
[
    'order.total' => 99.99,
    'customer_email' => 'jane@example.com',  // From UserMapping
    'shipping_street' => '123 Main St',      // From AddressMapping via customer
    'shipping_city' => 'New York',           // From AddressMapping via customer
]
```

---

### 3. Relationship Data in Computed Fields

**Use Case:** Calculate values based on relationship data

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
                // Use company data from CompanyMapping
                return ($model->company->employee_count ?? 0) > 100;
            },
        ];
    }

    public function configure(Extractor $extractor): Extractor
    {
        $extractor = parent::configure($extractor);

        $extractor->setRelationshipMappings([
            'company' => [
                'name' => 'employer_name',
                'employee_count' => 'employer_size',
            ],
        ]);

        return $extractor;
    }
}
```

**Generated Data:**
```php
[
    'employee.name' => 'John Doe',
    'employer_name' => 'Acme Corp',           // From CompanyMapping
    'employer_size' => 500,                   // From CompanyMapping
    'works_at_large_company' => true,         // Computed from company data
]
```

---

## Command Generation

### Auto-Detect Relationships

When generating mappings, the command automatically detects existing mapping classes:

```bash
php artisan eligify:make-mapping User
```

If `ProfileMapping` exists and User has a `profile()` relationship, generates:

```php
public function configure(Extractor $extractor): Extractor
{
    $extractor = parent::configure($extractor);

    // Relationship: uses ProfileModelMapping for profile
    $extractor->setRelationshipMappings([
        'profile' => [
            // TODO: Add specific profile fields you want to include
        ],
    ]);

    return $extractor;
}
```

### Bulk Generation

Generate mappings for all models at once:

```bash
# Generate for all models in app/Models
php artisan eligify:make-all-mappings

# Preview without creating files
php artisan eligify:make-all-mappings --dry-run

# Overwrite existing mappings
php artisan eligify:make-all-mappings --force
```

---

## Field Prefixing

Each mapping has its own prefix to avoid naming collisions:

```php
class UserMapping extends AbstractModelMapping
{
    protected ?string $prefix = 'user';  // Fields: user.id, user.email, etc.
}

class ProfileMapping extends AbstractModelMapping
{
    protected ?string $prefix = 'profile';  // Fields: profile.bio, profile.employed, etc.
}
```

**In Eligibility Rules:**
```php
Eligify::criteria('user_verification')
    ->addRule('user.email', 'not_null')           // UserMapping field
    ->addRule('user_bio', 'not_null')             // ProfileMapping field (via relationship)
    ->addRule('profile.has_complete_bio', '=', true)  // ProfileMapping computed
    ->evaluate($data);
```

---

## Common Patterns Cheat Sheet

| Pattern | Code | Result |
|---------|------|--------|
| **Direct field** | `'profile' => ['bio' => 'user_bio']` | Maps `profile.bio` → `user_bio` |
| **Multiple fields** | `'profile' => ['bio' => 'user_bio', 'city' => 'location']` | Maps multiple fields at once |
| **Nested** | `'customer.address' => ['street' => 'ship_street']` | Access nested relationships |
| **Computed** | `'risk' => fn($m) => $m->profile->score * 100` | Calculate from relationship |
| **Conditional** | `if ($model->relationLoaded('company'))` | Only include if loaded |

---

## Best Practices

### ✅ Do

1. **Create one mapping per model**
   ```php
   // Good: Separate mappings
   UserMapping, ProfileMapping, AddressMapping
   ```

2. **Reuse mappings in relationships**
   ```php
   // Good: Reference ProfileMapping in UserMapping
   'profile' => ['bio' => 'user_bio']
   ```

3. **Use descriptive field aliases**
   ```php
   // Good: Clear purpose
   'employment_status' => 'is_employed'
   ```

4. **Add prefixes to avoid collisions**
   ```php
   // Good: Namespaced
   protected ?string $prefix = 'user';
   ```

### ❌ Don't

1. **Don't duplicate mapping logic**
   ```php
   // Bad: Same mapping in multiple places
   class UserMapping {
       'profile_bio' => 'bio'  // Duplicates ProfileMapping
   }
   ```

2. **Don't hardcode relationship extraction**
   ```php
   // Bad: Manual extraction
   'user_profile_bio' => fn($m) => $m->profile->bio

   // Good: Use relationship mapping
   'profile' => ['bio' => 'user_bio']
   ```

3. **Don't forget eager loading**
   ```php
   // Bad: N+1 queries
   $data = $extractor->extract($user);

   // Good: Eager load relationships
   $user = User::with('profile', 'company')->find($id);
   $data = $extractor->extract($user);
   ```

---

## Complete Working Example

See `examples/17-relationship-mapping-usage.php` for 4 complete patterns:

1. **Direct relationship** - User → Profile
2. **Merged fields** - Applicant → Address
3. **Computed with relationship** - Employee → Company
4. **Multi-level nested** - Order → Customer → Address

Run it:
```bash
php examples/17-relationship-mapping-usage.php
```

---

## Quick Troubleshooting

| Problem | Solution |
|---------|----------|
| Fields not extracted | Check relationship is loaded with `$model->load('relation')` |
| Wrong field names | Verify mapping alias matches what you're using in rules |
| Missing computed fields | Ensure relationship loaded before computed field runs |
| Prefix collisions | Use unique prefixes like `user`, `profile`, `company` |
| N+1 queries | Eager load relationships before extraction |

---

## Related Documentation

- [Model Mappings Documentation](model-mappings.md)
- [Mapper Generation Guide](mapper-generation-guide.md)
- [Model Data Extraction Guide](model-data-extraction.md)
- [Quick Reference: Mapping Generation](quick-reference-mapping-generation.md)
