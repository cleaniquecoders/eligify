# Package Structure

This document explains how the Eligify package source code is organized.

## Directory Overview

```plaintext
src/
├── Actions/            # Single-purpose action classes
├── Audit/              # Audit logging system
├── Builder/            # Criteria and rule builders
├── Commands/           # Artisan console commands
├── Concerns/           # Reusable traits
├── Data/               # Data transfer objects (DTOs)
├── Engine/             # Core evaluation engine
├── Enums/              # Enum classes
├── Events/             # Laravel events
├── Facades/            # Laravel facades
├── Http/               # HTTP controllers and middleware
├── Listeners/          # Event listeners
├── Models/             # Eloquent models
├── Observers/          # Model observers
├── Support/            # Helper classes and utilities
├── Workflow/           # Workflow callback handlers
├── Eligify.php         # Main package class
└── EligifyServiceProvider.php  # Laravel service provider
```

## Core Components

### Actions (src/Actions/)

Single-purpose action classes following the Single Responsibility Principle:

```plaintext
Actions/
└── GetDashboardMetrics.php
```

**Example:**

```php
namespace CleaniqueCoders\Eligify\Actions;

class GetDashboardMetrics
{
    public function execute(): array
    {
        // Dashboard metrics logic
    }
}
```

### Audit System (src/Audit/)

Handles evaluation audit logging:

```plaintext
Audit/
└── AuditLogger.php
```

### Builder (src/Builder/)

Fluent interfaces for building criteria and rules:

```plaintext
Builder/
└── CriteriaBuilder.php
```

### Commands (src/Commands/)

Artisan console commands:

```plaintext
Commands/
├── AuditQueryCommand.php
├── BenchmarkCommand.php
├── CacheClearCommand.php
├── CacheStatsCommand.php
├── CacheWarmupCommand.php
├── CleanupAuditLogsCommand.php
├── CriteriaCommand.php
├── EligifyCommand.php
├── EvaluateCommand.php
├── MakeAllMappingsCommand.php
└── MakeMappingCommand.php
```

**Usage:**

```bash
php artisan eligify:cache-clear
php artisan eligify:cache-stats
php artisan eligify:cache-warmup
php artisan eligify:audit-query
php artisan eligify:benchmark
php artisan eligify:cleanup-audit-logs
php artisan eligify:criteria
php artisan eligify:evaluate
php artisan eligify:make-mapping User
php artisan eligify:make-all-mappings
```

### Concerns (src/Concerns/)

Reusable traits for models and classes:

```plaintext
Concerns/
└── HasEligibility.php
```

**Example:**

```php
namespace CleaniqueCoders\Eligify\Concerns;

trait HasEligibility
{
    public function evaluateAgainst(string $criteriaName): mixed
    {
        // Eligibility evaluation logic
    }
}
```

### Data Objects (src/Data/)

Data extraction and snapshot management for eligibility evaluations:

```plaintext
Data/
├── Extractor.php               # Model data extractor
├── Snapshot.php                # Immutable data snapshot
├── Contracts/
│   └── ModelMapping.php        # Model mapping interface
└── Mappings/
    ├── AbstractModelMapping.php    # Base mapping class
    └── UserModelMapping.php        # User model mapping
```

**Extractor Example:**

```php
namespace CleaniqueCoders\Eligify\Data;

use CleaniqueCoders\Eligify\Data\Extractor;

// Quick extraction with defaults
$extractor = new Extractor();
$snapshot = $extractor->extract($user);

// Custom configuration per instance
$extractor = new Extractor([
    'include_relationships' => true,
    'max_relationship_depth' => 3,
]);
$extractor->setFieldMappings(['annual_income' => 'income'])
          ->setComputedFields(['risk_score' => fn($model) => $model->calculateRisk()]);
$snapshot = $extractor->extract($user);

// Model-specific extractors (RECOMMENDED)
$snapshot = Extractor::forModel(User::class)->extract($user);
```

**Snapshot Example:**

