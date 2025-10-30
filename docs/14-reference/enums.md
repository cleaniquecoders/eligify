# Enums Reference

All enum values used in Eligify.

## ScoringMethod Enum

Scoring calculation methods.

### Values

- `weighted`: Weighted average of rule scores
- `pass_fail`: Binary pass/fail (0 or 100)
- `percentage`: Percentage of passed rules

### Usage

```php
->scoringMethod('weighted')
```

## OperatorType Enum

Operator categories.

### Values

- `comparison`: `==`, `!=`, `>`, `>=`, `<`, `<=`
- `array`: `in`, `not_in`
- `range`: `between`
- `string`: `contains`, `starts_with`, `ends_with`
- `custom`: User-defined operators

## AuditLevel Enum

Audit logging levels.

### Values

- `none`: No auditing
- `basic`: Log pass/fail only
- `detailed`: Log all rules and scores
- `full`: Log everything including snapshots

### Usage

```php
// config/eligify.php
'audit' => [
    'level' => 'detailed',
],
```

## CacheStrategy Enum

Caching strategies.

### Values

- `none`: No caching
- `entity`: Cache per entity
- `criteria`: Cache per criteria
- `global`: Global cache

### Usage

```php
// config/eligify.php
'cache' => [
    'strategy' => 'entity',
],
```

## Related

- [Operators Reference](operators.md)
- [Events Reference](events.md)
