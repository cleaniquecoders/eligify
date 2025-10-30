# Model Mapping Documentation Structure

## 📁 New Simplified Structure

### Core Documentation (Keep)

1. **[model-mapping-guide.md](model-mapping-guide.md)** ⭐ **MAIN GUIDE**
   - Complete guide covering all 4 patterns
   - Step-by-step examples
   - Best practices
   - Based directly on `examples/17-relationship-mapping-usage.php`

2. **[relationship-mapping-cheatsheet.md](relationship-mapping-cheatsheet.md)** 🔴 **QUICK REFERENCE**
   - One-page visual reference
   - Key rules and common mistakes
   - Quick pattern lookup

3. **[mapper-generation-guide.md](mapper-generation-guide.md)** 🔨 **AUTOMATION**
   - CLI commands for auto-generation
   - Bulk operations
   - Customization options

4. **[snapshot.md](snapshot.md)** 📦 **DATA SNAPSHOTS**
   - Snapshot functionality
   - Use cases

---

## 📚 The 4 Patterns (from Example 17)

All documentation now consistently references these patterns:

### Pattern 1: Direct Field Reference

Select specific fields from related mapping

```php
'profile' => [
    'biography' => 'user_bio',
    'employed' => 'is_employed',
]
```

### Pattern 1B: Spread Operator

Include ALL fields dynamically

```php
'profile' => $profileMapping->getFieldMappings()
```

### Pattern 2: Merge with Prefix

Custom prefix for related fields

```php
foreach ($addressFields as $original => $mapped) {
    $remapped[$mapped] = 'applicant_' . $original;
}
```

### Pattern 3: Computed Fields with Relationships

Calculate values from related data

```php
'works_at_large_company' => function ($model) {
    return ($model->company->employee_count ?? 0) > 100;
}
```

### Pattern 4: Nested Multi-Level

Access deep relationships

```php
'customer.address' => [
    'street_address' => 'shipping_street',
]
```

---

## 🎯 Documentation Flow

1. **Start**: [model-mapping-guide.md](model-mapping-guide.md)
   - Learn all 4 patterns
   - See complete examples
   - Understand concepts

2. **Quick Reference**: [relationship-mapping-cheatsheet.md](relationship-mapping-cheatsheet.md)
   - Visual guide
   - Quick lookup
   - Common mistakes

3. **Generate**: [mapper-generation-guide.md](mapper-generation-guide.md)
   - Auto-generate mappings
   - Save time
   - Consistent structure

4. **Practice**: `examples/17-relationship-mapping-usage.php`
   - Run working code
   - See all patterns in action
   - Copy and adapt

---

## 🔑 Key Concept (Emphasized Everywhere)

**CRITICAL RULE**: Always reference OUTPUT field names from related mappings, NOT database columns.

```php
// ✅ CORRECT
'profile' => [
    'biography' => 'user_bio',  // 'biography' is ProfileMapping OUTPUT
]

// ❌ WRONG
'profile' => [
    'bio' => 'user_bio',  // 'bio' is database column
]
```

This rule is now clearly explained in:

- Main guide intro
- Each pattern example
- Cheatsheet (red alert box)
- Best practices section
- Troubleshooting section

---

## 📖 README.md Integration

Updated sections in `docs/README.md`:

- Table of Contents: Simplified to 4 core docs
- Model Mapping section: Streamlined with pattern preview
- Links: All point to new structure
- Examples: Point to Example 17

---

## ✅ Benefits of New Structure

1. **Clarity**: One main guide, one cheatsheet, one automation guide
2. **Consistency**: All based on Example 17's proven patterns
3. **Maintainability**: Less duplication, easier to update
4. **Discoverability**: Clear hierarchy and flow
5. **Accuracy**: No more conflicting information

---

## 🚀 Next Steps for Developers

1. Read [model-mapping-guide.md](model-mapping-guide.md)
2. Keep [relationship-mapping-cheatsheet.md](relationship-mapping-cheatsheet.md) open while coding
3. Use `php artisan eligify:make-mapping` to generate mappings
4. Reference [Example 17](../examples/17-relationship-mapping-usage.php) for working code
5. Test and validate your mappings

---

Generated: 30 October 2025
