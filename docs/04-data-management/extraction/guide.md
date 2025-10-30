# Model Data Extraction Guide

The `Extractor` class is responsible for transforming Laravel Eloquent models into flat arrays suitable for eligibility rule evaluation. This guide helps you choose the right approach for your use case.

## Quick Decision Guide

```mermaid
flowchart TD
    Start([Need to extract model data?]) --> MultiModel{Working with<br/>multiple model types?}

    MultiModel -->|Yes| Production{Production environment?}
    MultiModel -->|No| OneTime{One-time extraction<br/>or repeated use?}

    Production -->|Yes| UseForModel[✅ Use forModel method<br/>with config mappings]
    Production -->|No| DevEnv{Need custom logic<br/>per model?}

    DevEnv -->|Yes| CreateMapping[Create ModelMapping classes<br/>+ Use forModel]
    DevEnv -->|No| UseForModel

    OneTime -->|One-time| QuickExtract[✅ Use new Extractor<br/>+ extract method]
    OneTime -->|Repeated| NeedCustom{Need custom<br/>field mappings?}

    NeedCustom -->|Yes| SetMappings[✅ Use setter methods<br/>setFieldMappings, etc.]
    NeedCustom -->|No| QuickExtract

    UseForModel --> Result1[Extractor::forModel User::class<br/>->extract user]
    CreateMapping --> Result1
    SetMappings --> Result2[extractor->setFieldMappings...<br/>->extract model]
    QuickExtract --> Result3[new Extractor<br/>->extract model]

    style UseForModel fill:#90EE90
    style CreateMapping fill:#90EE90
    style SetMappings fill:#87CEEB
    style QuickExtract fill:#FFD700
    style Result1 fill:#E8F5E9
    style Result2 fill:#E3F2FD
    style Result3 fill:#FFF9C4
```

## Method Comparison

### 1. Quick Extraction (Simple Use Cases)

**When to use:**

- Prototyping or testing
- Simple models without custom requirements
- One-off extractions
- You want default behavior

**Example:**

```php
$extractor = new Extractor();
$data = $extractor->extract($user);
```

**Pros:**

- ✅ Fastest to implement
- ✅ No configuration needed
- ✅ Good for quick tests

**Cons:**

- ❌ No customization
- ❌ Same config for all models
- ❌ Harder to maintain at scale

---

### 2. Custom Configuration (One-off Customization)

**When to use:**

- Need specific field mappings for a single extraction
- Adding computed fields on-the-fly
- Exploratory data analysis
- Non-production scripts

**Example:**

```php
$extractor = new Extractor([
    'include_relationships' => true,
    'max_relationship_depth' => 3,
]);

$extractor
    ->setFieldMappings([
        'annual_income' => 'income',
        'credit_rating' => 'credit_score',
    ])
    ->setComputedFields([
        'debt_to_income_ratio' => fn($model, $data) =>
            $data['debt'] / $data['income'],
    ]);

$data = $extractor->extract($user);
```

**Pros:**

- ✅ Flexible per-extraction
- ✅ No config files needed
- ✅ Good for exploration

**Cons:**

- ❌ Configuration not reusable
- ❌ Duplicated logic across codebase
- ❌ Hard to maintain

---

### 3. Model-Specific Extractors (RECOMMENDED for Production)

**When to use:**

- Production applications
- Multiple model types with different extraction needs
- Team environments requiring consistency
- When you want centralized extraction logic

**Example:**

**Step 1: Configure in `config/eligify.php`**

```php
'model_extraction' => [
    'model_mappings' => [
        \App\Models\User::class => \App\Eligify\Mappings\UserMapping::class,
        \App\Models\LoanApplication::class => \App\Eligify\Mappings\LoanMapping::class,
    ],
    'default_mapping' => \App\Eligify\Mappings\DefaultMapping::class,
],
```

**Step 2: Create mapping class**

