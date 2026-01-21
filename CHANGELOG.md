# Changelog

All notable changes to `eligify` will be documented in this file.

## Rule Groups & Versioning - 2025-11-07

### 🎯 Major Features

#### Rule Groups

Organize rules into logical groups with advanced combination logic:

```php
Eligify::criteria('Loan Approval')
    ->group('Financial', 'ALL')
        ->addRule('income', '>=', 3000, weight: 0.4)
        ->addRule('debt_ratio', '<=', 0.3, weight: 0.3)
    ->endGroup()
    ->group('Credit', 'OR')
        ->addRule('credit_score', '>=', 650, weight: 0.3)
        ->addRule('no_defaults', '==', true)
    ->endGroup()
    ->evaluate($applicant);

```
**Features:**

- Multiple logic types: ALL, ANY, MIN_REQUIRED, MAJORITY, BOOLEAN
- Group weights for scoring
- Group-level callbacks: `onPass()`, `onFail()`

#### Rule Versioning

Create snapshots of your criteria rules for historical evaluation and audit trails:

```php
// Create a version snapshot
$criteria->createVersion('Q4 2025 - Stricter requirements');

// Evaluate against a specific version
Eligify::evaluateVersion($criteria, 2, $applicant);

// Compare versions
$diff = Eligify::compareVersions($criteria, 1, 2);
// Returns: ['added' => [...], 'removed' => [...], 'modified' => [...]]

```
**Features:**

- Automatic version snapshots with immutable rule state
- Historical evaluation against any version
- Version comparison and audit tracking
- Version history in UI

### 📚 Documentation