```php
namespace CleaniqueCoders\Eligify\Data;

// Direct property access
$income = $snapshot->income;

// Safe access with defaults
$score = $snapshot->get('credit_score', 650);

// Check if field exists
if ($snapshot->has('employment_verified')) {
    // ...
}

// Convert to array for rule engine
$data = $snapshot->toArray();

// Get only specific fields
$subset = $snapshot->only(['income', 'credit_score', 'age']);

// Exclude sensitive fields
$safe = $snapshot->except(['ssn', 'account_number']);
```

**Custom Model Mapping Example:**

```php
namespace CleaniqueCoders\Eligify\Data\Mappings;

class UserModelMapping extends AbstractModelMapping
{
    public function getModelClass(): string
    {
        return 'App\Models\User';
    }

    protected array $fieldMappings = [
        'email_verified_at' => 'email_verified_timestamp',
        'created_at' => 'registration_date',
    ];

    protected array $computedFields = [
        'is_verified' => null,
    ];

    public function __construct()
    {
        $this->computedFields = [
            'is_verified' => fn ($model) => ! is_null($model->email_verified_at ?? null),
        ];
    }
}
```

### Engine (src/Engine/)

Core evaluation and scoring logic:

```plaintext
Engine/
├── AdvancedRuleEngine.php
└── RuleEngine.php
```

### Enums (src/Enums/)

Type-safe enumerations:

```plaintext
Enums/
├── FieldType.php
├── RuleOperator.php
├── RulePriority.php
└── ScoringMethod.php
```

**Example:**

```php
namespace CleaniqueCoders\Eligify\Enums;

enum ScoringMethod: string
{
    case WEIGHTED = 'weighted';
    case PASS_FAIL = 'pass_fail';
    case SUM = 'sum';
    case AVERAGE = 'average';
}

enum RuleOperator: string
{
    case EQUALS = '=';
    case NOT_EQUALS = '!=';
    case GREATER_THAN = '>';
    case LESS_THAN = '<';
    case GREATER_THAN_OR_EQUAL = '>=';
    case LESS_THAN_OR_EQUAL = '<=';
    case IN = 'in';
    case NOT_IN = 'not_in';
    case BETWEEN = 'between';
    case CONTAINS = 'contains';
}
```

### Events (src/Events/)

Laravel events for key actions:

```plaintext
Events/
├── CriteriaCreated.php
├── EvaluationCompleted.php
└── RuleExecuted.php
```

### Facades (src/Facades/)

Laravel facades for convenient access:

```plaintext
Facades/
└── Eligify.php
```

**Usage:**

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

Eligify::criteria('test')->evaluate($user);
```

### HTTP Layer (src/Http/)

Web interface with Livewire components and middleware:

```plaintext
Http/
├── Livewire/
│   ├── AuditLogList.php
│   ├── CriteriaEditor.php
│   ├── CriteriaList.php
│   ├── CriteriaShow.php
│   ├── Playground.php
│   ├── RuleEditor.php
│   ├── RuleLibraryList.php
│   └── SettingsManager.php
└── Middleware/
    └── AuthorizeDashboard.php
```

### Listeners (src/Listeners/)

Event listeners for async processing:

```plaintext
Listeners/
├── LogCriteriaCreated.php
├── LogEvaluationCompleted.php
└── LogRuleExecuted.php
```

### Models (src/Models/)

Eloquent models for database persistence:

```plaintext
Models/
├── AuditLog.php
├── Criteria.php
├── Evaluation.php
└── Rule.php
```

### Observers (src/Observers/)

Model lifecycle observers:

```plaintext
Observers/
├── CriteriaObserver.php
└── RuleObserver.php
```

### Support (src/Support/)

Helper classes and utilities:

```plaintext
Support/
├── Benchmark.php
├── Cache.php
├── Config.php
└── MappingRegistry.php
```

### Workflow (src/Workflow/)

Workflow callback execution:

```plaintext
Workflow/
└── WorkflowManager.php
```

## Additional Directories

### Config (config/)

Package configuration:

```
config/
└── eligify.php
```

### Database (database/)

Migrations and factories:

```
database/
├── factories/
│   └── CriteriaFactory.php
└── migrations/
    ├── create_eligify_criteria_table.php
    ├── create_eligify_rules_table.php
    └── create_eligify_evaluations_table.php