```php
namespace App\Eligify\Mappings;

use CleaniqueCoders\Eligify\Mappings\AbstractModelMapping;
use CleaniqueCoders\Eligify\Data\Extractor;

class UserMapping extends AbstractModelMapping
{
    protected ?string $prefix = 'user';

    protected array $fieldMappings = [
        'annual_income' => 'income',
        'credit_rating' => 'credit_score',
    ];

    protected array $computedFields = [];

    public function __construct()
    {
        $this->computedFields = [
            'risk_score' => fn($model) => $model->calculateRisk(),
        ];
    }

    public function getModelClass(): string
    {
        return \App\Models\User::class;
    }

    public function getName(): string
    {
        return 'User';
    }

    public function getDescription(): string
    {
        return 'User account and financial data';
    }

    public function configure(Extractor $extractor): Extractor
    {
        $extractor = parent::configure($extractor);

        // Include profile relationship using ProfileMapping
        $extractor->setRelationshipMappings([
            'profile' => [
                'employment_status' => 'is_employed',
            ],
        ]);

        return $extractor;
    }
}
```

### Step 3: Use in code

```php
$data = Extractor::forModel(User::class)->extract($user);
```

```

```php
namespace App\Eligify\Mappings;

use CleaniqueCoders\Eligify\Contracts\ModelMapping;
use CleaniqueCoders\Eligify\Data\Extractor;

class UserMapping implements ModelMapping
{
    public function configure(Extractor $extractor): Extractor
    {
        return $extractor
            ->setFieldMappings([
                'annual_income' => 'income',
                'credit_rating' => 'credit_score',
            ])
            ->setRelationshipMappings([
                'profile' => [
                    'employment_status' => 'is_employed',
                ],
            ])
            ->setComputedFields([
                'risk_score' => fn($model) => $model->calculateRisk(),
            ]);
    }
}
```

**Step 3: Use in code**

```php
$data = Extractor::forModel(User::class)->extract($user);
```

**Pros:**

- ✅ Centralized configuration
- ✅ Reusable across application
- ✅ Type-safe with model classes
- ✅ Easy to test and maintain
- ✅ Consistent extraction logic
- ✅ Version controlled

**Cons:**

- ❌ Requires initial setup
- ❌ More files to manage

---

## Usage Decision Tree

```mermaid
flowchart TD
    Start([Choose Extraction Method]) --> Q1{Is this for<br/>production use?}

    Q1 -->|Yes| Prod[Use Pattern 3:<br/>forModel with mappings]
    Q1 -->|No| Q2{Need custom<br/>field mappings?}

    Q2 -->|Yes| Q3{Will you reuse<br/>this logic?}
    Q2 -->|No| Simple[Use Pattern 1:<br/>Quick extraction]

    Q3 -->|Yes| Prod
    Q3 -->|No| Custom[Use Pattern 2:<br/>Custom configuration]

    Prod --> ProdCode["Extractor::forModel(User::class)<br/>->extract($user)"]
    Custom --> CustomCode["(new Extractor)<br/>->setFieldMappings(...)<br/>->extract($model)"]
    Simple --> SimpleCode["(new Extractor)<br/>->extract($model)"]

    style Prod fill:#90EE90
    style Custom fill:#87CEEB
    style Simple fill:#FFD700
```

## Extraction Process Flow

```mermaid
flowchart TB
    Input[Model Instance] --> Extract[extract method called]

    Extract --> Step1[1. Extract Basic Attributes<br/>Database columns, excluding sensitive fields]
    Step1 --> Step2[2. Extract Computed Fields<br/>created_days_ago, email_verified, etc.]
    Step2 --> Step3[3. Extract Relationships<br/>Counts, aggregations, summaries]
    Step3 --> Step4[4. Apply Field Mappings<br/>Rename fields per configuration]
    Step4 --> Step5[5. Apply Relationship Mappings<br/>Flatten nested relationship data]
    Step5 --> Step6[6. Apply Custom Computed Fields<br/>User-defined calculations]
    Step6 --> Output[Flat Array for Rules]

    Config[Configuration] -.-> Extract
    Mappings[Field Mappings] -.-> Step4
    RelMappings[Relationship Mappings] -.-> Step5
    Computed[Computed Fields] -.-> Step6

    style Input fill:#E3F2FD
    style Output fill:#C8E6C9
    style Extract fill:#FFF9C4
