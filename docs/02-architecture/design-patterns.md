# Design Patterns

Eligify uses several well-established design patterns to create a maintainable and extensible codebase.

## Builder Pattern

The **Builder Pattern** provides a fluent interface for constructing complex criteria objects.

### Implementation

```php
$criteria = Eligify::criteria('Loan Approval')
    ->addRule('income', '>=', 3000)
    ->addRule('credit_score', '>=', 650)
    ->setScoring('weighted')
    ->onPass(fn($user) => $user->approve())
    ->onFail(fn($user) => $user->reject());
```

### Benefits

- **Readable**: Chainable methods create self-documenting code
- **Flexible**: Build criteria incrementally
- **Immutable**: Each method returns a new instance (optional)

### Key Classes

- `CleaniqueCoders\Eligify\Builder\CriteriaBuilder`
- `CleaniqueCoders\Eligify\Builder\RuleBuilder`

```php
namespace CleaniqueCoders\Eligify\Builder;

class CriteriaBuilder
{
    protected array $rules = [];
    protected string $scoring = 'weighted';
    protected array $workflows = [];

    public function addRule(string $field, string $operator, mixed $value, int $weight = 1): self
    {
        $this->rules[] = compact('field', 'operator', 'value', 'weight');
        return $this;
    }

    public function setScoring(string $method): self
    {
        $this->scoring = $method;
        return $this;
    }

    public function build(): Criteria
    {
        return new Criteria($this->rules, $this->scoring, $this->workflows);
    }
}
```

## Factory Pattern

The **Factory Pattern** creates instances of evaluators, scorers, and operators based on configuration.

### Operator Factory

```php
namespace CleaniqueCoders\Eligify\Engine;

class OperatorFactory
{
    public static function make(string $operator): OperatorInterface
    {
        return match($operator) {
            '==', 'equals' => new EqualsOperator(),
            '>', 'greater_than' => new GreaterThanOperator(),
            '>=', 'gte' => new GreaterThanOrEqualOperator(),
            'in', 'contains' => new InOperator(),
            'between' => new BetweenOperator(),
            default => throw new UnsupportedOperatorException($operator),
        };
    }
}
```

### Usage

```php
$operator = OperatorFactory::make('>=');
$result = $operator->evaluate(100, 50); // true
```

### Scorer Factory

```php
namespace CleaniqueCoders\Eligify\Engine;

class ScorerFactory
{
    public static function make(string $method): ScorerInterface
    {
        return match($method) {
            'weighted' => new WeightedScorer(),
            'pass_fail' => new PassFailScorer(),
            'sum' => new SumScorer(),
            'average' => new AverageScorer(),
            default => throw new UnsupportedScoringException($method),
        };
    }
}
```

## Strategy Pattern

The **Strategy Pattern** allows different scoring algorithms to be used interchangeably.

### Scorer Interface

```php
namespace CleaniqueCoders\Eligify\Engine;

interface ScorerInterface
{
    public function calculate(array $rules, array $results): float;
    public function isPassing(float $score, float $threshold): bool;
}
```

### Implementations

#### Weighted Scorer

```php
class WeightedScorer implements ScorerInterface
{
    public function calculate(array $rules, array $results): float
    {
        $totalWeight = array_sum(array_column($rules, 'weight'));
        $earnedWeight = 0;

        foreach ($results as $index => $passed) {
            if ($passed) {
                $earnedWeight += $rules[$index]['weight'];
            }
        }

        return ($earnedWeight / $totalWeight) * 100;
    }

    public function isPassing(float $score, float $threshold): bool
    {
        return $score >= $threshold;
    }
}
```

#### Pass/Fail Scorer

```php
class PassFailScorer implements ScorerInterface
{
    public function calculate(array $rules, array $results): float
    {
        $allPassed = !in_array(false, $results, true);
        return $allPassed ? 100 : 0;
    }

    public function isPassing(float $score, float $threshold): bool
    {
        return $score === 100;
    }
}
```

### Usage

```php
$scorer = ScorerFactory::make('weighted');
$score = $scorer->calculate($rules, $results);
```

## Observer Pattern

The **Observer Pattern** notifies listeners when evaluations occur.

### Event System

```php
namespace CleaniqueCoders\Eligify\Events;

class EvaluationCompleted
{
    public function __construct(
        public Criteria $criteria,
        public mixed $subject,
        public EvaluationResult $result
    ) {}
}
```

### Observers

```php
namespace CleaniqueCoders\Eligify\Observers;

class CriteriaObserver
{
    public function created(Criteria $criteria): void
    {
        event(new CriteriaCreated($criteria));
    }

    public function updated(Criteria $criteria): void
    {
        event(new CriteriaUpdated($criteria));
    }
}
```

### Listeners

```php
namespace CleaniqueCoders\Eligify\Listeners;

class LogEvaluation
{
    public function handle(EvaluationCompleted $event): void
    {
        Log::info('Evaluation completed', [
            'criteria' => $event->criteria->name,
            'passed' => $event->result->passed(),
            'score' => $event->result->score(),
        ]);
    }
}
```

### Registration

