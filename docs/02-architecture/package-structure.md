# Package Structure

This document explains how the Eligify package source code is organized.

## Directory Overview

```
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

```
Actions/
├── CreateCriteria.php
├── EvaluateCriteria.php
├── DeleteCriteria.php
└── GenerateMapper.php
```

**Example:**

```php
namespace CleaniqueCoders\Eligify\Actions;

class EvaluateCriteria
{
    public function execute(Criteria $criteria, mixed $subject): EvaluationResult
    {
        // Evaluation logic
    }
}
```

### Audit System (src/Audit/)

Handles evaluation audit logging:

```
Audit/
├── AuditLogger.php
├── AuditManager.php
└── Contracts/
    └── AuditLoggerInterface.php
```

### Builder (src/Builder/)

Fluent interfaces for building criteria and rules:

```
Builder/
├── CriteriaBuilder.php
├── RuleBuilder.php
└── Contracts/
    └── BuilderInterface.php
```

### Commands (src/Commands/)

Artisan console commands:

```
Commands/
├── EligifyCommand.php
├── InstallCommand.php
├── GenerateMapperCommand.php
└── ClearCacheCommand.php
```

**Usage:**

```bash
php artisan eligify:install
php artisan eligify:generate-mapper User
php artisan eligify:clear-cache
```

### Concerns (src/Concerns/)

Reusable traits for models and classes:

```
Concerns/
├── HasCriteria.php
├── HasEvaluations.php
├── Mappable.php
└── Cacheable.php
```

**Example:**

```php
namespace CleaniqueCoders\Eligify\Concerns;

trait HasCriteria
{
    public function criteria()
    {
        return $this->belongsToMany(Criteria::class);
    }

    public function evaluateAgainst(string $criteriaName): EvaluationResult
    {
        $criteria = Criteria::findByName($criteriaName);
        return $criteria->evaluate($this);
    }
}
```

### Data Objects (src/Data/)

Immutable DTOs for passing data:

```
Data/
├── Snapshot.php
├── EvaluationResult.php
├── RuleData.php
└── CriteriaData.php
```

**Example:**

```php
namespace CleaniqueCoders\Eligify\Data;

readonly class EvaluationResult
{
    public function __construct(
        public bool $passed,
        public float $score,
        public array $passedRules,
        public array $failedRules,
        public array $details
    ) {}

    public function passed(): bool
    {
        return $this->passed;
    }

    public function score(): float
    {
        return $this->score;
    }
}
```

### Engine (src/Engine/)

Core evaluation and scoring logic:

```
Engine/
├── RuleEngine.php
├── EvaluationEngine.php
├── Operators/
│   ├── EqualsOperator.php
│   ├── GreaterThanOperator.php
│   ├── InOperator.php
│   └── ...
├── Scorers/
│   ├── WeightedScorer.php
│   ├── PassFailScorer.php
│   └── ...
└── Contracts/
    ├── EvaluatorInterface.php
    ├── OperatorInterface.php
    └── ScorerInterface.php
```

### Enums (src/Enums/)

Type-safe enumerations:

```
Enums/
├── ScoringMethod.php
├── OperatorType.php
└── EvaluationStatus.php
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
```

### Events (src/Events/)

Laravel events for key actions:

```
Events/
├── CriteriaCreated.php
├── CriteriaUpdated.php
├── EvaluationCompleted.php
└── RuleAdded.php
```

### Facades (src/Facades/)

Laravel facades for convenient access:

```
Facades/
└── Eligify.php
```

**Usage:**

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

Eligify::criteria('test')->evaluate($user);
```

### HTTP Layer (src/Http/)

Web interface controllers and middleware:

```
Http/
├── Controllers/
│   ├── CriteriaController.php
│   ├── EvaluationController.php
│   └── PlaygroundController.php
└── Middleware/
    ├── EnsureEligifyEnabled.php
    └── LogEvaluations.php
```

### Listeners (src/Listeners/)

Event listeners for async processing:

```
Listeners/
├── LogEvaluation.php
├── SendNotification.php
└── UpdateStatistics.php
```

### Models (src/Models/)

Eloquent models for database persistence:

```
Models/
├── Criteria.php
├── Rule.php
├── Evaluation.php
└── Snapshot.php
```

### Observers (src/Observers/)

Model lifecycle observers:

```
Observers/
├── CriteriaObserver.php
└── EvaluationObserver.php
```

### Support (src/Support/)

Helper classes and utilities:

```
Support/
├── Mappers/
│   ├── BaseMapper.php
│   ├── UserMapper.php
│   └── ...
├── Helpers.php
├── CacheManager.php
└── DataExtractor.php
```

### Workflow (src/Workflow/)

Workflow callback execution:

```
Workflow/
├── WorkflowManager.php
├── Callbacks/
│   ├── OnPassCallback.php
│   └── OnFailCallback.php
└── Contracts/
    └── WorkflowInterface.php
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
