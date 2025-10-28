# Changelog

All notable changes to `eligify` will be documented in this file.

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

Eligify::criteria('loan_approval')->evaluate($data);


```
##### The Solution: ModelDataExtractor

Now, with v1.1.0:

```php
// ✅ The new way - automatic, consistent, reusable
$data = ModelDataExtractor::forModel(User::class)->extract($user);

Eligify::criteria('loan_approval')->evaluate($data);


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

$result = Eligify::criteria('loan_approval')
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

$result = Eligify::criteria('scholarship_eligibility')
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

$result = Eligify::criteria('vip_tier')
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
Eligify::criteria('loan_approval')
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