```php
// In EventServiceProvider
protected $listen = [
    EvaluationCompleted::class => [
        LogEvaluation::class,
        SendNotification::class,
        UpdateStatistics::class,
    ],
];
```

## Repository Pattern

The **Repository Pattern** abstracts data access for criteria and evaluations.

### Interface

```php
namespace CleaniqueCoders\Eligify\Contracts;

interface CriteriaRepository
{
    public function find(int $id): ?Criteria;
    public function findByName(string $name): ?Criteria;
    public function save(Criteria $criteria): bool;
    public function delete(int $id): bool;
    public function all(): Collection;
}
```

### Implementation

```php
namespace CleaniqueCoders\Eligify\Repositories;

class EloquentCriteriaRepository implements CriteriaRepository
{
    public function findByName(string $name): ?Criteria
    {
        return CriteriaModel::where('name', $name)->first();
    }

    public function save(Criteria $criteria): bool
    {
        return $criteria->save();
    }

    // ... more methods
}
```

### Usage

```php
$repository = app(CriteriaRepository::class);
$criteria = $repository->findByName('loan_approval');
```

## Chain of Responsibility

The **Chain of Responsibility** pattern processes rules sequentially, with early termination options.

### Implementation

```php
namespace CleaniqueCoders\Eligify\Engine;

class RuleChain
{
    protected array $handlers = [];

    public function add(RuleHandler $handler): self
    {
        $this->handlers[] = $handler;
        return $this;
    }

    public function process(mixed $data): array
    {
        $results = [];

        foreach ($this->handlers as $handler) {
            $result = $handler->handle($data);
            $results[] = $result;

            if ($handler->shouldStop($result)) {
                break;
            }
        }

        return $results;
    }
}
```

### Handler

```php
abstract class RuleHandler
{
    abstract public function handle(mixed $data): bool;

    public function shouldStop(bool $result): bool
    {
        return false; // Override in subclasses for early termination
    }
}
```

## Decorator Pattern

The **Decorator Pattern** adds functionality to the evaluation process without modifying core classes.

### Cache Decorator

```php
namespace CleaniqueCoders\Eligify\Engine\Decorators;

class CacheDecorator implements EvaluatorInterface
{
    public function __construct(
        protected EvaluatorInterface $evaluator,
        protected CacheInterface $cache
    ) {}

    public function evaluate(Criteria $criteria, mixed $subject): EvaluationResult
    {
        $cacheKey = $this->getCacheKey($criteria, $subject);

        return $this->cache->remember($cacheKey, 3600, function () use ($criteria, $subject) {
            return $this->evaluator->evaluate($criteria, $subject);
        });
    }

    protected function getCacheKey(Criteria $criteria, mixed $subject): string
    {
        return sprintf(
            'eligify:evaluation:%s:%s',
            $criteria->name,
            md5(serialize($subject))
        );
    }
}
```

### Audit Decorator

```php
namespace CleaniqueCoders\Eligify\Engine\Decorators;

class AuditDecorator implements EvaluatorInterface
{
    public function __construct(
        protected EvaluatorInterface $evaluator,
        protected AuditLogger $logger
    ) {}

    public function evaluate(Criteria $criteria, mixed $subject): EvaluationResult
    {
        $startTime = microtime(true);
        $result = $this->evaluator->evaluate($criteria, $subject);
        $duration = microtime(true) - $startTime;

        $this->logger->log([
            'criteria' => $criteria->name,
            'subject' => $subject,
            'result' => $result,
            'duration' => $duration,
        ]);

        return $result;
    }
}
```

### Usage

```php
$evaluator = new RuleEngine();
$evaluator = new CacheDecorator($evaluator, $cache);
$evaluator = new AuditDecorator($evaluator, $logger);

$result = $evaluator->evaluate($criteria, $subject);
```

## Dependency Injection

All Eligify components use **Dependency Injection** via Laravel's service container.

### Service Provider

```php
namespace CleaniqueCoders\Eligify;

class EligifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(EvaluatorInterface::class, RuleEngine::class);
        $this->app->singleton(CriteriaRepository::class, EloquentCriteriaRepository::class);
        $this->app->bind(ScorerInterface::class, fn($app, $params) =>
            ScorerFactory::make($params['method'] ?? 'weighted')
        );
    }
}
```

### Usage

```php
class MyService
{
    public function __construct(
        protected EvaluatorInterface $evaluator,
        protected CriteriaRepository $repository
    ) {}

    public function checkEligibility(User $user): bool
    {
        $criteria = $this->repository->findByName('premium_upgrade');
        $result = $this->evaluator->evaluate($criteria, $user);
        return $result->passed();
    }
}
```

## Summary

Eligify leverages these design patterns to achieve:

- **Maintainability**: Clear separation of concerns
- **Extensibility**: Easy to add new features
- **Testability**: Components can be tested in isolation
- **Flexibility**: Behavior can be changed without modifying core code

## Related Documentation

- [Package Structure](package-structure.md) - How code is organized
- [Request Lifecycle](request-lifecycle.md) - How evaluation flows
- [Extensibility](extensibility.md) - How to extend Eligify
