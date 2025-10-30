# Relationship Mapping Cheatsheet

## 🔴 THE GOLDEN RULE

**Always reference OUTPUT field names from related mappings, NOT database column names.**

---

## Visual Guide

```
┌─────────────────────────────────────────────────────────────────────┐
│                   HOW TO REFERENCE RELATED MAPPINGS                 │
└─────────────────────────────────────────────────────────────────────┘

Step 1: Look at the related mapping's $fieldMappings
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

// ProfileMapping.php
protected array $fieldMappings = [
    'bio' => 'biography',              // DB column → OUTPUT field name
    'employment_status' => 'employed', // DB column → OUTPUT field name
];


Step 2: Use the OUTPUT field names (right side of =>)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

// UserMapping.php
$extractor->setRelationshipMappings([
    'profile' => [
        'biography' => 'user_bio',   // ✅ Use 'biography' (OUTPUT)
        'employed' => 'is_employed', // ✅ Use 'employed' (OUTPUT)
    ],
]);


❌ WRONG: Don't use database column names (left side)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

$extractor->setRelationshipMappings([
    'profile' => [
        'bio' => 'user_bio',                    // ❌ 'bio' is DB column
        'employment_status' => 'is_employed',   // ❌ 'employment_status' is DB column
    ],
]);
```

---

## Quick Reference Table

| Mapping Type | What to Reference | Example |
|--------------|-------------------|---------|
| **Field Mappings** | OUTPUT field name (right side of `=>`) | `'biography' => 'user_bio'` |
| **Computed Fields** | Raw model properties | `fn($m) => $m->profile->bio` |
| **Nested Relationships** | OUTPUT from each mapping | `'customer.address' => ['street_address' => ...]` |

---

## Complete Example

```php
// 1️⃣ ProfileMapping transforms DB columns
class ProfileMapping extends AbstractModelMapping
{
    protected array $fieldMappings = [
        'bio' => 'biography',                    // Transform
        'employment_status' => 'employed',       // Transform
    ];
}

// 2️⃣ UserMapping references ProfileMapping's OUTPUTS
class UserMapping extends AbstractModelMapping
{
    public function configure(Extractor $extractor): Extractor
    {
        $extractor = parent::configure($extractor);

        // Reference OUTPUT fields from ProfileMapping
        $extractor->setRelationshipMappings([
            'profile' => [
                'biography' => 'user_bio',     // ✅ 'biography' is ProfileMapping output
                'employed' => 'is_employed',   // ✅ 'employed' is ProfileMapping output
            ],
        ]);

        return $extractor;
    }
}

// 3️⃣ Data transformation flow
Database: profile.bio = "Software engineer..."
    ↓
ProfileMapping: biography = "Software engineer..."
    ↓
UserMapping: user_bio = "Software engineer..."
    ↓
Final Result: ['user_bio' => 'Software engineer...']
```

---

## Common Mistakes & Fixes

| ❌ Mistake | ✅ Fix |
|-----------|--------|
| `'profile' => ['bio' => ...]` | `'profile' => ['biography' => ...]` |
| `'company' => ['name' => ...]` | `'company' => ['company_name' => ...]` (if CompanyMapping outputs 'company_name') |
| `'address' => ['street' => ...]` | `'address' => ['street_address' => ...]` (if AddressMapping outputs 'street_address') |

---

## How to Check What to Reference

### Method 1: Open the Related Mapping File

```php
// In ProfileMapping.php, look at $fieldMappings:
protected array $fieldMappings = [
    'bio' => 'biography',  // ← Use 'biography' (right side)
];
```

### Method 2: Use Your IDE

1. Open the related mapping class
2. Find `protected array $fieldMappings`
3. Use the VALUES (right side of `=>`)

### Method 3: Run Example 17

```bash
php examples/17-relationship-mapping-usage.php
```

This shows all relationship patterns with clear explanations.

---

## Pattern Cheatsheet

### Pattern 1: Direct Relationship

```php
// Reference UserMapping's outputs
'user' => [
    'email_address' => 'customer_email',  // UserMapping outputs 'email_address'
]
```

### Pattern 2: Nested Relationship

```php
// Reference through relationships
'customer.address' => [
    'street_address' => 'shipping_street',  // AddressMapping outputs 'street_address'
]
```

### Pattern 3: Multiple Fields

```php
// Reference multiple outputs from same mapping
'profile' => [
    'biography' => 'user_bio',
    'employed' => 'is_employed',
    'city_name' => 'location',
]
```

---

## Key Takeaways

1. ✅ **DO** reference OUTPUT field names (right side of `$fieldMappings`)
2. ❌ **DON'T** reference database column names (left side of `$fieldMappings`)
3. 📖 **CHECK** the related mapping class before writing your relationship mappings
4. 🔄 **REMEMBER** the flow: DB → RelatedMapping → YourMapping
5. 🧪 **TEST** with example 17 if unsure

---

## See Also

- [Model Mapping Guide](model-mapping-guide.md) - Complete guide with all patterns
- [Mapper Generation Guide](mapper-generation-guide.md) - Auto-generate mappings
- [Example 17](../examples/17-relationship-mapping-usage.php) - Working code examples
