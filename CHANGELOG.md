# Changelog

All notable changes to `eligify` will be documented in this file.

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