```

## Real-World Examples

### Example 1: Loan Approval (Production)

```php
// config/eligify.php
'model_mappings' => [
    \App\Models\LoanApplication::class => \App\Eligify\Mappings\LoanMapping::class,
],

// app/Eligify/Mappings/LoanMapping.php
class LoanMapping implements ModelMapping
{
    public function configure(Extractor $extractor): Extractor
    {
        return $extractor->setFieldMappings([
            'requested_amount' => 'loan_amount',
            'monthly_income' => 'income',
        ])->setComputedFields([
            'debt_to_income_ratio' => function($model, $data) {
                $totalDebt = $data['loans_sum_amount'] ?? 0;
                return $totalDebt / $data['income'];
            },
        ]);
    }
}

// In your controller/service
$data = Extractor::forModel(LoanApplication::class)
    ->extract($application);

// Use $data in eligibility rules
```

### Example 2: Quick Script (Development)

```php
// Quick one-off extraction for testing
$extractor = new Extractor(['include_relationships' => false]);
$data = $extractor->extract($user);

// Inspect what data is available
dd($data);
```

### Example 3: Custom Report (Ad-hoc)

```php
// Generate a custom eligibility report with specific metrics
$extractor = (new Extractor)
    ->setComputedFields([
        'eligibility_score' => function($model, $data) {
            $score = 0;
            $score += $data['income'] > 5000 ? 30 : 0;
            $score += $data['account_age_days'] > 180 ? 25 : 0;
            $score += $data['email_verified'] ? 20 : 0;
            $score += ($data['orders_count'] ?? 0) > 5 ? 25 : 0;
            return $score;
        },
    ]);

$results = collect($users)->map(fn($user) => [
    'user' => $user->name,
    'data' => $extractor->extract($user),
]);
```

## Best Practices

### ✅ Do's

1. **Use `forModel()` for production code**

   ```php
   // Good: Centralized, maintainable
   Extractor::forModel(User::class)->extract($user)
   ```

2. **Create mapping classes for each model type**

   ```php
   // Good: Organized, reusable
   class UserMapping implements ModelMapping { ... }
   ```

3. **Keep computed fields pure and testable**

   ```php
   // Good: Simple, testable calculation
   'risk_score' => fn($model, $data) => ($data['income'] / $data['debt']) * 100
   ```

4. **Document your field mappings**

   ```php
   // Good: Clear documentation
   ->setFieldMappings([
       'annual_income' => 'income', // Rules expect 'income'
   ])
   ```

### ❌ Don'ts

1. **Don't duplicate extraction logic**

   ```php
   // Bad: Repeated in multiple places
   $extractor = new Extractor();
   $extractor->setFieldMappings(['annual_income' => 'income']);
   // ... repeated everywhere
   ```

2. **Don't perform side effects in computed fields**

   ```php
   // Bad: Modifies database
   'score' => function($model) {
       $model->update(['last_scored' => now()]); // Don't do this!
       return $model->score;
   }
   ```

3. **Don't ignore sensitive data filtering**

   ```php
   // Bad: Exposes sensitive data
   new Extractor(['exclude_sensitive_fields' => false])
   ```

4. **Don't nest extractors recursively**

   ```php
   // Bad: Can cause infinite loops
   'profile_data' => fn($model) =>
       Extractor::forModel(Profile::class)->extract($model->profile)
   ```

## Summary Table

| Pattern | Use Case | Setup Time | Maintainability | Reusability | Production Ready |
|---------|----------|------------|-----------------|-------------|------------------|
| **Pattern 1: Quick** | Prototyping, testing | ⚡ Instant | ⭐ Low | ❌ No | ❌ No |
| **Pattern 2: Custom** | One-off scripts, exploration | ⏱️ Minutes | ⭐⭐ Medium | ⚠️ Limited | ⚠️ Maybe |
| **Pattern 3: forModel** | Production, teams | ⏰ Hours | ⭐⭐⭐ High | ✅ Yes | ✅ Yes |

## Using Relationships with Existing Model Mappings

When your models have relationships, you should reuse existing model mappings instead of duplicating field mapping logic. This keeps your code DRY and maintainable.

### Pattern 1: Include Relationship Fields

When you have a User model with a Profile relationship, and both have their own mapping classes:

```php
// ProfileMapping.php
class ProfileMapping extends AbstractModelMapping
{
    protected ?string $prefix = 'profile';

