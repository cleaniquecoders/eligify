# Documentation Migration Guide

**For developers using old model mapping documentation**

## 📅 Migration Date: 30 October 2025

---

## ✅ What Changed

The model mapping documentation has been **completely restructured** for clarity and consistency. All documentation now follows the 4 proven patterns from `examples/17-relationship-mapping-usage.php`.

---

## 📚 Old Files → New Files

| Old File (DELETED) | New File | Status |
|-------------------|----------|--------|
| `model-mappings.md` | `model-mapping-guide.md` | ✅ Complete rewrite |
| `model-data-extraction.md` | `model-mapping-guide.md` | ✅ Merged in |
| `quick-reference-model-extraction.md` | `model-mapping-guide.md` | ✅ Merged in |
| `quick-reference-relationship-mapping.md` | `relationship-mapping-cheatsheet.md` | ✅ Simplified |
| `quick-reference-mapping-generation.md` | `mapper-generation-guide.md` | ✅ Info retained |
| `extractor-architecture.md` | N/A | ❌ Removed (too technical) |
| `make-mapping-command.md` | `mapper-generation-guide.md` | ✅ Merged in |

---

## 🎯 New Structure

### 1. Main Guide
**File**: `model-mapping-guide.md`

**Covers**:
- All 4 relationship patterns
- Complete examples
- Best practices
- When to use each pattern

**Read this first!**

### 2. Quick Reference
**File**: `relationship-mapping-cheatsheet.md`

**Covers**:
- Visual guide
- Common mistakes
- Quick pattern lookup
- Golden rule (OUTPUT vs DB columns)

**Keep this open while coding!**

### 3. Generator Guide
**File**: `mapper-generation-guide.md`

**Covers**:
- CLI commands
- Auto-generation
- Bulk operations

**Use this to save time!**

### 4. Example Code
**File**: `examples/17-relationship-mapping-usage.php`

**Covers**:
- Working implementation
- All 4 patterns in action
- Copy-paste ready code

**Run this to see it work!**

---

## 🔄 Code Changes Needed

### ❌ Old Approach (WRONG)

```php
// Using database column names
$extractor->setRelationshipMappings([
    'profile' => [
        'bio' => 'user_bio',                    // ❌ 'bio' is DB column
        'employment_status' => 'is_employed',   // ❌ DB column
    ],
]);
```

### ✅ New Approach (CORRECT)

```php
// Using ProfileMapping OUTPUT field names
$extractor->setRelationshipMappings([
    'profile' => [
        'biography' => 'user_bio',     // ✅ 'biography' is ProfileMapping output
        'employed' => 'is_employed',   // ✅ 'employed' is ProfileMapping output
    ],
]);
```

**Key Change**: Reference OUTPUT field names (right side of `$fieldMappings`), not database columns!

---

## 📖 Where to Find Information

### Old Documentation Said...

**"Create field mappings for relationships"**

### New Documentation Says...

**Pattern 1: Direct Field Reference**
- See `model-mapping-guide.md` → Pattern 1 section
- Reference OUTPUT fields from related mappings
- Select specific fields only

**Pattern 1B: Spread Operator**
- See `model-mapping-guide.md` → Pattern 1B section
- Include ALL fields dynamically
- Auto-updates when related mapping changes

**Pattern 2: Merge with Prefix**
- See `model-mapping-guide.md` → Pattern 2 section
- Custom prefix for fields
- Remap and rename related fields

**Pattern 3: Computed Fields**
- See `model-mapping-guide.md` → Pattern 3 section
- Use relationship data in calculations
- Access raw model properties

**Pattern 4: Nested Relationships**
- See `model-mapping-guide.md` → Pattern 4 section
- Multi-level relationships
- Deep data access

---

## 🚀 Migration Steps

### Step 1: Update Bookmarks

Remove bookmarks to:
- ❌ `model-mappings.md`
- ❌ `model-data-extraction.md`
- ❌ `quick-reference-*.md`

Add bookmarks to:
- ✅ `model-mapping-guide.md`
- ✅ `relationship-mapping-cheatsheet.md`

### Step 2: Review Your Code

Check all `setRelationshipMappings()` calls:

```bash
# Find all usages in your code
grep -r "setRelationshipMappings" app/
```

For each usage:
1. Identify the related mapping class
2. Check its `$fieldMappings` array
3. Verify you're using VALUES (right side), not KEYS (left side)

### Step 3: Run Tests

```bash
# Test your mappings
php artisan test --filter=ModelMappingTest

# Or test manually
php examples/17-relationship-mapping-usage.php
```

### Step 4: Update Team

Share this migration guide with your team:
```bash
# Copy to your project
cp vendor/cleaniquecoders/eligify/docs/migration-guide.md docs/
```

---

## ❓ FAQ

### Q: Do I need to change my existing mapping classes?

**A**: Only if you're using `setRelationshipMappings()` and referencing database columns instead of OUTPUT field names. Check the [model-mapping-guide.md](model-mapping-guide.md) for correct patterns.

### Q: Where did the `Extractor` architecture docs go?

**A**: Removed as too technical. The main guide now covers practical usage. See `model-mapping-guide.md` for everything you need.

### Q: Can I still use old patterns?

**A**: If your code works, it works. But we recommend updating to the new patterns for:
- Better maintainability
- Clearer intent
- Future compatibility
- Team consistency

### What if I'm confused?

1. Read [model-mapping-guide.md](model-mapping-guide.md) from start to finish
2. Look at [relationship-mapping-cheatsheet.md](relationship-mapping-cheatsheet.md)
3. Run `examples/17-relationship-mapping-usage.php`
4. Check [model-mapping-structure.md](model-mapping-structure.md) for overview

---

## 📞 Support

If you have questions:
1. Check [model-mapping-guide.md](model-mapping-guide.md) first
2. Look at [relationship-mapping-cheatsheet.md](relationship-mapping-cheatsheet.md)
3. Run the example: `php examples/17-relationship-mapping-usage.php`
4. Open an issue on GitHub with specific questions

---

## 🎉 Benefits of New Documentation

1. **Clearer**: 4 distinct patterns, no confusion
2. **Consistent**: All based on working Example 17
3. **Practical**: Focus on what you need, not theory
4. **Maintainable**: Less duplication, easier to update
5. **Accurate**: No more conflicting information between docs

---

**Last Updated**: 30 October 2025
**Effective Date**: Immediate
**Breaking Changes**: None (code works the same, docs improved)
