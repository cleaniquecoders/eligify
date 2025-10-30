# Eligify Documentation# Eligify Documentation

**Tagline:** "Define criteria. Enforce rules. Decide eligibility."**Tagline:** "Define criteria. Enforce rules. Decide eligibility."

Eligify is a Laravel package that provides a flexible rule and criteria engine for determining entity eligibility. It makes eligibility decisions **data-driven**, **traceable**, and **automatable**.Eligify is a Laravel package that provides a flexible rule and criteria engine for determining entity eligibility. It makes eligibility decisions **data-driven**, **traceable**, and **automatable**.

---## Table of Contents

## 📚 Documentation Structure- [Installation](#installation)

- [Quick Start](#quick-start)

This documentation is organized into 15 sections, designed to take you from beginner to expert:- [Core Concepts](#core-concepts)

- [UI Dashboard](ui-setup-guide.md) 🎨

### 🚀 Getting Started  - [Dynamic Field Selection](dynamic-field-selection.md) 🎯

- [Model Mapping & Data Extraction](#model-mapping--data-extraction)

**[01. Getting Started](01-getting-started/)** - Installation, quick start, and core concepts  - [**Model Mapping Guide**](model-mapping-guide.md) � **START HERE**

- [Relationship Mapping Cheatsheet](relationship-mapping-cheatsheet.md) 🔴 **MUST READ**

### 🏗️ Understanding Eligify  - [Mapper Generation Guide](mapper-generation-guide.md) 🔨 Auto-generate mappings

- [Snapshot Data Object](snapshot.md) 📦 Data snapshots

**[02. Architecture](02-architecture/)** - Design patterns and package structure  - [Configuration](#configuration)

**[03. Core Features](03-core-features/)** - Criteria builder, rules engine, evaluation, and workflows  - [Configuration Guide](configuration.md) ⚙️

- [Environment Variables](environment-variables.md) 🔧

### 📊 Working with Data- [Database Structure](#database-structure)

- [Usage Guide](#usage-guide)

**[04. Data Management](04-data-management/)** - Model mapping, snapshots, data extraction- [Advanced Features](#advanced-features)

- [CLI Commands](#cli-commands)

- [Model Mapping](04-data-management/model-mapping/) - Transform models for evaluation- [Policy Integration](#policy-integration)

- [Snapshots](04-data-management/snapshot/) - Point-in-time data capture- [Testing](#testing)

- [Real-World Examples](#real-world-examples)

### 🎨 User Interface

## Installation

**[05. User Interface](05-user-interface/)** - Web dashboard, playground, and dynamic fields

Install the package via Composer:

### ⚙️ Configuration

```bash

**[06. Configuration](06-configuration/)** - Settings, operators, environment variablescomposer require cleaniquecoders/eligify

```

### 🔥 Advanced Topics

Publish and run migrations:

**[07. Advanced Features](07-advanced-features/)** - Caching, custom operators, policy integration

**[08. CLI Commands](08-cli/)** - Command-line tools and automation  ```bash

**[09. Testing](09-testing/)** - Unit tests, integration tests, performance benchmarksphp artisan vendor:publish --tag="eligify-migrations"

php artisan migrate

### 🚀 Production```

**[10. Deployment](10-deployment/)** - Production setup and optimization  Publish configuration file:

**[11. Security](11-security/)** - Best practices and authorization

**[12. Migration](12-migration/)** - Upgrade guides and breaking changes```bash

php artisan vendor:publish --tag="eligify-config"

### 📖 Learning & Reference```

**[13. Examples](13-examples/)** - Real-world use cases and patterns  ## Quick Start

**[14. API Reference](14-reference/)** - Complete API documentation

**[15. Appendix](15-appendix/)** - Glossary, FAQ, and resourcesHere's a simple loan approval example to get you started:

---```php

use CleaniqueCoders\Eligify\Facades\Eligify;

## 🎯 Quick Navigation

// Step 1: Define criteria

### I want to...$criteria = Eligify::criteria('loan_approval')

    ->description('Standard Personal Loan Approval')

- **Get started quickly** → [Quick Start Guide](01-getting-started/README.md)    ->addRule('credit_score', '>=', 650, 30)      // 30% weight

- **Understand the architecture** → [Architecture Overview](02-architecture/README.md)    ->addRule('annual_income', '>=', 30000, 25)   // 25% weight

- **Build my first criteria** → [Core Features](03-core-features/README.md)    ->addRule('debt_to_income_ratio', '<=', 43, 20) // 20% weight

- **Map my models** → [Model Mapping Guide](04-data-management/model-mapping/getting-started.md)    ->addRule('employment_status', 'in', ['employed', 'self-employed'], 15)

- **Use the web interface** → [UI Setup](05-user-interface/setup.md)    ->addRule('active_loans', '<=', 3, 10)

- **Configure advanced features** → [Advanced Features](07-advanced-features/README.md)    ->passThreshold(70)

- **Deploy to production** → [Production Guide](10-deployment/production.md)    ->onPass(function($applicant, $result) {

- **See real examples** → [Examples](13-examples/README.md)        echo "✅ Loan Approved! Score: {$result['score']}%";

- **Look up an API** → [API Reference](14-reference/README.md)    })

    ->onFail(function($applicant, $result) {

---        echo "❌ Loan Denied. Score: {$result['score']}%";

    })

## ⚡ Quick Example    ->save()

```php// Step 2: Evaluate applicant

use CleaniqueCoders\Eligify\Facades\Eligify;$result = Eligify::evaluate('loan_approval', [

    'credit_score' => 720,

// Define eligibility criteria    'annual_income' => 55000,

$result = Eligify::criteria('loan_approval')    'employment_status' => 'employed',

    ->addRule('income', '>=', 3000)    'debt_to_income_ratio' => 35,

    ->addRule('credit_score', '>=', 650)    'active_loans' => 2,

    ->addRule('employment_months', '>=', 6)]);

    ->scoringMethod('weighted')```

    ->onPass(fn($applicant) => $applicant->approveLoan())

    ->onFail(fn($applicant) => $applicant->rejectLoan())**Result structure:**

    ->evaluate($applicant);

```php

// Check results[

if ($result->passed()) {    'passed' => true,

    echo "Loan approved! Score: {$result->score()}";    'score' => 85,

}    'decision' => 'Approved',

```    'failed_rules' => [],

    'criteria_id' => 1,

---    'evaluation_id' => 123,

]

## 🎓 Learning Paths```



### For New Users### Using the Web UI (Optional)



1. [Installation & Setup](01-getting-started/README.md)Eligify includes an optional dashboard for visual management:

2. [Core Concepts](03-core-features/README.md)

3. [Basic Examples](13-examples/README.md)```bash

4. [Model Mapping](04-data-management/model-mapping/)# Enable in .env

ELIGIFY_UI_ENABLED=true

### For Integrators

# Access at

1. [Architecture Overview](02-architecture/README.md)http://your-app.test/eligify

2. [Data Management](04-data-management/README.md)```

3. [Configuration](06-configuration/README.md)

4. [Advanced Features](07-advanced-features/README.md)![Dashboard Preview](../screenshots/01-dashboard-overview.png)



### For Operations**Features:**

- 📊 Visual criteria builder

1. [Testing Guide](09-testing/README.md)- 🎮 Interactive testing playground

2. [Deployment](10-deployment/README.md)- 🔍 Audit log explorer

3. [Security](11-security/README.md)- ⚖️ Rule library management

4. [Migration & Upgrades](12-migration/README.md)

> 📖 **Complete setup guide:** [UI Setup Guide](ui-setup-guide.md)

---

## Core Concepts

## 📦 Installation

### 1. Criteria

Install the package via Composer:

A **Criteria** is a named collection of rules that define eligibility requirements for a specific use case (e.g., "loan_approval", "scholarship_eligibility").

```bash

composer require cleaniquecoders/eligify**Key properties:**

```

- `name` - Human-readable name

Publish and run migrations:- `slug` - Unique identifier (auto-generated from name)

- `description` - Detailed explanation of the criteria

```bash- `is_active` - Enable/disable criteria

php artisan vendor:publish --tag="eligify-migrations"- `pass_threshold` - Minimum score required (0-100, default: 65)

php artisan migrate- `meta` - Additional metadata (JSON)

```

**Example:**

Publish configuration file:

```php

```bash$criteria = Eligify::criteria('scholarship_eligibility')

php artisan vendor:publish --tag="eligify-config"    ->description('Merit-based scholarship qualification')

```    ->passThreshold(80)

    ->save();

For complete installation instructions, see the [Getting Started Guide](01-getting-started/README.md).```



---### 2. Rules



## 🎯 Key FeaturesA **Rule** is a single condition that evaluates a field against a value using an operator.



- 🧱 **Criteria Builder** - Define what makes something eligible**Components:**

- ⚖️ **Rule Engine** - Apply logic and conditions dynamically

- 🎯 **Evaluator** - Run real-time eligibility checks- `field` - Data field to evaluate (e.g., 'credit_score')

- 🔄 **Workflow Manager** - Trigger actions when eligibility passes or fails- `operator` - Comparison operator (>=, <=, ==, !=, in, between, etc.)

- 🧾 **Audit Log** - Record every decision for transparency- `value` - Expected value or threshold

- 🧩 **Integrations** - Works with Laravel models, policies, and events- `weight` - Importance factor (default: 1, range: 1-10)

- 📦 **Snapshots** - Preserve state at evaluation time- `order` - Execution priority (default: 0)

- 🗺️ **Model Mapping** - Transform complex data structures- `is_active` - Enable/disable rule

- 🎨 **UI Dashboard** - Visual criteria management

- ⚡ **Performance** - Built-in caching and optimization**Example:**



---```php

$criteria->addRule('gpa', '>=', 3.5, 8)           // Weight: 8

## 🏢 Use Cases         ->addRule('age', 'between', [16, 25], 5)  // Weight: 5

         ->addRule('country', 'in', ['US', 'CA'], 3); // Weight: 3

- **Finance**: Loan approval, credit scoring```

- **Education**: Scholarship/admission eligibility

- **HR**: Candidate screening, promotion qualification### 3. Evaluation

- **Government**: Aid distribution, program qualification

- **E-commerce**: Discount/loyalty eligibilityAn **Evaluation** is a recorded assessment of an entity against criteria.

- **Insurance**: Policy underwriting

- **SaaS**: Plan upgrade eligibility**Stored data:**

- **Real Estate**: Rental application screening

- `criteria_id` - Which criteria was evaluated

See the [Examples section](13-examples/README.md) for detailed implementations.- `evaluable_type/id` - Polymorphic relation to the entity

- `passed` - Boolean result

---- `score` - Calculated score (0-100)

- `failed_rules` - Array of failed rule IDs

## 📖 Complete Table of Contents- `rule_results` - Detailed results per rule

- `decision` - Human-readable decision

<details>- `context` - Input data provided

<summary><strong>01. Getting Started</strong></summary>- `evaluated_at` - Timestamp



- [Overview](01-getting-started/README.md)**Example:**

- [Installation](01-getting-started/installation.md)

- [Quick Start](01-getting-started/quick-start.md)```php

- [Usage Guide](01-getting-started/usage-guide.md)// Automatic evaluation recording

- [Core Concepts](01-getting-started/core-concepts.md)$result = Eligify::evaluate('loan_approval', $data);



</details>// Or evaluate against a model

$user = User::find(1);

<details>$result = Eligify::evaluateModel($user, 'premium_membership');

<summary><strong>02. Architecture</strong></summary>```



- [Overview](02-architecture/README.md)### 4. Operators

- [Design Patterns](02-architecture/design-patterns.md)

- [Package Structure](02-architecture/package-structure.md)Eligify supports 16 operators across different data types:

- [Request Lifecycle](02-architecture/request-lifecycle.md)

- [Extensibility](02-architecture/extensibility.md)#### Numeric Comparisons



</details>| Operator | Description | Example |

|----------|-------------|---------|

<details>| `==` | Equal to | `->addRule('status', '==', 'active')` |

<summary><strong>03. Core Features</strong></summary>| `!=` | Not equal to | `->addRule('status', '!=', 'banned')` |

| `>` | Greater than | `->addRule('age', '>', 18)` |

- [Overview](03-core-features/README.md)| `>=` | Greater than or equal | `->addRule('score', '>=', 70)` |

- [Criteria Builder](03-core-features/criteria-builder.md)| `<` | Less than | `->addRule('debt', '<', 10000)` |

- [Rule Engine](03-core-features/rule-engine.md)| `<=` | Less than or equal | `->addRule('ratio', '<=', 0.43)` |

- [Scoring Methods](03-core-features/scoring-methods.md)

- [Evaluation Engine](03-core-features/evaluation-engine.md)#### Array Operations

- [Workflow Callbacks](03-core-features/workflow-callbacks.md)

| Operator | Description | Example |

</details>|----------|-------------|---------|

| `in` | Value in array | `->addRule('tier', 'in', ['gold', 'platinum'])` |

<details>| `not_in` | Value not in array | `->addRule('status', 'not_in', ['banned', 'suspended'])` |

<summary><strong>04. Data Management</strong></summary>

#### Range Operations

- [Overview](04-data-management/README.md)

- [Model Mapping](04-data-management/model-mapping/)| Operator | Description | Example |

  - [Getting Started](04-data-management/model-mapping/getting-started.md)|----------|-------------|---------|

  - [Patterns](04-data-management/model-mapping/patterns.md)| `between` | Within range (inclusive) | `->addRule('age', 'between', [18, 65])` |

  - [Relationship Mapping](04-data-management/model-mapping/relationship-mapping.md)| `not_between` | Outside range | `->addRule('risk_score', 'not_between', [80, 100])` |

  - [Generator](04-data-management/model-mapping/generator.md)

- [Snapshots](04-data-management/snapshot/)#### String Operations

  - [Usage](04-data-management/snapshot/usage.md)

  - [Data Structure](04-data-management/snapshot/data-structure.md)| Operator | Description | Example |

- [Data Extractor](04-data-management/extractor.md)|----------|-------------|---------|

- [Dynamic Values](04-data-management/dynamic-values.md)| `contains` | String contains substring | `->addRule('email', 'contains', '@company.com')` |

| `starts_with` | String starts with | `->addRule('code', 'starts_with', 'ACC')` |

</details>| `ends_with` | String ends with | `->addRule('email', 'ends_with', '.edu')` |



<details>#### Existence Operations

<summary><strong>05. User Interface</strong></summary>

| Operator | Description | Example |

- [Overview](05-user-interface/README.md)|----------|-------------|---------|

- [Setup Guide](05-user-interface/setup.md)| `exists` | Field has a value | `->addRule('profile_photo', 'exists', true)` |

- [Features](05-user-interface/features.md)| `not_exists` | Field is null/empty | `->addRule('deleted_at', 'not_exists', true)` |

- [Dynamic Fields](05-user-interface/dynamic-fields.md)

- [Playground](05-user-interface/playground.md)#### Pattern Matching

- [Customization](05-user-interface/customization.md)

| Operator | Description | Example |

</details>|----------|-------------|---------|

| `regex` | Regex pattern match | `->addRule('phone', 'regex', '/^\+?[1-9]\d{1,14}$/')` |

<details>

<summary><strong>06. Configuration</strong></summary>### 5. Scoring Methods



- [Overview](06-configuration/README.md)Configure how scores are calculated using the `ScoringMethod` enum:

- [Reference Guide](06-configuration/reference.md)

- [Environment Variables](06-configuration/environment-variables.md)```php

- [Operators](06-configuration/operators.md)use CleaniqueCoders\Eligify\Enums\ScoringMethod;

- [Presets](06-configuration/presets.md)

- [Environments](06-configuration/environments.md)// Weighted average based on rule weights (default)

$criteria->scoringMethod(ScoringMethod::WEIGHTED);

</details>

// Binary pass/fail (100 if all pass, 0 if any fail)

<details>$criteria->scoringMethod(ScoringMethod::PASS_FAIL);

<summary><strong>07. Advanced Features</strong></summary>

// Sum of weights for passed rules

- [Overview](07-advanced-features/README.md)$criteria->scoringMethod(ScoringMethod::SUM);

- [Caching](07-advanced-features/caching/)

  - [Implementation](07-advanced-features/caching/implementation.md)// Simple average (all rules equal weight)

  - [Strategies](07-advanced-features/caching/strategies.md)$criteria->scoringMethod(ScoringMethod::AVERAGE);

  - [Redis Setup](07-advanced-features/caching/redis-setup.md)

- [Policy Integration](07-advanced-features/policy-integration.md)// Percentage of passed rules

- [Custom Operators](07-advanced-features/custom-operators.md)$criteria->scoringMethod(ScoringMethod::PERCENTAGE);

- [Custom Scoring](07-advanced-features/custom-scoring.md)```

- [Events & Listeners](07-advanced-features/events-listeners.md)

- [Audit Logging](07-advanced-features/audit-logging.md)### 6. Workflow Callbacks



</details>Define actions to execute based on evaluation results:



<details>```php

<summary><strong>08. CLI Commands</strong></summary>$criteria->onPass(function($data, $result) {

    // Execute when evaluation passes

- [Overview](08-cli/README.md)    SendApprovalEmail::dispatch($data);

- [Commands Reference](08-cli/commands.md)})

- [Playground CLI](08-cli/playground-cli.md)->onFail(function($data, $result) {

- [Cache Management](08-cli/cache-management.md)    // Execute when evaluation fails

    SendRejectionEmail::dispatch($data, $result['failed_rules']);

</details>})

->onExcellent(function($data, $result) {

<details>    // Execute when score >= 90

<summary><strong>09. Testing</strong></summary>    OfferPremiumBenefits::dispatch($data);

})

- [Overview](09-testing/README.md)->onGood(function($data, $result) {

- [Unit Testing](09-testing/unit-testing.md)    // Execute when 70 <= score < 90

- [Integration Testing](09-testing/integration-testing.md)    OfferStandardBenefits::dispatch($data);

- [Performance Testing](09-testing/performance-testing.md)});

- [Test Helpers](09-testing/test-helpers.md)```



</details>## Model Mapping & Data Extraction



<details>Eligify provides a powerful system to extract and transform data from Eloquent models for eligibility evaluation.

<summary><strong>10. Deployment</strong></summary>

### Quick Example

- [Overview](10-deployment/README.md)

- [Production Guide](10-deployment/production.md)```php

- [Optimization](10-deployment/optimization.md)use CleaniqueCoders\Eligify\Data\Extractor;

- [Monitoring](10-deployment/monitoring.md)

- [Troubleshooting](10-deployment/troubleshooting.md)// Extract data from a model

$user = User::with('profile')->find(1);

</details>$extractor = Extractor::forModel(User::class);

$data = $extractor->extract($user);

<details>

<summary><strong>11. Security</strong></summary>// Evaluate with extracted data

$result = Eligify::evaluate('premium_membership', $data);

- [Overview](11-security/README.md)```

- [Best Practices](11-security/best-practices.md)

- [Authorization](11-security/authorization.md)### Creating Model Mappings

- [Input Validation](11-security/input-validation.md)

- [Vulnerability Reporting](11-security/vulnerability-reporting.md)The model mapping system supports 4 powerful patterns for handling relationships and data transformation:



</details>#### Pattern 1: Direct Field Reference



<details>```php

<summary><strong>12. Migration</strong></summary>class UserMapping extends AbstractModelMapping

{

- [Overview](12-migration/README.md)    public function configure(Extractor $extractor): Extractor

- [Upgrade Guide](12-migration/upgrade-guide.md)    {

- [Model Mapping Migration](12-migration/model-mapping-migration.md)        $extractor = parent::configure($extractor);

- [Breaking Changes](12-migration/breaking-changes.md)

- [API Stability](12-migration/api-stability.md)        // Reference OUTPUT fields from ProfileMapping

        $extractor->setRelationshipMappings([

</details>            'profile' => [

                'biography' => 'user_bio',     // ProfileMapping outputs 'biography'

<details>                'employed' => 'is_employed',   // ProfileMapping outputs 'employed'

<summary><strong>13. Examples</strong></summary>            ],

        ]);

- [Overview](13-examples/README.md)

- Basic Examples        return $extractor;

  - [Loan Approval](13-examples/basic/loan-approval.md)    }

  - [Scholarship](13-examples/basic/scholarship.md)}

  - [Job Screening](13-examples/basic/job-screening.md)```

- Intermediate Examples

  - [Insurance](13-examples/intermediate/insurance.md)#### Pattern 2: Spread Operator (All Fields)

  - [E-commerce](13-examples/intermediate/e-commerce.md)

  - [Government Aid](13-examples/intermediate/government-aid.md)```php

- Advanced Examples// Include ALL fields from ProfileMapping automatically

  - [Membership Tiers](13-examples/advanced/membership-tiers.md)$profileMapping = app(ProfileMapping::class);

  - [Credit Card](13-examples/advanced/credit-card.md)$extractor->setRelationshipMappings([

  - [Rental Screening](13-examples/advanced/rental-screening.md)    'profile' => $profileMapping->getFieldMappings(),

  - [SaaS Upgrade](13-examples/advanced/saas-upgrade.md)]);

- Real-World Examples```

  - [Multi-Tenant](13-examples/real-world/multi-tenant.md)

  - [High Traffic](13-examples/real-world/high-traffic.md)#### Pattern 3: Nested Relationships

  - [Complex Workflows](13-examples/real-world/complex-workflows.md)

```php

</details>// Multi-level: Order → Customer → Address

$extractor->setRelationshipMappings([

<details>    'customer' => ['email_address' => 'customer_email'],

<summary><strong>14. API Reference</strong></summary>    'customer.address' => ['street_address' => 'shipping_street'],

]);

- [Overview](14-reference/README.md)```

- API Documentation

  - [Eligify Facade](14-reference/api/eligify-facade.md)#### Pattern 4: Computed Fields with Relationships

  - [Criteria Builder](14-reference/api/criteria-builder.md)

  - [Evaluation Result](14-reference/api/evaluation-result.md)```php

  - [Models](14-reference/api/models.md)$this->computedFields = [

- Reference Guides    'works_at_large_company' => function ($model) {

  - [Operators](14-reference/operators.md)        return ($model->company->employee_count ?? 0) > 100;

  - [Enums](14-reference/enums.md)    },

  - [Events](14-reference/events.md)];

  - [Database Schema](14-reference/database-schema.md)```



</details>### 📖 Complete Documentation



<details>For complete guides on all 4 patterns with working examples:

<summary><strong>15. Appendix</strong></summary>

- **[Model Mapping Guide](model-mapping-guide.md)** - Complete guide with all patterns

- [Overview](15-appendix/README.md)- **[Relationship Mapping Cheatsheet](relationship-mapping-cheatsheet.md)** - Quick reference

- [Glossary](15-appendix/glossary.md)- **[Mapper Generation Guide](mapper-generation-guide.md)** - Auto-generate mappings

- [FAQ](15-appendix/faq.md)- **[Example 17](../examples/17-relationship-mapping-usage.php)** - Working code for all patterns

- [Comparison](15-appendix/comparison.md)

- [Roadmap](15-appendix/roadmap.md)### Generate Mappings Automatically



</details>```bash

# Generate single mapping

---php artisan eligify:make-mapping "App\Models\User"



## 🤝 Contributing# Generate all mappings

php artisan eligify:make-all-mappings

Contributions are welcome! Please see [CONTRIBUTING.md](../CONTRIBUTING.md) for details.```



## 📝 License#### 3. Use Your Mapping



The MIT License (MIT). Please see [License File](../LICENSE.md) for more information.```php

$customer = Customer::with('orders')->find(1);

## 🔗 Links$extractor = Extractor::forModel('App\Models\Customer');

$data = $extractor->extract($customer);

- [GitHub Repository](https://github.com/cleaniquecoders/eligify)

- [Issue Tracker](https://github.com/cleaniquecoders/eligify/issues)// Evaluate with extracted data

- [Changelog](../CHANGELOG.md)$result = Eligify::evaluate('vip_program', $data);

```

### Available Helper Methods

The `AbstractModelMapping` provides safe helper methods:

```php
// Relationship checks
$this->safeRelationshipCheck($model, 'subscriptions', 'active')
$this->hasRelationship($model, 'orders')

// Relationship counts
$this->safeRelationshipCount($model, 'orders')

// Relationship aggregates
$this->safeRelationshipSum($model, 'orders', 'total')
$this->safeRelationshipAvg($model, 'orders', 'rating')
$this->safeRelationshipMax($model, 'orders', 'created_at')
$this->safeRelationshipMin($model, 'orders', 'total')

// Safe data access
$this->getRelationshipData($model, 'profile', [])
```

### Configuration Options

Configure extraction behavior in `config/eligify.php`:

```php
'model_extraction' => [
    // Include timestamp-based computed fields
    'include_timestamps' => true,

    // Include relationship data and counts
    'include_relationships' => true,

    // Include computed fields
    'include_computed_fields' => true,

    // Maximum depth for relationship extraction
    'max_relationship_depth' => 2,

    // Exclude sensitive fields
    'exclude_sensitive_fields' => true,
    'sensitive_fields' => [
        'password',
        'remember_token',
        'api_token',
        'secret',
    ],

    // Date format
    'date_format' => 'Y-m-d H:i:s',

    // Model mappings
    'model_mappings' => [
        'App\Models\User' => \CleaniqueCoders\Eligify\Data\Mappings\UserModelMapping::class,
    ],
],
```

### Automatic Field Extraction

The extractor automatically provides:

#### Timestamp Fields

- `created_days_ago` - Days since creation
- `created_months_ago` - Months since creation
- `created_years_ago` - Years since creation
- `account_age_days` - Alias for created_days_ago
- `updated_days_ago` - Days since last update
- `last_activity_days` - Alias for updated_days_ago

#### Relationship Data

For each loaded relationship:

- `{relation}_count` - Number of related records
- `{relation}_exists` - Boolean: has related records
- `{relation}_{field}_sum` - Sum of numeric field
- `{relation}_{field}_avg` - Average of numeric field
- `{relation}_{field}_max` - Maximum value
- `{relation}_{field}_min` - Minimum value
- `{relation}_{date}_latest` - Most recent date
- `{relation}_{date}_earliest` - Oldest date

### Example: E-commerce Eligibility

```php
// Define criteria using extracted fields
Eligify::criteria('vip_customer_program')
    ->addRule('customer_tier', 'in', ['gold', 'platinum'], 30)
    ->addRule('lifetime_value', '>=', 5000, 25)
    ->addRule('total_orders', '>=', 10, 20)
    ->addRule('account_age_days', '>=', 180, 15)
    ->addRule('last_order_days_ago', '<=', 90, 10)
    ->passThreshold(70)
    ->save();

// Evaluate customer
$customer = Customer::with('orders')->find(1);
$extractor = Extractor::forModel('App\Models\Customer');
$data = $extractor->extract($customer);

$result = Eligify::evaluate('vip_customer_program', $data);

if ($result['passed']) {
    $customer->assignVipStatus();
}
```

### Best Practices

1. **Eager Load Relationships**: Always eager load relationships before extraction to avoid N+1 queries

   ```php
   $user = User::with(['orders', 'subscriptions', 'profile'])->find(1);
   ```

2. **Create Specific Mappings**: Create model-specific mappings for frequently evaluated models

3. **Use Safe Helpers**: Always use the safe helper methods to prevent errors

4. **Cache Extracted Data**: Cache expensive computations if evaluating multiple criteria

5. **Test Your Mappings**: Write tests for custom mapping classes

For detailed documentation on creating custom model mappings, see [Model Mappings Guide](model-mappings.md).

## Configuration

The `config/eligify.php` file contains comprehensive settings for customizing the package behavior.

### Scoring Configuration

```php
'scoring' => [
    'pass_threshold' => 65,      // Default minimum score to pass
    'max_score' => 100,          // Maximum possible score
    'min_score' => 0,            // Minimum possible score
    'method' => 'weighted',      // Default scoring method
    'failure_penalty' => 5,      // Penalty per failed rule
    'excellence_bonus' => 10,    // Bonus for exceptional scores
],
```

### Audit Configuration

```php
'audit' => [
    'enabled' => true,           // Enable audit logging
    'events' => [                // Events to log
        'evaluation_completed',
        'rule_created',
        'criteria_activated',
        'criteria_deactivated',
        'rule_modified',
    ],
    'auto_cleanup' => true,      // Auto-delete old logs
    'retention_days' => 365,     // How long to keep logs
    'cleanup_schedule' => 'daily', // Cleanup frequency
],
```

### Performance Settings

```php
'performance' => [
    'optimize_queries' => true,       // Enable query optimization
    'batch_size' => 100,              // Batch processing size
    'compile_rules' => true,          // Pre-compile rules
    'compilation_cache_ttl' => 1440,  // Cache TTL in minutes (24h)
],
```

### Workflow Configuration

```php
'workflow' => [
    'max_steps' => 50,              // Maximum workflow steps
    'timeout' => 30,                // Timeout in seconds
    'retry_on_failure' => false,    // Retry failed workflows
    'fail_on_callback_error' => true, // Fail if callback throws
    'log_callback_errors' => true,  // Log callback exceptions
],
```

### Common Presets

Pre-configured criteria templates for common use cases:

```php
'presets' => [
    'loan_approval' => [
        'name' => 'Loan Approval',
        'pass_threshold' => 70,
        'rules' => [
            ['field' => 'credit_score', 'operator' => '>=', 'value' => 650, 'weight' => 8],
            ['field' => 'income', 'operator' => '>=', 'value' => 30000, 'weight' => 7],
            ['field' => 'debt_to_income_ratio', 'operator' => '<=', 'value' => 43, 'weight' => 6],
            ['field' => 'employment_status', 'operator' => 'in', 'value' => ['employed', 'self-employed'], 'weight' => 5],
        ],
    ],
    // ... more presets
],
```

## Database Structure

### Tables Overview

Eligify creates four main tables:

1. **eligify_criteria** - Stores criteria definitions
2. **eligify_rules** - Stores individual rules within criteria
3. **eligify_evaluations** - Records evaluation results
4. **eligify_audit_logs** - Comprehensive audit trail

### Criteria Table

```sql
eligify_criteria
├── id (bigint, primary key)
├── uuid (uuid, unique, indexed)
├── name (varchar) - Display name
├── slug (varchar, unique) - URL-friendly identifier
├── description (text, nullable)
├── is_active (boolean, default: true)
├── meta (json, nullable) - Additional metadata
└── timestamps (created_at, updated_at)
```

**Indexes:**

- `uuid` (unique)
- `slug` (unique)
- `name, is_active` (composite)

### Rules Table

```sql
eligify_rules
├── id (bigint, primary key)
├── uuid (uuid, unique, indexed)
├── criteria_id (foreign key → eligify_criteria)
├── field (varchar) - Field name to evaluate
├── operator (varchar) - Comparison operator
├── value (json) - Expected value (supports arrays)
├── weight (integer, default: 1) - Rule importance
├── order (integer, default: 0) - Execution order
├── is_active (boolean, default: true)
├── meta (json, nullable)
└── timestamps (created_at, updated_at)
```

**Indexes:**

- `uuid` (unique)
- `criteria_id, is_active` (composite)
- `field, operator` (composite)

**Foreign Keys:**

- `criteria_id` cascades on delete

### Evaluations Table

```sql
eligify_evaluations
├── id (bigint, primary key)
├── uuid (uuid, unique, indexed)
├── criteria_id (foreign key → eligify_criteria)
├── evaluable_type (varchar, nullable) - Polymorphic type
├── evaluable_id (bigint, nullable) - Polymorphic ID
├── passed (boolean, indexed)
├── score (decimal 8,2, default: 0)
├── failed_rules (json, nullable) - IDs of failed rules
├── rule_results (json, nullable) - Detailed per-rule results
├── decision (varchar, nullable) - Human-readable decision
├── context (json, nullable) - Input data
├── meta (json, nullable)
├── evaluated_at (timestamp)
└── timestamps (created_at, updated_at)
```

**Indexes:**

- `uuid` (unique)
- `evaluable_type, evaluable_id` (composite)
- `passed, evaluated_at` (composite)
- `criteria_id, passed` (composite)

### Audit Logs Table

```sql
eligify_audit_logs
├── id (bigint, primary key)
├── uuid (uuid, unique, indexed)
├── event (varchar) - Event type
├── auditable_type (varchar) - Polymorphic type
├── auditable_id (bigint) - Polymorphic ID
├── old_values (json, nullable) - Previous state
├── new_values (json, nullable) - New state
├── context (json, nullable) - Additional context
├── user_type (varchar, nullable) - User polymorphic type
├── user_id (bigint, nullable) - User ID
├── ip_address (varchar, nullable)
├── user_agent (varchar, nullable)
├── meta (json, nullable)
└── timestamps (created_at, updated_at)
```

**Indexes:**

- `uuid` (unique)
- `auditable_type, auditable_id` (composite)
- `event, created_at` (composite)
- `user_type, user_id` (composite)

## Usage Guide

### Creating Criteria

#### Basic Criteria

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

$criteria = Eligify::criteria('basic_eligibility')
    ->description('Basic eligibility requirements')
    ->addRule('age', '>=', 18)
    ->addRule('country', 'in', ['US', 'CA', 'UK'])
    ->save();
```

#### Weighted Criteria

```php
$criteria = Eligify::criteria('weighted_loan')
    ->description('Weighted loan approval')
    ->addRule('credit_score', '>=', 650, 40)  // 40% weight
    ->addRule('income', '>=', 30000, 30)       // 30% weight
    ->addRule('employment', '==', 'active', 20) // 20% weight
    ->addRule('debt_ratio', '<=', 0.43, 10)    // 10% weight
    ->passThreshold(75)
    ->save();
```

#### With Callbacks

```php
$criteria = Eligify::criteria('membership_upgrade')
    ->addRule('tenure_months', '>=', 6)
    ->addRule('payment_history', '==', 'good')
    ->onPass(function($user, $result) {
        $user->upgradeMembership();
        Notification::send($user, new MembershipUpgraded($result));
    })
    ->onFail(function($user, $result) {
        Log::info('Membership upgrade denied', [
            'user_id' => $user->id,
            'score' => $result['score'],
            'failed_rules' => $result['failed_rules'],
        ]);
    })
    ->save();
```

### Evaluating Criteria

#### Simple Evaluation

```php
$result = Eligify::evaluate('loan_approval', [
    'credit_score' => 720,
    'income' => 55000,
    'employment' => 'active',
    'debt_ratio' => 0.35,
]);

if ($result['passed']) {
    echo "Approved with score: {$result['score']}%";
} else {
    echo "Denied. Failed rules: " . count($result['failed_rules']);
}
```

#### Model-based Evaluation

```php
// Add HasEligibility trait to your model
class User extends Model
{
    use \CleaniqueCoders\Eligify\Concerns\HasEligibility;
}

// Evaluate user (automatically extracts model data)
$user = User::find(1);
$result = $user->evaluateEligibility('premium_membership');

// Or use facade
$result = Eligify::evaluateModel($user, 'premium_membership');

// Manual extraction for more control
$extractor = Extractor::forModel('App\Models\User');
$data = $extractor->extract($user);
$result = Eligify::evaluate('premium_membership', $data);
```

> **Note**: Model-based evaluation automatically uses the [Model Mapping system](#model-mapping--data-extraction) to extract and compute fields. See the [Model Mapping Guide](model-mapping-guide.md) for details on creating custom mappings.

#### Batch Evaluation

```php
$users = User::whereIn('id', [1, 2, 3, 4, 5])->get();

$results = Eligify::evaluateBatch('loan_approval', $users->map(function($user) {
    return [
        'credit_score' => $user->credit_score,
        'income' => $user->annual_income,
        'employment' => $user->employment_status,
    ];
})->toArray());

foreach ($results as $index => $result) {
    echo "User {$users[$index]->name}: " . ($result['passed'] ? 'PASS' : 'FAIL') . "\n";
}
```

### Dynamic Criteria

Create criteria on-the-fly without persisting:

```php
$result = Eligify::evaluateDynamic([
    'age' => 25,
    'country' => 'US',
    'verified' => true,
], function($builder) {
    $builder->addRule('age', '>=', 18)
           ->addRule('country', 'in', ['US', 'CA'])
           ->addRule('verified', '==', true)
           ->passThreshold(100); // All rules must pass
});
```

### Retrieving Evaluations

```php
use CleaniqueCoders\Eligify\Models\Evaluation;

// Get all evaluations for a criteria
$evaluations = Evaluation::where('criteria_id', $criteriaId)->get();

// Get recent passed evaluations
$passed = Evaluation::where('passed', true)
    ->orderBy('evaluated_at', 'desc')
    ->limit(10)
    ->get();

// Get evaluations for a specific model
$userEvaluations = Evaluation::where('evaluable_type', User::class)
    ->where('evaluable_id', $userId)
    ->get();

// Get evaluations with failed rules
$failed = Evaluation::where('passed', false)
    ->whereNotNull('failed_rules')
    ->get();
```

## Advanced Features

### Model Data Extraction & Mappings

Create custom model mapping classes to define how data is extracted from your Eloquent models. This allows you to:

- Transform model attributes before evaluation
- Create computed fields based on relationships
- Add domain-specific business logic
- Reuse extraction logic across multiple criteria

```php
// Create custom mapping
class OrderModelMapping extends AbstractModelMapping
{
    public function getModelClass(): string
    {
        return 'App\Models\Order';
    }

    public function __construct()
    {
        $this->fieldMappings = [
            'created_at' => 'order_date',
        ];

        $this->computedFields = [
            'order_value_category' => fn($m) => match(true) {
                $m->total >= 1000 => 'high',
                $m->total >= 500 => 'medium',
                default => 'low'
            },
            'days_since_order' => fn($m) => now()->diffInDays($m->created_at),
        ];
    }
}
```

See the [Model Mapping Guide](model-mapping-guide.md) for complete documentation on all 4 patterns with working examples.

### Custom Scoring Methods

Implement custom scoring logic:

```php
use CleaniqueCoders\Eligify\Engine\RuleEngine;

class CustomScoringEngine extends RuleEngine
{
    protected function calculateScore(array $ruleResults): int
    {
        // Your custom scoring logic
        $score = 0;
        foreach ($ruleResults as $result) {
            if ($result['passed']) {
                $score += $result['rule']->weight * 2; // Double weight bonus
            }
        }
        return min($score, 100);
    }
}

// Use custom engine
app()->bind(RuleEngine::class, CustomScoringEngine::class);
```

### Advanced Rule Engine

Use the advanced engine for complex scenarios:

```php
use CleaniqueCoders\Eligify\Engine\AdvancedRuleEngine;

$engine = new AdvancedRuleEngine();

// Set custom options
$engine->setOption('parallel_execution', true)
       ->setOption('cache_results', true)
       ->setOption('optimization_level', 'aggressive');

$result = $engine->evaluate($criteria, $data);

// Get execution plan
$plan = $engine->getExecutionPlan($criteria);
```

### Conditional Rules

Create rules with dependencies:

```php
$criteria->addRule('age', '>=', 18)
         ->addRule('has_guardian', '==', true)
         ->addConditionalRule(
             condition: fn($data) => $data['age'] < 21,
             rule: ['field' => 'parental_consent', 'operator' => '==', 'value' => true]
         );
```

### Rule Groups

Group related rules for better organization:

```php
$criteria->addRuleGroup('financial_requirements', [
    ['field' => 'income', 'operator' => '>=', 'value' => 30000, 'weight' => 5],
    ['field' => 'credit_score', 'operator' => '>=', 'value' => 650, 'weight' => 5],
    ['field' => 'debt_ratio', 'operator' => '<=', 'value' => 0.43, 'weight' => 3],
])
->addRuleGroup('identity_verification', [
    ['field' => 'ssn_verified', 'operator' => '==', 'value' => true, 'weight' => 8],
    ['field' => 'address_verified', 'operator' => '==', 'value' => true, 'weight' => 7],
]);
```

### Audit Trail

Track all eligibility evaluations:

```php
use CleaniqueCoders\Eligify\Models\AuditLog;

// Get audit logs for a specific evaluation
$logs = AuditLog::where('auditable_type', Evaluation::class)
    ->where('auditable_id', $evaluationId)
    ->orderBy('created_at', 'desc')
    ->get();

// Get user activity
$userLogs = AuditLog::where('user_type', User::class)
    ->where('user_id', $userId)
    ->where('event', 'evaluation_completed')
    ->get();

// Get logs by event type
$ruleChanges = AuditLog::where('event', 'rule_modified')
    ->with('auditable')
    ->get();
```

### Custom Events

Listen to Eligify events:

```php
// In EventServiceProvider
protected $listen = [
    \CleaniqueCoders\Eligify\Events\EvaluationCompleted::class => [
        \App\Listeners\SendEligibilityNotification::class,
    ],
    \CleaniqueCoders\Eligify\Events\CriteriaCreated::class => [
        \App\Listeners\LogCriteriaCreation::class,
    ],
    \CleaniqueCoders\Eligify\Events\RuleExecuted::class => [
        \App\Listeners\TrackRulePerformance::class,
    ],
];
```

## CLI Commands

Eligify provides several Artisan commands for management and monitoring.

### Status Command

View package status and recent criteria:

```bash
php artisan eligify status
```

Output:

```
🎯 Eligify Package Status

Component      Count  Status
────────────────────────────────
Criteria       12     ✅ Active
Rules          48     ✅ Active
Evaluations    1,234  ✅ Active
Audit Logs     5,678  ✅ Active

📋 Recent Criteria:
Name                    Slug                    Rules      Status     Created
────────────────────────────────────────────────────────────────────────────────
Loan Approval          loan-approval           5 rules    ✅ Active  2025-10-27
Scholarship            scholarship             8 rules    ✅ Active  2025-10-26
```

### Statistics Command

View detailed statistics:

```bash
php artisan eligify stats
```

### Health Check

Run system health checks:

```bash
php artisan eligify health
```

### Criteria Management

Create criteria via CLI:

```bash
php artisan eligify:criteria create
```

List all criteria:

```bash
php artisan eligify:criteria list
```

Deactivate criteria:

```bash
php artisan eligify:criteria deactivate loan-approval
```

### Evaluate Command

Evaluate criteria from command line:

```bash
php artisan eligify:evaluate loan-approval --data='{"credit_score":720,"income":55000}'
```

Interactive evaluation:

```bash
php artisan eligify:evaluate loan-approval --interactive
```

### Audit Management

Query audit logs:

```bash
# All logs
php artisan eligify:audit

# Specific event
php artisan eligify:audit --event=evaluation_completed

# Date range
php artisan eligify:audit --from=2025-10-01 --to=2025-10-27

# Export to CSV
php artisan eligify:audit --export=audits.csv
```

Clean up old logs:

```bash
# Clean logs older than 365 days
php artisan eligify:cleanup-audit

# Custom retention
php artisan eligify:cleanup-audit --days=90

# Dry run
php artisan eligify:cleanup-audit --dry-run
```

## Policy Integration

Integrate Eligify with Laravel policies for authorization. See [Policy Integration Guide](policy-integration.md) for detailed documentation.

### Basic Integration

```php
use CleaniqueCoders\Eligify\Concerns\HasEligibility;

class UserPolicy
{
    use HasEligibility;

    public function updateProfile(User $user)
    {
        return $this->hasEligibility($user, 'profile_update_eligibility');
    }

    public function accessPremiumFeatures(User $user)
    {
        return $this->hasMinimumScore($user, 'premium_eligibility', 80);
    }
}
```

### Advanced Policy Usage

```php
public function applyForLoan(User $user)
{
    $result = $this->checkEligibility($user, 'loan_approval');

    if (!$result['passed']) {
        return Response::deny(
            'You do not meet the loan approval criteria. ' .
            'Score: ' . $result['score'] . '%'
        );
    }

    return Response::allow();
}
```

## Testing

### Factory Usage

Eligify provides factories for all models:

```php
use CleaniqueCoders\Eligify\Models\Criteria;
use CleaniqueCoders\Eligify\Models\Rule;

// Create criteria with rules
$criteria = Criteria::factory()
    ->has(Rule::factory()->count(5))
    ->create([
        'name' => 'Test Criteria',
        'pass_threshold' => 75,
    ]);

// Create evaluation
$evaluation = Evaluation::factory()
    ->for($criteria)
    ->for($user, 'evaluable')
    ->create([
        'passed' => true,
        'score' => 85,
    ]);
```

### Testing Criteria

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

test('loan approval passes for qualified applicant', function () {
    $criteria = Eligify::criteria('test_loan')
        ->addRule('credit_score', '>=', 650)
        ->addRule('income', '>=', 30000)
        ->passThreshold(70)
        ->save();

    $result = Eligify::evaluate('test_loan', [
        'credit_score' => 720,
        'income' => 55000,
    ]);

    expect($result['passed'])->toBeTrue();
    expect($result['score'])->toBeGreaterThan(70);
});
```

### Mocking Evaluations

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

test('handles failed evaluation', function () {
    Eligify::shouldReceive('evaluate')
        ->once()
        ->with('loan_approval', Mockery::any())
        ->andReturn([
            'passed' => false,
            'score' => 45,
            'failed_rules' => [/* ... */],
        ]);

    // Your test code
});
```

## Real-World Examples

The `examples/` directory contains 11 comprehensive real-world examples:

1. **[Loan Approval](../examples/01-loan-approval.php)** - Financial services loan decisioning
2. **[Scholarship Eligibility](../examples/02-scholarship-eligibility.php)** - Educational award qualification
3. **[Job Candidate Screening](../examples/03-job-candidate-screening.php)** - HR recruitment filtering
4. **[Insurance Underwriting](../examples/04-insurance-underwriting.php)** - Risk assessment and premium calculation
5. **[E-commerce Discount](../examples/05-e-commerce-discount-eligibility.php)** - Retail promotion eligibility
6. **[Government Aid](../examples/06-government-aid-qualification.php)** - Social services qualification
7. **[Gym Membership](../examples/07-gym-membership-tiers.php)** - Tiered subscription services
8. **[Credit Card Approval](../examples/08-credit-card-approval.php)** - Banking card approval tiers
9. **[Rental Screening](../examples/09-rental-application-screening.php)** - Property tenant qualification
10. **[SaaS Upgrade](../examples/10-saas-plan-upgrade-eligibility.php)** - Software subscription upgrades
11. **[User Account Verification](../examples/11-user-account-verification.php)** - UserModelMapping demonstration with account verification

### Featured Example: User Account Verification

This example demonstrates the power of **UserModelMapping** for automatic data extraction:

```php
use CleaniqueCoders\Eligify\Data\Extractor;

// Create criteria using UserModelMapping computed fields
$criteria = Eligify::criteria('verified_user_access')
    ->addRule('is_verified', '==', true, 40)           // Computed field
    ->addRule('account_age_days', '>=', 7, 30)         // Computed field
    ->addRule('registration_date', '<=', now(), 30)    // Mapped field
    ->passThreshold(70)
    ->save();

// Extract data automatically using UserModelMapping
$user = User::find(1);
$extractor = Extractor::forModel('App\Models\User');
$data = $extractor->extract($user);

// Evaluate with extracted data
$result = Eligify::evaluate('verified_user_access', $data);
```

The example shows:

- ✅ Automatic field mapping (`created_at` → `registration_date`)
- ✅ Computed fields (`is_verified`, `account_age_days`)
- ✅ Single and batch user evaluation
- ✅ Detailed rule-by-rule breakdown

Run any example:

```bash
php examples/01-loan-approval.php
```

## Best Practices

### 1. Naming Conventions

```php
// Use descriptive, unique names
Eligify::criteria('personal_loan_approval_tier_1')
    ->description('Personal loans up to $50,000 for tier 1 applicants');

// Avoid generic names
// ❌ Eligify::criteria('approval')
// ✅ Eligify::criteria('premium_membership_approval')
```

### 2. Weight Distribution

```php
// Ensure weights add up logically
$criteria->addRule('credit_score', '>=', 650, 40)   // Critical factor
         ->addRule('income', '>=', 30000, 30)        // Important
         ->addRule('employment', '!=', 'unemployed', 20)  // Moderate
         ->addRule('address_verified', '==', true, 10);   // Minor
```

### 3. Threshold Selection

```php
// Choose appropriate pass thresholds
->passThreshold(100)  // Perfect score required (strict)
->passThreshold(80)   // High bar (selective)
->passThreshold(65)   // Moderate bar (balanced)
->passThreshold(50)   // Low bar (lenient)
```

### 4. Error Handling

```php
try {
    $result = Eligify::evaluate('loan_approval', $data);
} catch (\InvalidArgumentException $e) {
    // Criteria not found
    Log::error('Criteria not found', ['name' => 'loan_approval']);
} catch (\Exception $e) {
    // Other evaluation errors
    Log::error('Evaluation failed', ['error' => $e->getMessage()]);
}
```

### 5. Performance Optimization

```php
// Enable caching in config
'performance' => [
    'optimize_queries' => true,
    'compile_rules' => true,
    'compilation_cache_ttl' => 1440,
],

// Eager load relationships
$criteria = Criteria::with('rules')->find($id);

// Batch evaluations instead of loops
$results = Eligify::batchEvaluate('criteria_name', $dataArray);
```

## Troubleshooting

### Common Issues

**Issue: Criteria not found**

```php
// Solution: Check slug format
$criteria = Criteria::where('slug', str('My Criteria')->slug())->first();
```

**Issue: Rules not evaluating correctly**

```php
// Solution: Verify data types match expectations
$result = Eligify::evaluate('test', [
    'age' => (int) $age,        // Ensure integer
    'income' => (float) $income, // Ensure float
]);
```

**Issue: Callbacks not executing**

```php
// Solution: Ensure criteria is saved before evaluation
$criteria->save();  // Must save first
$result = Eligify::evaluate('criteria_name', $data);
```

## Support

- **Documentation**: [https://github.com/cleaniquecoders/eligify/tree/main/docs](https://github.com/cleaniquecoders/eligify/tree/main/docs)
- **Issues**: [https://github.com/cleaniquecoders/eligify/issues](https://github.com/cleaniquecoders/eligify/issues)
- **Discussions**: [https://github.com/cleaniquecoders/eligify/discussions](https://github.com/cleaniquecoders/eligify/discussions)

## License

Eligify is open-sourced software licensed under the [MIT license](../LICENSE.md).