    protected array $fieldMappings = [
        'bio' => 'biography',
        'employment_status' => 'employed',
    ];

    public function getModelClass(): string
    {
        return Profile::class;
    }

    // ... other required methods
}

// UserMapping.php - reuses ProfileMapping
class UserMapping extends AbstractModelMapping
{
    protected ?string $prefix = 'user';

    protected array $fieldMappings = [
        'email' => 'email_address',
    ];

    public function configure(Extractor $extractor): Extractor
    {
        $extractor = parent::configure($extractor);

        // Include specific fields from ProfileMapping
        $extractor->setRelationshipMappings([
            'profile' => [
                'biography' => 'user_bio',        // Maps profile.biography -> user_bio
                'employed' => 'is_employed',       // Maps profile.employed -> is_employed
            ],
        ]);

        return $extractor;
    }

    // ... other required methods
}
```

**Result:** User data includes `user.email_address`, `user_bio`, and `is_employed`

### Pattern 2: Nested Relationships

For multi-level relationships like Order -> Customer -> Address:

```php
class OrderMapping extends AbstractModelMapping
{
    protected ?string $prefix = 'order';

    public function configure(Extractor $extractor): Extractor
    {
        $extractor = parent::configure($extractor);

        $extractor->setRelationshipMappings([
            // Direct relationship
            'customer' => [
                'email_address' => 'customer_email',
            ],
            // Nested relationship: customer's address
            'customer.address' => [
                'street_address' => 'shipping_street',
                'city_name' => 'shipping_city',
                'zip_code' => 'shipping_zip',
            ],
        ]);

        return $extractor;
    }
}
```

**Result:** Order data includes fields from three different mappings seamlessly

### Pattern 3: Relationship Data in Computed Fields

Use related model data in computed fields:

```php
class EmployeeMapping extends AbstractModelMapping
{
    protected ?string $prefix = 'employee';

    protected array $computedFields = [];

    public function __construct()
    {
        $this->computedFields = [
            'works_at_large_company' => function ($model) {
                if (!$model->relationLoaded('company')) {
                    return false;
                }
                // Reference company data through relationship
                return ($model->company->employee_count ?? 0) > 100;
            },
        ];
    }

    public function configure(Extractor $extractor): Extractor
    {
        $extractor = parent::configure($extractor);

        // Include company fields using CompanyMapping
        $extractor->setRelationshipMappings([
            'company' => [
                'company_name' => 'employer_name',
                'employees' => 'employer_size',
            ],
        ]);

        return $extractor;
    }
}
```

**Result:** Employee data includes company fields and computed field based on company data

### Auto-Generation with Relationships

When using the mapper generation commands, relationships with existing mappings are automatically detected:

```bash
php artisan eligify:make-mapping User
```

If User model has a `profile()` relationship and ProfileMapping exists, the generated code will include:

```php
public function configure(Extractor $extractor): Extractor
{
    $extractor = parent::configure($extractor);

    // Relationship: uses ProfileModelMapping for profile
    $extractor->setRelationshipMappings([
        'profile' => [
            // Add specific profile fields you want to include
        ],
    ]);

    return $extractor;
}
```

### Benefits of Reusing Mappings

- **No Duplication**: Field mapping logic defined once per model
- **Consistent**: Same field transformations everywhere
- **Maintainable**: Update mapping in one place
- **Type-Safe**: Use actual mapping classes, not string references
- **Prefixed**: Each mapping has its own namespace (e.g., `user.email` vs `profile.bio`)
