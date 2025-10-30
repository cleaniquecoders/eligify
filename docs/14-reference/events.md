# Events Reference

All events dispatched by Eligify.

## CriteriaEvaluated

Fired after criteria evaluation completes.

### Properties

```php
class CriteriaEvaluated
{
    public function __construct(
        public string $criteriaName,
        public Model $entity,
        public EvaluationResult $result,
    ) {}
}
```

### Listening

```php
use CleaniqueCoders\Eligify\Events\CriteriaEvaluated;

Event::listen(CriteriaEvaluated::class, function ($event) {
    Log::info("Evaluated {$event->criteriaName}", [
        'passed' => $event->result->passed(),
        'score' => $event->result->score,
    ]);
});
```

## RulePassed

Fired when a rule passes.

### Properties

```php
class RulePassed
{
    public function __construct(
        public array $rule,
        public mixed $actualValue,
    ) {}
}
```

## RuleFailed

Fired when a rule fails.

### Properties

```php
class RuleFailed
{
    public function __construct(
        public array $rule,
        public mixed $actualValue,
        public string $reason,
    ) {}
}
```

## CriteriaCreated

Fired when criteria is created.

### Properties

```php
class CriteriaCreated
{
    public function __construct(
        public Criteria $criteria,
    ) {}
}
```

## CriteriaUpdated

Fired when criteria is updated.

## SnapshotCreated

Fired when snapshot is created.

## Related

- [Event System](../../07-advanced-features/README.md#event-system)
- [Models API](api/models.md)
