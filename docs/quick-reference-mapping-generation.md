# Model Mapping Quick Reference

## Commands

### Generate Single Mapping

```bash
php artisan eligify:make-mapping "App\Models\User"
php artisan eligify:make-mapping "App\Models\User" --force
php artisan eligify:make-mapping "App\Models\User" --name=premium-user
```

### Generate All Mappings

```bash
php artisan eligify:make-all-mappings
php artisan eligify:make-all-mappings --dry-run
php artisan eligify:make-all-mappings --force
php artisan eligify:make-all-mappings --path=modules/User/Models --namespace=Modules\\User\\Models
```

## Prefix Usage

| Model Class | Auto-Generated Prefix | Field Examples |
|-------------|----------------------|----------------|
| `User` | `user` | `user.name`, `user.email` |
| `Applicant` | `applicant` | `applicant.income`, `applicant.credit_score` |
| `LoanApplication` | `loan.application` | `loan.application.amount`, `loan.application.status` |
| `CreditReport` | `credit.report` | `credit.report.score`, `credit.report.delinquencies` |

## Mapping Structure

```php
class UserMapping extends AbstractModelMapping
{
    // Model this mapping is for
    public function getModelClass(): string
    {
        return 'App\Models\User';
    }

    // Field mappings: original => mapped
    protected array $fieldMappings = [
        'created_at' => 'created_date',
        'email_verified_at' => 'email_verified_timestamp',
    ];

    // Relationship mappings
    protected array $relationshipMappings = [
        'posts.count' => 'posts_count',
    ];

    // Computed fields (closures)
    protected array $computedFields = [
        'is_verified' => null,
    ];

    // Prefix for field namespacing
    protected ?string $prefix = 'user';

    public function __construct()
    {
        $this->computedFields = [
            'is_verified' => fn($model) => !is_null($model->email_verified_at),
        ];
    }

    public function getName(): string
    {
        return 'User';
    }

    public function getDescription(): string
    {
        return 'User model mapping with profile data';
    }
}
```

## Using Prefixes in Rules

### Basic Usage

```php
Eligify::criteria('loan_approval')
    ->addRule('applicant.income', '>=', 3000)
    ->addRule('applicant.credit_score', '>=', 650)
    ->evaluate($applicant);
```

### Relationship Mapping Usage

```php
// When Applicant has 'user' relationship with UserMapping
Eligify::criteria('application_review')
    ->addRule('applicant.income', '>=', 3000)
    ->addRule('applicant.user.is_verified', '=', true)  // Via UserMapping
    ->addRule('applicant.user.email', 'contains', '@example.com')
    ->evaluate($applicant);
```

## Relationship Detection

When generating mappings, relationships are detected:

- ✅ **Has Mapping**: Comments added suggesting to use related mapping's prefix
- ❌ **No Mapping**: Standard relationship mappings generated (count, sum, avg)

### Example Output

```php
protected array $relationshipMappings = [
    'user.count' => 'user_count',
    // user uses User which has UserMapping
    // You can reference fields like: user.user.field_name

    'applications.count' => 'applications_count',
    'applications.sum:amount' => 'total_application_amount',
];
```

## Best Practices

1. **Use Consistent Prefixes**: Stick to snake_case model names
2. **Generate Early**: Create mappings before writing rules
3. **Leverage Relationships**: Use related model mappings for nested data
4. **Version Control**: Commit generated mappings
5. **Dry Run First**: Use `--dry-run` to preview bulk generations

## Common Patterns

### Multi-Word Models

```php
LoanApplication → loan.application
CreditReport → credit.report
UserProfile → user.profile
```

### Related Data Access

```php
// Direct field
applicant.income

// Related model field via mapping
applicant.user.email

// Nested relationship
loan.application.user.is_verified
```

### Computed Fields

```php
// In mapping
'is_verified' => fn($model) => !is_null($model->email_verified_at)
'full_name' => fn($model) => $model->first_name . ' ' . $model->last_name
'age' => fn($model) => $model->birth_date?->age

// In rules
->addRule('applicant.is_verified', '=', true)
->addRule('applicant.full_name', '!=', null)
->addRule('applicant.age', '>=', 18)
```