```

### Resources (resources/)

Views and assets:

```
resources/
└── views/
    ├── layouts/
    ├── criteria/
    ├── playground/
    └── audit/
```

### Routes (routes/)

Package routes:

```
routes/
└── eligify.php
```

### Tests (tests/)

Test suite:

```
tests/
├── ArchTest.php
├── Pest.php
├── TestCase.php
├── Feature/
│   ├── CriteriaTest.php
│   ├── EvaluationTest.php
│   └── ...
└── Unit/
    ├── BuilderTest.php
    ├── EngineTest.php
    └── ...
```

## Autoloading

### PSR-4 Namespaces

```json
{
    "autoload": {
        "psr-4": {
            "CleaniqueCoders\\Eligify\\": "src/",
            "CleaniqueCoders\\Eligify\\Database\\Factories\\": "database/factories/"
        }
    }
}
```

### Class Resolution

```php
// Maps to src/Builder/CriteriaBuilder.php
use CleaniqueCoders\Eligify\Builder\CriteriaBuilder;

// Maps to src/Engine/Operators/EqualsOperator.php
use CleaniqueCoders\Eligify\Engine\Operators\EqualsOperator;

// Maps to database/factories/CriteriaFactory.php
use CleaniqueCoders\Eligify\Database\Factories\CriteriaFactory;
```

## Naming Conventions

### Classes

- **Controllers**: `{Name}Controller` (e.g., `CriteriaController`)
- **Models**: `{Name}` (e.g., `Criteria`, `Rule`)
- **Actions**: `{Verb}{Noun}` (e.g., `CreateCriteria`, `EvaluateCriteria`)
- **Builders**: `{Name}Builder` (e.g., `CriteriaBuilder`)
- **Events**: `{Noun}{PastTense}` (e.g., `CriteriaCreated`)
- **Listeners**: `{Verb}{Noun}` (e.g., `LogEvaluation`)
- **Observers**: `{Name}Observer` (e.g., `CriteriaObserver`)
- **Traits**: `Has{Feature}` or `{Adjective}` (e.g., `HasCriteria`, `Cacheable`)

### Files

- Classes: PascalCase (e.g., `CriteriaBuilder.php`)
- Config: snake_case (e.g., `eligify.php`)
- Views: kebab-case (e.g., `criteria-list.blade.php`)
- Migrations: snake_case with timestamp (e.g., `2024_01_01_000000_create_eligify_table.php`)

## Dependencies

### External Packages

- **spatie/laravel-package-tools**: Package development utilities
- **spatie/laravel-data**: Data transfer objects
- **livewire/livewire**: Interactive UI components

### Laravel Features Used

- Service Container
- Eloquent ORM
- Event System
- Cache System
- Queue System
- Validation
- Middleware
- Console Commands

## Package Bootstrap

### Service Provider

```php
namespace CleaniqueCoders\Eligify;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class EligifyServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('eligify')
            ->hasConfigFile()
            ->hasMigrations([
                'create_eligify_criteria_table',
                'create_eligify_rules_table',
                'create_eligify_evaluations_table',
            ])
            ->hasViews()
            ->hasRoute('eligify')
            ->hasCommands([
                InstallCommand::class,
                GenerateMapperCommand::class,
            ]);
    }

    public function registeringPackage(): void
    {
        $this->app->singleton(Eligify::class);
    }

    public function bootingPackage(): void
    {
        // Register observers
        Criteria::observe(CriteriaObserver::class);
    }
}
```

## Extension Points

Eligify is designed to be extended:

1. **Custom Operators**: Implement `OperatorInterface`
2. **Custom Scorers**: Implement `ScorerInterface`
3. **Custom Mappers**: Extend `BaseMapper`
4. **Event Listeners**: Listen to Eligify events
5. **Middleware**: Add custom evaluation middleware

## Related Documentation

- [Design Patterns](design-patterns.md) - Patterns used
- [Request Lifecycle](request-lifecycle.md) - Evaluation flow
- [Extensibility](extensibility.md) - How to extend
