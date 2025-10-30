# Model Mapping Documentation Structure

## 📁 Documentation Organization

### Core Documentation Files

1. **[getting-started.md](getting-started.md)** ⭐ **MAIN GUIDE**
   - Complete guide covering all 4 patterns
   - Step-by-step examples
   - Best practices
   - Based directly on `examples/17-relationship-mapping-usage.php`

2. **[relationship-mapping.md](relationship-mapping.md)** 🔴 **QUICK REFERENCE**
   - One-page visual reference
   - Key rules and common mistakes
   - Quick pattern lookup

3. **[generator.md](generator.md)** 🔨 **AUTOMATION**
   - CLI commands for auto-generation
   - Bulk operations
   - Customization options

4. **[README.md](README.md)** � **OVERVIEW**
   - Quick introduction
   - Navigation guide
   - Basic example

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

1. **Start**: [getting-started.md](getting-started.md)
   - Learn all 4 patterns
   - See complete examples
   - Understand concepts

2. **Quick Reference**: [relationship-mapping.md](relationship-mapping.md)
   - Visual guide
   - Quick lookup
   - Common mistakes

3. **Generate**: [generator.md](generator.md)
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

This rule is clearly explained in:

- Main guide intro ([getting-started.md](getting-started.md))
- Each pattern example
- Cheatsheet ([relationship-mapping.md](relationship-mapping.md))
- Best practices section
- Troubleshooting guides

---

## ✅ Benefits of This Structure

1. **Clarity**: Focused documentation with clear purposes
2. **Consistency**: All based on Example 17's proven patterns
3. **Maintainability**: Minimal duplication, easier to update
4. **Discoverability**: Clear hierarchy and navigation
5. **Accuracy**: Single source of truth for each topic

---

## 🚀 Next Steps for Developers

1. Read [getting-started.md](getting-started.md) for complete patterns
2. Keep [relationship-mapping.md](relationship-mapping.md) open while coding
3. Use `php artisan eligify:make-mapping` to generate mappings
4. Reference Example 17 for working code examples
5. Test and validate your mappings

---

Last updated: 30 October 2025