- [Rule Groups Guide](https://github.com/cleaniquecoders/eligify/blob/main/docs/07-advanced-features/rule-groups.md)
- [Rule Versioning Guide](https://github.com/cleaniquecoders/eligify/blob/main/docs/07-advanced-features/rule-versioning.md)

### 🧪 Testing

- 14+ Rule Groups test cases
- 15+ Versioning test cases
- 100% backward compatible

### 🚀 Upgrade

```bash
composer update cleaniquecoders/eligify
php artisan migrate

```
**Full Changelog**: https://github.com/cleaniquecoders/eligify/compare/v1.3.6...v1.4.0

## Fixed Input Not Display - 2025-11-03

### Release Notes - v1.3.6

#### 🐛 Bug Fixes

##### Rule Editor Input Display Issue

- **Fixed**: Input fields not displaying when changing field types from array to text/number/date
- **Root Cause**: Livewire component rendering conflicts and operator state management
- **Solution**:
  - Enhanced field type change handling to reset operators appropriately
  - Improved operator change logic to handle both single and multiple value transitions
  - Replaced problematic Blade component with standard HTML input for better compatibility
  

##### Technical Details

- Updated `RuleEditor::updatedFieldType()` to automatically select compatible operators for non-array field types
- Enhanced `RuleEditor::updatedOperator()` to properly clear values when switching between operator types
- Replaced `x-eligify::ui.input` component usage with native HTML input in rule editor form

#### 🎯 Impact

- Rule creation and editing forms now work reliably across all field types
- Improved user experience when switching between different data types
- Better form state management and validation


---

**Full Changelog**: [View on GitHub](https://github.com/cleaniquecoders/eligify/compare/v1.3.5...v1.3.6)

**Full Changelog**: https://github.com/cleaniquecoders/eligify/compare/1.3.5...1.3.6

## UI Improvement - Support both TailwindCSS and Bootstrap - 2025-10-31

### Eligify v1.3.5 (2025-10-31)

<center>
<img width="240" height="237" alt="image" src="https://github.com/user-attachments/assets/7c56a622-eee0-4c05-8538-f0efa58042f7" />
**Bootstrap**
<img width="240" height="237" alt="image" src="https://github.com/user-attachments/assets/df337a7c-170b-48bf-b3f4-664d4591e077" />
**TailwindCSS**
</center>
- UI theming: added configurable theme switch (`eligify.ui.theme`) with Tailwind (default) and Bootstrap support; conditional asset loading.
- New theme-aware Blade components: button, input, select, textarea, checkbox, radio, badge, card (via `CleaniqueCoders\Eligify\Support\Theme`).
- Livewire UX: Rule Editor now auto-switches to textarea for multi-value operators (IN/NOT_IN/BETWEEN/NOT_BETWEEN); immediate reactivity on operator change.
- Criteria List: added filters by type, group, category, and tag for faster browsing.
- Helpers: unified smart finder `eligify_find_criteria(string $keyword, bool $createIfMissing = false)` and updated docs; improved evaluate flags.
- Workbench seeder: seeded example criteria and rules using enums; normalized scalar vs array rule values.
- Layout polish: fixed logo tile visibility across themes; Tailwind gradient classes corrected for CDN (v3) compatibility.
Upgrade notes:
- If you previously used `eligify_find_criteria($keyword, $field)`, update calls to the new signature or use slugs/names directly.
- To enable Bootstrap UI, set `ELIGIFY_UI_THEME=bootstrap` (or `config('eligify.ui.theme') = 'bootstrap'`).

## Criteria classification & polymorphic attachments - 2025-10-31

### Eligify v1.3.4 (2025-10-31)

- Added criteria classification fields: type, group, category, tags (nullable) on eligify_criteria.
- Added polymorphic attachments via eligify_criteriables and HasCriteria trait for $model->criteria().
- Enhanced CriteriaBuilder with chainable methods: type(), group(), category(), tags(), addTags(), removeTags(), clearTags().
- Added migration stubs for the new columns and pivot; tests now bootstrap all stubs in deterministic order.
- Updated documentation: database schema, models API, builder API, core features (Criteria Attachments), getting started, and migration guide.

Upgrade notes:

- Run the new migrations in your app after publishing.
- No breaking changes expected (new columns are nullable; pivot is additive).

## FIx Advanced Rule Engine and Added Helpers - 2025-10-30

### Version 1.3.3 - 2025-10-30

#### Fixed

- **Type Safety Improvements**: Updated `AdvancedRuleEngine` and `RuleEngine` to accept `array|Snapshot` parameter types instead of `array` only, providing better flexibility for data input and consistent handling across evaluation methods

#### Added

- **Helper Functions**: Three new global helper functions for simplified eligibility operations:
  - `eligify_snapshot(string $model, Model $data): Snapshot` - Create data snapshots from Eloquent models
  - `eligify_evaluate(string|Criteria $criteria, Snapshot $snapshot): mixed` - Quick eligibility evaluation without persistence
  - `eligify_find_criteria(string $keyword, string $field = 'name'): Criteria` - Find or create criteria instances gracefully
  

**Full Changelog**: https://github.com/cleaniquecoders/eligify/compare/1.3.2...1.3.3

## Refactor and Update Documentation - 2025-10-30

**Full Changelog**: https://github.com/cleaniquecoders/eligify/compare/1.3.0...1.3.1

## Model Mapping Namespace Migration - 2025-10-30

### Breaking Changes

- **BREAKING**: Moved model mapping classes from `CleaniqueCoders\Eligify\Mappings` to `CleaniqueCoders\Eligify\Data\Mappings`
- **BREAKING**: Moved `ModelMapping` interface from `CleaniqueCoders\Eligify\Contracts` to `CleaniqueCoders\Eligify\Data\Contracts`

### Migration Guide

Update all imports in your code:

```php
// Before
use CleaniqueCoders\Eligify\Mappings\AbstractModelMapping;
use CleaniqueCoders\Eligify\Contracts\ModelMapping;

// After
use CleaniqueCoders\Eligify\Data\Mappings\AbstractModelMapping;
use CleaniqueCoders\Eligify\Data\Contracts\ModelMapping;






```
This change better organizes the package structure by grouping all data-related functionality under the `Data` namespace alongside `Extractor` and `Snapshot` classes.

## Model Mapping Generation & Relationship Patterns - 2025-10-30

### Summary

Version 1.3.0 introduces a comprehensive model mapping generation system with automatic prefix support, relationship detection, and reusable mapping patterns. This release also includes experimental cache mechanisms and enhanced UI features for dynamic field selection.


---

### 🚀 Major Features

#### 1. **Automated Model Mapping Generation**

Two new Artisan commands for scaffolding model mappings:

```bash
# Generate single mapping
php artisan eligify:make-mapping "App\Models\User"

# Bulk generate all models in directory
php artisan eligify:make-all-mappings
php artisan eligify:make-all-mappings --dry-run
php artisan eligify:make-all-mappings --path=modules/User/Models --namespace=Modules\\User\\Models







```
**Features:**

- ✅ Automatic field detection from database schema
- ✅ Relationship detection with mapping awareness
- ✅ Computed field suggestions (is_verified, is_active, etc.)
- ✅ Timestamp field detection
- ✅ Dry-run mode for preview
- ✅ Force overwrite option

#### 2. **Automatic Prefix Generation**

Each mapping now auto-generates a prefix based on model name:

| Model Class | Auto-Generated Prefix | Field Examples |
|-------------|----------------------|----------------|
| `User` | `user` | `user.name`, `user.email` |
| `Applicant` | `applicant` | `applicant.income` |
| `LoanApplication` | `loan.application` | `loan.application.amount` |

**Benefits:**

- Prevents field name collisions
- Makes rules more readable
- Clear namespace separation

#### 3. **Relationship Mapping Patterns**

Four comprehensive patterns for reusing mappings across relationships:

**Pattern 1: Direct Field Selection**

```php
$extractor->setRelationshipMappings([
    'profile' => [
        'biography' => 'user_bio',
        'employed' => 'is_employed',
    ],
]);







```
**Pattern 2: Spread Operator (Include All Fields)**

```php
$profileMapping = app(ProfileModelMapping::class);
$extractor->setRelationshipMappings([
    'profile' => $profileMapping->getFieldMappings(),
]);







```
**Pattern 3: Merge with Prefix Remapping**

```php
$addressFields = $addressMapping->getFieldMappings();
foreach ($addressFields as $original => $mapped) {
    $remappedFields[$mapped] = 'applicant_'.$original;
}







```
**Pattern 4: Multi-Level Nested Relationships**

```php
$extractor->setRelationshipMappings([
    'customer' => ['email_address' => 'customer_email'],
    'customer.address' => ['street_address' => 'shipping_street'],
]);







```
#### 4. **Enhanced UI: Dynamic Field Selection**

New dynamic field selection in Rule Editor based on model mappings:

**Features:**

- 📋 Choose model mapping first, then select from available fields
- 🏷️ Field type auto-detection (string, integer, boolean, datetime)
- 📝 Helpful descriptions for each field
- 📁 Category grouping (attributes, computed, relationships)
- ✅ Operator filtering based on field type
- 🔄 Toggle between mapping-based and manual input

<img width="1240" height="691" alt="Screenshot 2025-10-30 at 12 43 39 PM" src="https://github.com/user-attachments/assets/f7969e2d-6527-4263-86c4-93fb0de525c7" />
<img width="1205" height="504" alt="Screenshot 2025-10-30 at 9 24 26 AM" src="https://github.com/user-attachments/assets/b1013ef8-ccc5-4056-82b7-7d9e6abaa610" />
**Improvements:**
- Sort results alphabetically by category
- Fixed extra backslash in namespace display
- Dynamic operator suggestions based on field type
#### 5. **Data Extraction Evolution**
**Initial Approach:** Extracted Model Data class
```php
// Early implementation (deprecated)
$data = ExtractedModelData::from($model);
```
**Current Approach:** Snapshot + Extractor pattern
```php
// Modern implementation
$extractor = new Extractor(['include_relationships' => true]);
$snapshot = $extractor->extract($model);







```
**Benefits:**

- More flexible data extraction
- Support for custom mappings
- Relationship data inclusion
- Computed fields support

#### 6. **Experimental: Cache Mechanism** ⚠️

Performance optimization through evaluation result caching:

```php
// Enable in config
'evaluation' => [
    'cache_enabled' => true,
    'cache_ttl' => 3600,
],

// Usage
$result = Eligify::evaluate($criteria, $data, false); // Cached
$result = Eligify::evaluate($criteria, $data, false, false); // Bypass cache

// Cache management
Eligify::flushCache();
Eligify::warmupCache($criteria, $sampleDataSets);
Eligify::invalidateCache($criteria);







```
**Features:**

- ✅ Automatic cache invalidation on criteria/rule updates
- ✅ Cache warmup support
- ✅ Per-criteria cache invalidation
- ✅ Cache statistics
- ⚠️ **Status: Experimental** - May change in future releases


---

### 📦 New Files & Components

**Commands:**

- MakeMappingCommand.php - Single mapping generator
- MakeAllMappingsCommand.php - Bulk mapping generator

**Contracts:**

- ModelMapping.php - Mapping interface

**Base Classes:**

- AbstractModelMapping.php - Enhanced with getters
- UserModelMapping.php - Default User mapping

**Stubs:**

- model-mapping.stub - Template for generated mappings

**Examples:**

- 16-mapping-generation.php - Prefix & bulk generation
- 17-relationship-mapping-usage.php - Relationship patterns

**Documentation:**

- mapper-generation-guide.md - Comprehensive guide
- quick-reference-mapping-generation.md - Quick commands
- quick-reference-relationship-mapping.md - Relationship patterns
- dynamic-field-selection.md - UI field selection guide (updated)

**Tests:**

- MappingPrefixTest.php - Prefix generation tests
- CacheTest.php - Cache mechanism tests


---

### 🔧 Technical Improvements

1. **AbstractModelMapping Enhancements:**
   
   - Added `getFieldMappings()` public method
   - Added `getRelationshipMappings()` public method
   - Added `getComputedFields()` public method
   - Added `getPrefix()` with auto-generation
   - Enhanced helper methods for relationship data
   
2. **Service Provider Updates:**
   
   - Registered new mapping generation commands
   - Auto-discovery of mapping classes
   
3. **Namespace Handling:**
   
   - Fixed extra backslash display in UI
   - Better namespace resolution for modules
   - Support for Workbench models
   


---

### 📖 Usage Examples

#### Generate Mappings for Loan System

```bash
# Step 1: Generate all mappings
php artisan eligify:make-all-mappings

# Step 2: Mappings created with prefixes
# - UserMapping (prefix: 'user')
# - ApplicantMapping (prefix: 'applicant')
# - LoanApplicationMapping (prefix: 'loan.application')







```
#### Use in Eligibility Rules

```php
Eligify::criteria('Loan Approval')
    // Applicant fields
    ->addRule('applicant.income', '>=', 3000)
    ->addRule('applicant.employment_years', '>=', 2)

    // User relationship (via UserMapping)
    ->addRule('applicant.user.is_verified', '=', true)

    // Credit report relationship
    ->addRule('applicant.credit_report.score', '>=', 650)

    ->evaluate($applicant);







```

---

### ⚡ Performance

- Mapping auto-discovery with caching
- Relationship detection optimized
- Cache mechanism for evaluation results (experimental)
- Reduced redundant field mapping definitions


---

### 🔄 Breaking Changes

**None.** This is a minor release with backward-compatible additions.


---

### 📋 Migration Guide

#### From Manual Mapping to Generated

**Before:**

```php
// Manual mapping creation
class UserMapping extends AbstractModelMapping
{
    // Manual field definitions...
}







```
**After:**

```bash
# Generate automatically
php artisan eligify:make-mapping "App\Models\User"

# Review and customize generated mapping







```
#### Adopting Relationship Patterns

**Before (Duplicated Logic):**

```php
// Duplicate field mappings in each parent
protected array $relationshipMappings = [
    'profile.bio' => 'user_bio',
    'profile.employment_status' => 'is_employed',
];







```
**After (Reuse Existing Mapping):**

```php
public function configure(Extractor $extractor): Extractor
{
    $profileMapping = app(ProfileModelMapping::class);
    $extractor->setRelationshipMappings([
        'profile' => $profileMapping->getFieldMappings(),
    ]);
    return $extractor;
}







```

---

### 🐛 Bug Fixes

- Fixed extra backslash in namespace display (UI)
- Improved relationship detection for edge cases
- Better handling of multi-word model names in prefix generation


---

### 🎯 Benefits Summary

1. **Faster Development** - Auto-generate mappings instead of manual creation
2. **Less Duplication** - Reuse mappings across relationships
3. **Better Organization** - Automatic prefixes prevent naming conflicts
4. **Easier Maintenance** - Change mapping once, affects all usages
5. **Type Safety** - Field type detection and validation
6. **Better UX** - Dynamic field selection in UI


---

### ⚠️ Known Limitations

1. **Cache Mechanism** - Experimental feature, may change
2. **Relationship Detection** - Requires proper type hints on relationship methods
3. **Field Discovery** - Only detects fields explicitly defined in mappings


---

### 🚀 Upgrade Instructions

```bash
# 1. Update package
composer update cleaniquecoders/eligify

# 2. Publish new assets (optional)
php artisan vendor:publish --tag="eligify-config" --force
php artisan vendor:publish --tag="eligify-migrations" --force

# 3. Generate mappings for existing models
php artisan eligify:make-all-mappings --dry-run  # Preview first
php artisan eligify:make-all-mappings            # Generate

# 4. Review and customize generated mappings







```

---

### 📚 Documentation

- Mapper Generation Guide
- Quick Reference: Mapping Generation
- Quick Reference: Relationship Mapping
- Dynamic Field Selection
- Example 16: Mapping Generation
- Example 17: Relationship Patterns


---

### 🙏 Acknowledgements

Thank you to everyone who provided feedback on the mapping system design and helped shape these relationship patterns.


---

### 🔮 What's Next (v1.4.0)

- Enhanced cache mechanism (stabilization)
- Visual mapping editor in UI
- Import/export mapping configurations
- Mapping versioning and migration tools


---

**Full Changelog:** CHANGELOG.md

## Fix Github Action - 2025-10-29

### v1.2.2 - CI/CD Optimization - 2025-10-29

#### What's Changed

Version 1.2.2 focuses on streamlining the continuous integration pipeline for faster, more targeted testing.

##### 🚀 CI/CD Improvements

###### Optimized Test Matrix

The GitHub Actions workflow has been simplified to focus on the primary production environment:

- **Single OS**: Ubuntu Linux only (removed Windows testing)
- **Single PHP Version**: PHP 8.4 only (removed PHP 8.3)
- **Single Laravel Version**: Laravel 12.x only (removed Laravel 11.x)
- **Single Stability**: `prefer-stable` only (removed `prefer-lowest`)

**Before**: 16 test jobs (2 OS × 2 PHP × 2 Laravel × 2 stability)
**After**: 1 test job (Ubuntu × PHP 8.4 × Laravel 12.x × stable)

###### Benefits

✅ **Faster CI/CD** - Reduced from 16 parallel jobs to 1 focused job
✅ **Lower Resource Usage** - Significant reduction in GitHub Actions minutes
✅ **Simplified Maintenance** - Single environment to monitor and debug
✅ **Production-Focused** - Tests the exact stack used in production

##### 📋 Changes

- Updated run-tests.yml:
  - Removed Windows OS from test matrix
  - Removed PHP 8.3 support from CI (package still supports it)
  - Removed Laravel 11.x from CI (package still compatible)
  - Removed `prefer-lowest` stability testing
  - Simplified matrix configuration
  

##### 🔧 Technical Details

**Workflow Configuration**:

```yaml
matrix:
  os: [ubuntu-latest]
  php: [8.4]
  laravel: [12.*]
  stability: [prefer-stable]








```
**Timeout**: 5 minutes
**Testbench**: 10.* (for Laravel 12)

##### ⚠️ Important Notes

- This change **only affects CI/CD testing**, not package compatibility
  
- The package remains compatible with:
  
  - PHP 8.3 and 8.4
  - Laravel 11.x and 12.x
  - Both Ubuntu and Windows environments
  
- Users can still use the package on any supported configuration
  
- Consider this a strategic focus on primary deployment targets
  

##### 🔄 Compatibility

- ✅ Fully backward compatible with v1.2.1
- ✅ No breaking changes
- ✅ No changes to package code or features
- ✅ Infrastructure-only update

##### 📦 Upgrade Instructions

No action required. This is a CI/CD-only update with no impact on package functionality.

```bash
composer update cleaniquecoders/eligify








```

---

**Full Changelog**: https://github.com/cleaniquecoders/eligify/compare/v1.2.1...v1.2.2

## Added eligify:make-mapping Command - 2025-10-29

### v1.2.1 - Model Mapping Generator - 2025-10-29

#### What's New

Version 1.2.1 introduces an intelligent code generation tool that simplifies the process of creating model mapping classes for data extraction in eligibility evaluations.

##### 🎨 Model Mapping Generator Command

A powerful new Artisan command that automatically generates mapping classes by analyzing your Eloquent models:

```bash
# Generate mapping for a model
php artisan eligify:make-mapping "App\Models\User"

# With custom name (kebab-case)
php artisan eligify:make-mapping "App\Models\Order" --name=premium-order

# With custom namespace
php artisan eligify:make-mapping "App\Models\Post" --namespace="App\CustomMappings"

# Force overwrite existing mapping
php artisan eligify:make-mapping "App\Models\User" --force









```
##### Key Features

###### 🔍 Intelligent Model Analysis

The command automatically analyzes your model to extract:

- **Database Fields** - Reads table schema to detect all available columns
- **Relationships** - Discovers model relationships via reflection
- **Computed Fields** - Identifies patterns for common computed fields
- **Timestamp Fields** - Maps timestamp columns to readable names

###### 🎯 Smart Field Mapping

Automatically generates mappings for common patterns:

```php
// Timestamp fields mapped to readable names
'created_at' => 'created_date',
'email_verified_at' => 'email_verified_timestamp',
'published_at' => 'published_timestamp',

// Relationship aggregations
'orders.count' => 'orders_count',
'orders.sum:amount' => 'total_order_amount',
'posts.avg:rating' => 'avg_post_rating',









```
###### ⚡ Computed Fields Generation

Automatically creates computed fields for:

- **Verification Status** - `is_verified` from `email_verified_at`
- **Approval Status** - `is_approved` from `approved_at`
- **Publication Status** - `is_published` from `published_at`
- **Count Checks** - `has_orders` from `orders_count`

###### 📝 Professional Code Output

Generates clean, well-documented mapping classes:

```php
<?php

namespace App\Eligify\Mappings;

use CleaniqueCoders\Eligify\Mappings\AbstractModelMapping;

/**
 * User Model Mapping
 *
 * Generated: 2025-10-29 10:30:00
 * Model: App\Models\User
 */
class UserMapping extends AbstractModelMapping
{
    public function getModelClass(): string
    {
        return 'App\Models\User';
    }

    protected array $fieldMappings = [
        'email_verified_at' => 'email_verified_timestamp',
        'created_at' => 'created_date',
    ];

    protected array $computedFields = [
        'is_verified' => null,
    ];

    public function __construct()
    {
        $this->computedFields = [
            // Check if email_verified_at is set
            'is_verified' => fn ($model) => !is_null($model->email_verified_at ?? null),
        ];
    }
}









```
##### Command Options

| Option | Description |
|--------|-------------|
| `model` | The fully qualified model class name (required) |
| `--name` | Custom name for the mapping class in kebab-case |
| `--namespace` | Custom namespace for the mapping class |
| `--force` | Overwrite existing mapping class without confirmation |

##### Usage Workflow

1. **Generate the Mapping**
   
   ```bash
   php artisan eligify:make-mapping "App\Models\User"
   
   
   
   
   
   
   
   
   
   ```
2. **Review and Customize**
   
   - Open the generated file in `app/Eligify/Mappings/UserMapping.php`
   - Customize field mappings, relationships, and computed fields as needed
   
3. **Register in Configuration**
   
   ```php
   // config/eligify.php
   'model_extraction' => [
       'model_mappings' => [
           'App\Models\User' => \App\Eligify\Mappings\UserMapping::class,
       ],
   ],
   
   
   
   
   
   
   
   
   
   ```
4. **Use in Evaluations**
   
   ```php
   $data = ModelDataExtractor::forModel(User::class)->extract($user);
   
   
   
   
   
   
   
   
   
   ```

##### What Gets Analyzed

###### Model Fields

- Reads database schema using Laravel's Schema facade
- Falls back to `$fillable` and `$guarded` if table doesn't exist
- Excludes sensitive fields (`password`, `remember_token`, etc.)

###### Relationships

- Scans public methods for relationship return types
- Detects: `hasMany`, `belongsTo`, `belongsToMany`, `hasOne`, etc.
- Generates common aggregations: `count`, `sum`, `avg`

###### Computed Fields

- Detects timestamp fields for verification status
- Creates boolean helpers for common patterns
- Generates count-based existence checks

##### Benefits

✅ **Saves Time** - Generates mapping classes in seconds instead of manual writing
✅ **Reduces Errors** - Automatically detects available fields and relationships
✅ **Standardizes** - Consistent structure across all mapping classes
✅ **Type-Safe** - Uses reflection and schema inspection for accuracy
✅ **Customizable** - Generated code is fully editable and extensible

##### New Files

- **Command**: `src/Commands/MakeMappingCommand.php` - Generator command
- **Stub**: `stubs/model-mapping.stub` - Template for generated classes
- **Tests**: `tests/Feature/Commands/MakeMappingCommandTest.php` - Comprehensive test coverage

##### Documentation

📖 New documentation added:

- Model mapping generation guide
- Example use cases and workflows
- Customization patterns
- Best practices for mapping classes

##### Compatibility

- ✅ Fully backward compatible with v1.2.0
- ✅ No breaking changes
- ✅ Works with existing mapping classes
- ✅ PHP 8.3+ and Laravel 11-12 support

##### Upgrade Instructions

```bash
# Update the package
composer update cleaniquecoders/eligify

# Start generating mappings
php artisan eligify:make-mapping "App\Models\User"









```
##### Testing

All new functionality is fully tested:

- ✅ Command execution and file generation
- ✅ Field and relationship detection
- ✅ Custom name and namespace options
- ✅ Force overwrite functionality
- ✅ Error handling for invalid models
- ✅ Computed field generation patterns

##### Next Steps

After generating your mapping class:

1. Review the generated field mappings
2. Add custom computed fields if needed
3. Configure relationship aggregations
4. Register in `config/eligify.php`
5. Use with `ModelDataExtractor::forModel()`


---

**Full Changelog**: [v1.2.0...v1.2.1](https://github.com/cleaniquecoders/eligify/compare/v1.2.0...v1.2.1)

## Eligitfy UI - 2025-10-28

### Release Notes - Eligify v1.2.0

**Released:** October 28, 2025
**Type:** Feature Enhancement Release

#### What's New

Version 1.2.0 brings powerful UI, developer tools, and performance optimization capabilities that make Eligify easier to use, test, and optimize for production workloads.

##### UI to Manage Your Criteria *& Rules

See [UI Setup Guide](https://github.com/cleaniquecoders/eligify/blob/main/docs/ui-setup-guide.md) for more details.

##### 🎮 Interactive Testing Playground

<img width="1235" height="949" alt="05-playground" src="https://github.com/user-attachments/assets/e3a62b12-918d-4de2-8d01-025c87b6a4a5" />
Test your eligibility criteria in real-time with sample data generation:
- **Smart Sample Generation** - Auto-generate test data from your rules with one click
- **Flexible Input** - Support for both flat (dot notation) and nested JSON structures
- **Visual Results** - See detailed pass/fail breakdown with execution times per rule
- **Quick Examples** - Pre-filled templates for common data types
```php
// The playground can auto-generate data like this:
{
  "applicant": {
    "income": 3010,
    "age": 28,
    "not_bankrupt": true
  }
}
```
##### 🎯 Dynamic Field Type Input
The rule editor now adapts intelligently based on field types:
- **Smart Input Types** - Number fields, date pickers, boolean toggles, text areas
- **Type-Aware Validation** - Automatic validation based on selected field type
- **Filtered Operators** - Only show relevant operators for each data type
- **Better UX** - Context-aware placeholders and help text
##### ⚡ Performance Benchmarking System
New built-in performance testing and optimization toolkit:
###### Benchmark Command
```bash
# Run all benchmarks with default settings (100 iterations)
php artisan eligify:benchmark

# Quick test with fewer iterations
php artisan eligify:benchmark --iterations=10

# Test specific scenarios
php artisan eligify:benchmark --type=simple    # Basic rules
php artisan eligify:benchmark --type=complex   # Complex evaluations
php artisan eligify:benchmark --type=batch     # Batch processing
php artisan eligify:benchmark --type=cache     # Cache performance

# JSON output for CI/CD pipelines
php artisan eligify:benchmark --format=json










```
###### Key Features

- **Multiple Test Scenarios** - Simple, complex, batch (100/1000 items), and cache performance tests
- **Comprehensive Metrics** - Average/min/max/median time, throughput (req/s), memory usage
- **Cache Analysis** - Compare performance with/without caching, shows improvement percentage
- **Production Safety** - Automatically prevents running in production environment
- **Color-Coded Output** - Visual performance indicators (green/yellow/red)
- **Automatic Cleanup** - Removes test data after benchmarking

###### Real-World Performance Metrics

Based on benchmark results:

| Scenario | Average Time | Throughput | Memory |
|----------|--------------|------------|--------|
| Simple (3 rules) | ~15-30 ms | 50-100 req/s | 2-4 MB |
| Complex (8 rules) | ~30-60 ms | 20-50 req/s | 4-8 MB |
| Batch (100 items) | ~500-1000 ms | 100-200 items/s | 10-20 MB |
| Batch (1000 items) | ~5-10 sec | 100-200 items/s | 30-50 MB |

**Cache Improvement:** 2-5x faster with caching enabled

###### New Classes

- **`BenchmarkCommand`** - Artisan command for running performance tests
- **`EligifyBenchmark`** - Core benchmarking class with measurement utilities

##### 📊 Performance Benchmarking Guide

Comprehensive documentation for optimizing your eligibility checks:

- **Benchmark Results** - Real-world performance metrics and throughput data
- **Testing Methodology** - Scripts and tools for measuring your system
- **Optimization Strategies** - Cache, batch processing, and database tips
- **Load Testing** - Guidelines for production performance monitoring
- **Profiling Tools** - Integration with Laravel Telescope, Blackfire, and XDebug

#### What This Means For You

##### For Development

- **Test faster** - Interactive playground reduces testing time from minutes to seconds
- **Catch issues earlier** - Type-aware validation prevents configuration errors
- **Optimize confidently** - Benchmark real performance before production deployment

##### For Production

- **Measure performance** - Understand your system's capacity and bottlenecks
- **Plan scaling** - Know your throughput limits for infrastructure planning
- **Monitor degradation** - Regular benchmarks detect performance regressions

#### Upgrade Guide

```bash
composer update cleaniquecoders/eligify
php artisan migrate










```
No breaking changes - fully backward compatible with v1.1.x

#### Documentation

📖 **Complete Documentation:** [https://github.com/cleaniquecoders/eligify/tree/main/docs](https://github.com/cleaniquecoders/eligify/tree/main/docs)

**New Guides:**

- [Playground Guide](https://github.com/cleaniquecoders/eligify/blob/main/docs/playground-guide.md) - Interactive testing tutorial
- [Dynamic Value Input](https://github.com/cleaniquecoders/eligify/blob/main/docs/dynamic-value-input.md) - Field type system reference
- [Performance Benchmarking](https://github.com/cleaniquecoders/eligify/blob/main/docs/performance-benchmarking.md) - Optimization strategies and benchmarking guide

#### Best Practices

##### Benchmarking in CI/CD

```bash
# Add to your CI pipeline
php artisan eligify:benchmark --iterations=1000 --format=json > benchmark-results.json










```
##### Before Production Deployment

```bash
# Run comprehensive benchmarks
php artisan eligify:benchmark --iterations=1000 --type=all

# Test expected production load
php artisan eligify:benchmark --type=batch --iterations=1000










```
##### Performance Optimization Tips

1. **Enable Caching** - 2-5x performance improvement for repeated evaluations
2. **Batch Processing** - Use `evaluateBatch()` for multiple entities
3. **Database Indexing** - Add indexes on frequently queried criteria slugs
4. **Rule Optimization** - Place high-priority rules first for early termination
5. **Monitor Memory** - Watch peak memory usage for large batch operations


---

**Previous Release:** [v1.1.0 - Model Data Extraction System](https://github.com/cleaniquecoders/eligify/blob/main/CHANGELOG.md#model-data-extraction-system---2025-10-28)

**Full Changelog:** [CHANGELOG.md](https://github.com/cleaniquecoders/eligify/blob/main/CHANGELOG.md)

## Model Data Extraction System - 2025-10-28

### Release Notes - Eligify v1.1.0

**Released:** November 2025
**Type:** Feature Release
**Tagline:** "Extract smarter. Evaluate faster. Decide better."

Eligify v1.1.0 introduces the **Model Data Extraction System** - a powerful new feature that transforms how you work with Laravel Eloquent models. This release makes it dramatically easier to evaluate eligibility by automatically extracting and transforming model data for rule evaluation.

#### 🎯 Model Data Extraction System

##### The Problem We Solved

Before v1.1.0, you had to manually prepare data for eligibility evaluation:

```php
// ❌ The old way - tedious and error-prone
$data = [
    'income' => $user->profile->annual_income,
    'credit_score' => $user->creditReport->score ?? 0,
    'active_loans' => $user->loans()->where('status', 'active')->count(),
    'debt_ratio' => $user->calculateDebtRatio(),
];

Eligify::criteria('Loan Approval')->evaluate($data);











```
##### The Solution: ModelDataExtractor

Now, with v1.1.0:

```php
// ✅ The new way - automatic, consistent, reusable
$data = ModelDataExtractor::forModel(User::class)->extract($user);

Eligify::criteria('Loan Approval')->evaluate($data);











```
#### ✨ What's New in v1.1.0

##### 🔄 Model Data Extraction System

Transform any Eloquent model into evaluation-ready data automatically.

###### Three Usage Patterns

**Pattern 1: Quick Extraction (Prototyping)**

```php
$data = (new ModelDataExtractor())->extract($user);











```
**Pattern 2: Custom Configuration (One-off)**

```php
$data = (new ModelDataExtractor())
    ->setFieldMappings(['annual_income' => 'income'])
    ->setComputedFields(['risk_score' => fn($m) => $m->calculateRisk()])
    ->extract($user);











```
**Pattern 3: Production-Ready (Recommended)**

```php
// Configure once in config/eligify.php
$data = ModelDataExtractor::forModel(User::class)->extract($user);











```
###### Key Features

✅ **Automatic Attribute Extraction** - All model attributes extracted automatically
✅ **Relationship Data** - Access nested relationships (e.g., `user.profile.income`)
✅ **Computed Fields** - Add dynamic calculations and business logic
✅ **Field Mapping** - Rename fields to match your rule definitions
✅ **Relationship Counts** - Automatic counts for relationships
✅ **Relationship Sums** - Sum numeric fields from relationships
✅ **Safe Navigation** - No errors if relationships don't exist
✅ **Custom Model Mappings** - Create reusable mapping classes
✅ **Type Casting** - Automatic type conversion for rule evaluation

##### 📦 New Components

###### ModelDataExtractor Class

The core extraction engine that transforms models into flat arrays:

```php
use CleaniqueCoders\Eligify\Support\ModelDataExtractor;

$extractor = new ModelDataExtractor([
    'include_relationships' => true,
    'max_relationship_depth' => 3,
    'exclude_hidden' => true,
    'cast_dates_to_timestamps' => true,
]);

// Extract with custom field mappings
$data = $extractor
    ->setFieldMappings([
        'email_verified_at' => 'verified_date',
        'created_at' => 'signup_date',
    ])
    ->setComputedFields([
        'account_age_days' => fn($model) =>
            now()->diffInDays($model->created_at),
        'is_premium' => fn($model) =>
            $model->subscription?->tier === 'premium',
    ])
    ->extract($user);











```
###### AbstractModelMapping Class

Create custom mapping classes for production use:

```php
use CleaniqueCoders\Eligify\Mappings\AbstractModelMapping;

class CustomerModelMapping extends AbstractModelMapping
{
    public function getModelClass(): string
    {
        return 'App\Models\Customer';
    }

    protected array $fieldMappings = [
        'email_verified_at' => 'verified_date',
        'created_at' => 'signup_date',
    ];

    public function __construct()
    {
        $this->computedFields = [
            'is_verified' => fn($m) => !is_null($m->email_verified_at),
            'total_orders' => fn($m) => $this->safeRelationshipCount($m, 'orders'),
            'lifetime_value' => fn($m) => $this->safeRelationshipSum($m, 'orders', 'total'),
            'customer_tier' => function($m) {
                $value = $this->safeRelationshipSum($m, 'orders', 'total');
                return match(true) {
                    $value >= 10000 => 'vip',
                    $value >= 5000 => 'gold',
                    $value >= 1000 => 'silver',
                    default => 'standard'
                };
            },
        ];
    }
}











```
###### ModelMapping Contract

Define custom model mappings with a standard interface:

```php
interface ModelMapping
{
    public function getModelClass(): string;
    public function getFieldMappings(): array;
    public function getRelationshipMappings(): array;
    public function getComputedFields(): array;
}











```
###### Built-in Model Mappings

**UserModelMapping** - Ready-to-use mapping for Laravel User models:

```php
// Automatically extracts:
// - email_verified_at → email_verified_timestamp
// - created_at → registration_date
// - is_verified → computed field (true/false)











```
##### 📚 New Documentation

Five comprehensive guides added (1,500+ lines total):

1. **[model-data-extraction.md](https://github.com/cleaniquecoders/eligify/blob/main/docs/model-data-extraction.md)** (367 lines)
   
   - Complete usage guide with decision flowcharts
   - Method comparison and best practices
   - Real-world examples and patterns
   
2. **[model-mappings.md](https://github.com/cleaniquecoders/eligify/blob/main/docs/model-mappings.md)** (313 lines)
   
   - Creating custom model mappings
   - Helper methods reference
   - Advanced techniques and patterns
   
3. **[quick-reference-model-extraction.md](https://github.com/cleaniquecoders/eligify/blob/main/docs/quick-reference-model-extraction.md)** (144 lines)
   
   - TL;DR quick reference guide
   - Method comparison card
   - Common use cases
   
4. **[model-data-extractor-architecture.md](https://github.com/cleaniquecoders/eligify/blob/main/docs/model-data-extractor-architecture.md)** (303 lines)
   
   - System architecture overview
   - Data flow diagrams
   - Integration patterns
   
5. **Updated [usage-guide.md](https://github.com/cleaniquecoders/eligify/blob/main/docs/usage-guide.md)**
   
   - Integrated model extraction examples
   - End-to-end evaluation workflows
   

#### 🚀 Real-World Examples

##### Example 1: Loan Approval

```php
// Create custom mapping
class LoanApplicationMapping extends AbstractModelMapping
{
    public function getModelClass(): string
    {
        return 'App\Models\LoanApplication';
    }

    public function __construct()
    {
        $this->fieldMappings = [
            'annual_income' => 'income',
        ];

        $this->computedFields = [
            'credit_score' => fn($m) => $m->applicant->creditScore->score ?? 0,
            'active_loans' => fn($m) => $this->safeRelationshipCount($m->applicant, 'loans', fn($q) =>
                $q->where('status', 'active')
            ),
            'debt_to_income_ratio' => fn($m, $data) =>
                $m->total_debt / max($data['income'], 1),
        ];
    }
}

// Register in config/eligify.php
'model_mappings' => [
    'App\Models\LoanApplication' => \App\Eligify\Mappings\LoanApplicationMapping::class,
],

// Use in evaluation
$application = LoanApplication::find(1);
$data = ModelDataExtractor::forModel(LoanApplication::class)->extract($application);

$result = Eligify::criteria('Loan Approval')
    ->addRule('income', '>=', 3000)
    ->addRule('credit_score', '>=', 650)
    ->addRule('active_loans', '<=', 2)
    ->addRule('debt_to_income_ratio', '<=', 0.4)
    ->evaluate($data);











```
##### Example 2: Scholarship Eligibility

```php
class StudentMapping extends AbstractModelMapping
{
    public function getModelClass(): string
    {
        return 'App\Models\Student';
    }

    public function __construct()
    {
        $this->computedFields = [
            'gpa' => fn($m) => $m->grades()->avg('grade') ?? 0,
            'attendance_rate' => fn($m) => $m->calculateAttendanceRate(),
            'extracurricular_count' => fn($m) => $this->safeRelationshipCount($m, 'activities'),
            'has_financial_need' => fn($m) => $m->family_income < 30000,
            'academic_standing' => fn($m) => $m->getAcademicStanding(),
        ];
    }
}

// Extract and evaluate
$student = Student::find(1);
$data = ModelDataExtractor::forModel(Student::class)->extract($student);

$result = Eligify::criteria('Scholarship Eligibility')
    ->addRule('gpa', '>=', 3.5)
    ->addRule('attendance_rate', '>=', 0.9)
    ->addRule('extracurricular_count', '>=', 2)
    ->addRule('has_financial_need', '==', true)
    ->evaluate($data);











```
##### Example 3: E-commerce VIP Tier

```php
class CustomerMapping extends AbstractModelMapping
{
    public function getModelClass(): string
    {
        return 'App\Models\Customer';
    }

    public function __construct()
    {
        $this->computedFields = [
            'total_orders' => fn($m) => $this->safeRelationshipCount($m, 'orders'),
            'lifetime_value' => fn($m) => $this->safeRelationshipSum($m, 'orders', 'total'),
            'avg_order_value' => fn($m, $data) =>
                $data['total_orders'] > 0 ? $data['lifetime_value'] / $data['total_orders'] : 0,
            'account_age_months' => fn($m) =>
                $m->created_at->diffInMonths(now()),
            'return_rate' => fn($m) => $m->calculateReturnRate(),
        ];
    }
}

// Evaluate VIP eligibility
$customer = Customer::find(1);
$data = ModelDataExtractor::forModel(Customer::class)->extract($customer);

$result = Eligify::criteria('Vip Tier')
    ->addRule('total_orders', '>=', 20)
    ->addRule('lifetime_value', '>=', 10000)
    ->addRule('avg_order_value', '>=', 200)
    ->addRule('account_age_months', '>=', 12)
    ->addRule('return_rate', '<=', 0.05)
    ->setScoringMethod(ScoringMethod::WEIGHTED_AVERAGE)
    ->evaluate($data);











```
#### 🔧 Configuration Updates

New configuration section in `config/eligify.php`:

```php
return [
    // ... existing config

    /*
    |--------------------------------------------------------------------------
    | Model Data Extraction
    |--------------------------------------------------------------------------
    |
    | Configure how model data is extracted for eligibility evaluation
    |
    */
    'model_extraction' => [
        // Registered model mappings
        'model_mappings' => [
            'App\Models\User' => \CleaniqueCoders\Eligify\Mappings\UserModelMapping::class,
            // Add your custom mappings here
        ],

        // Default extraction options
        'defaults' => [
            'include_relationships' => true,
            'max_relationship_depth' => 2,
            'exclude_hidden' => true,
            'exclude_guarded' => false,
            'cast_dates_to_timestamps' => true,
            'flatten_json_fields' => true,
        ],

        // Performance settings
        'performance' => [
            'cache_extracted_data' => false,
            'cache_ttl' => 3600, // seconds
            'lazy_load_relationships' => true,
        ],
    ],
];











```
#### 🔄 Migration Guide

##### From v1.0.x to v1.1.0

This is a **minor version release** with **100% backward compatibility**. All existing code continues to work without changes.

**Optional: Add Model Data Extraction**

1. **Publish new config section:**

```bash
php artisan vendor:publish --tag="eligify-config" --force











```
2. **Create your first model mapping:**

```php
php artisan make:eligify-mapping CustomerMapping











```
3. **Register in config:**

```php
// config/eligify.php
'model_mappings' => [
    'App\Models\Customer' => \App\Eligify\Mappings\CustomerMapping::class,
],











```
4. **Start using it:**

```php
$data = ModelDataExtractor::forModel(Customer::class)->extract($customer);
Eligify::criteria('vip_program')->evaluate($data);











```
#### 📦 Installation & Upgrade

**New Installation:**

```bash
composer require cleaniquecoders/eligify:^1.1











```
**Upgrade from v1.0.x:**

```bash
composer update cleaniquecoders/eligify
php artisan vendor:publish --tag="eligify-config" --force
php artisan optimize:clear











```
#### 🧪 Testing

All **95+ tests** passing with new test coverage:

- ✅ Model data extraction with various configurations
- ✅ Field mapping transformations
- ✅ Relationship data extraction (nested up to 3 levels)
- ✅ Computed field calculations
- ✅ Custom model mapping classes
- ✅ Safe relationship navigation (no errors on missing relations)
- ✅ Type casting and data normalization

**New test helpers:**

```php
// In your tests
use CleaniqueCoders\Eligify\Support\ModelDataExtractor;

$data = ModelDataExtractor::forModel(User::class)->extract($user);
$this->assertArrayHasKey('is_verified', $data);
$this->assertTrue($data['is_verified']);











```
#### 🎯 Use Cases Enhanced by v1.1.0

##### Before v1.1.0 → After v1.1.0

**Loan Approval:**

- ❌ Manual data preparation (10-15 lines)
- ✅ Automatic extraction (1 line)

**Scholarship Eligibility:**

- ❌ Complex queries and calculations
- ✅ Computed fields in mapping class

**Customer Tier Evaluation:**

- ❌ Repeated relationship queries
- ✅ Cached relationship counts/sums

**Multi-Model Evaluations:**

- ❌ Different extraction code per model
- ✅ Consistent mapping classes

#### 📝 Full Changelog

See all changes: [v1.0.1...v1.1.0](https://github.com/cleaniquecoders/eligify/compare/v1.0.1...v1.1.0)

## Update documentation - 2025-10-27

### Release Notes - Eligify v1.0.1

**Released:** October 27, 2025
**Type:** Documentation Release

Complete documentation overhaul with **4,600+ lines** of guides and **200+ code examples**. No code changes—purely better docs to help you ship faster.

#### ✨ What's New

##### Documentation Added

- **📖 Main README** - Quick start, core concepts, troubleshooting
- **⚙️ Configuration Guide** - All config options, scoring methods, presets
- **🎯 Usage Guide** - Basic to advanced patterns with examples
- **🗄️ Migration Guide** - Complete database schema and customization
- **💻 CLI Commands** - Full reference for 10+ Artisan commands
- **🚀 Advanced Features** - Custom operators, scoring, workflows, events
- **🔐 Policy Integration** - Laravel authorization patterns

##### Key Coverage

✅ **16 operators** explained with examples
✅ **5 scoring methods** (weighted, pass/fail, sum, average, percentage)
✅ **10 real-world use cases** (finance, education, HR, insurance, e-commerce, government, SaaS)
✅ **Batch operations** and performance optimization
✅ **Multi-tenancy** patterns
✅ **Event-driven workflows**
✅ **Custom implementations**


---

**Full Changelog**: [v1.0.0...v1.0.1](https://github.com/cleaniquecoders/eligify/compare/v1.0.0...v1.0.1)

## First Release - 2025-10-27

1.0.0 Release Notes

**Tagline:** "Define criteria. Enforce rules. Decide eligibility."

We're thrilled to announce the first stable release of Eligify - a powerful Laravel package that transforms eligibility decisions into data-driven, traceable, and automatable processes.


---

### 🌟 What is Eligify?

Eligify is a flexible rule and criteria engine for Laravel that helps you determine entity eligibility for persons, applications, transactions, and more. Whether you're building loan approval systems, scholarship qualification tools, or access control mechanisms, Eligify provides the foundation for intelligent decision-making.

#### Key Use Cases

- **Finance**: Loan approval, credit scoring, risk assessment
- **Education**: Scholarship eligibility, admission qualification
- **HR**: Candidate screening, promotion qualification
- **Government**: Aid distribution, program qualification
- **E-commerce**: Discount eligibility, loyalty tier determination


---

### ✨ Headline Features

#### 🎯 Intuitive Fluent API

```php
Eligify::criteria('Loan Approval')
    ->addRule('income', '>=', 3000)
    ->addRule('credit_score', '>=', 650)
    ->addRule('active_loans', '<=', 2)
    ->onPass(fn($applicant) => $applicant->approveLoan())
    ->onFail(fn($applicant) => $applicant->notifyRejection())
    ->evaluate($applicant);












```
#### 🧠 Advanced Rule Engine

- **Complex Logic**: AND/OR/NAND/NOR/XOR/MAJORITY operators for nested conditions
- **Rule Dependencies**: Conditional rule execution based on other rules
- **Group Combinations**: Multiple rule groups with configurable combination logic
- **Execution Plans**: Optimized rule evaluation with smart dependency resolution
- **Weighted Scoring**: Sophisticated scoring algorithms with customizable weights
- **Threshold Decisions**: Automatic decision-making based on score thresholds

#### 🔄 Powerful Workflow System

- **Advanced Callbacks**: `onPass()`, `onFail()`, `beforeEvaluation()`, `afterEvaluation()`
- **Score-Based Triggers**: `onExcellent()`, `onGood()`, `onScoreRange()`
- **Conditional Execution**: `onCondition()` for complex workflow logic
- **Async Support**: Background processing with `onPassAsync()`, `onFailAsync()`
- **Batch Processing**: Efficient evaluation of multiple entities
- **Error Handling**: Robust error recovery and timeout management

#### 📊 Comprehensive Audit System

- **Automatic Logging**: Every evaluation, rule change, and workflow execution tracked
- **Advanced Queries**: Filter by event type, user, date range, and search terms
- **Event Listeners**: Integrated with Laravel events for seamless logging
- **Model Observers**: Automatic CRUD audit for criteria and rules
- **Export Capabilities**: CSV and JSON export for compliance and analysis
- **Auto-Cleanup**: Configurable retention policies with scheduled maintenance

#### 🛠️ Laravel Integration

- **Policy Trait**: `HasEligibility` trait for seamless Laravel policy integration
- **Artisan Commands**: Complete CLI suite for criteria management and evaluation
- **Event System**: Native Laravel events for ecosystem integration
- **Database Support**: Full Eloquent integration with optimized queries
- **Factory Support**: Comprehensive testing factories included


---

### 📦 Core Components

#### Models & Database

- ✅ `Criteria` - Define eligibility criteria sets
- ✅ `Rule` - Individual evaluation rules with operators and priorities
- ✅ `Evaluation` - Evaluation results with scores and decisions
- ✅ `AuditLog` - Comprehensive audit trail with metadata

#### Enums

- ✅ `RuleOperator` - 15+ comparison operators (>=, <=, ==, in, between, etc.)
- ✅ `FieldType` - Type validation (string, integer, float, boolean, array, etc.)
- ✅ `RulePriority` - Rule execution priority (low, normal, high, critical)
- ✅ `ScoringMethod` - Scoring algorithms (weighted average, pass/fail, sum, etc.)

#### Engine Components

- ✅ `RuleEngine` - Core evaluation engine with sophisticated scoring
- ✅ `CriteriaBuilder` - Fluent interface for building criteria
- ✅ `WorkflowManager` - Advanced workflow execution pipeline
- ✅ `AuditLogger` - Comprehensive audit logging system


---

### 🚀 Features in v1.0.0

#### Advanced Rule Engine

```php
Eligify::criteria('complex_approval')
    ->addRuleGroup('financial', 'AND')
        ->addRule('income', '>=', 50000, priority: 'high', weight: 0.4)
        ->addRule('debt_ratio', '<=', 0.3, weight: 0.3)
    ->endGroup()
    ->addRuleGroup('credit', 'OR')
        ->addRule('credit_score', '>=', 700, weight: 0.3)
        ->addRule('payment_history', '==', 'excellent')
    ->endGroup()
    ->setCombinationLogic('MAJORITY')
    ->setDecisionThresholds([
        'approved' => 80,
        'review' => 60,
        'rejected' => 0
    ])
    ->evaluate($applicant);












```
#### Policy Integration

```php
class LoanPolicy
{
    use HasEligibility;

    public function approve(User $user, Loan $loan)
    {
        return $this->checkEligibility(
            'loan_approval',
            $loan,
            fn($l) => [
                'income' => $l->applicant->income,
                'credit_score' => $l->applicant->credit_score,
            ]
        );
    }
}













```
#### Artisan Commands

```bash
# Manage criteria
php artisan eligify:criteria create loan_approval
php artisan eligify:criteria list
php artisan eligify:criteria export loan_approval

# Evaluate entities
php artisan eligify:evaluate loan_approval --inline='{"income":5000}'
php artisan eligify:evaluate loan_approval --model="App\Models\Loan:1"

# Audit management
php artisan eligify:audit-query --event=evaluation_completed
php artisan eligify:cleanup-audit --days=90













```

---

### 🔧 Requirements

- **PHP**: 8.3 or 8.4
- **Laravel**: 11.x or 12.x
- **Database**: MySQL 8.0+, PostgreSQL 12+, SQLite 3.35+


---

### 📦 Installation

```bash
composer require cleaniquecoders/eligify












```
```bash
php artisan vendor:publish --tag="eligify-migrations"
php artisan vendor:publish --tag="eligify-config"
php artisan migrate













```

---

### 🎯 What's Next?

#### Planned for v1.1.0

- REST API endpoints for remote evaluation
- Visual rule builder UI
- Machine learning integration for dynamic rules
- Real-time evaluation via WebSockets
- Multi-tenancy support
- Enhanced performance optimization


---

*Eligify - Making eligibility decisions simple, transparent, and powerful.*
