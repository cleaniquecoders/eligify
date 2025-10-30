# Operators Reference

Complete list of available operators in Eligify.

## Comparison Operators

### `==` (Equals)

Strict equality check.

```php
->addRule('status', '==', 'active')
```

### `!=` (Not Equals)

Not equal check.

```php
->addRule('status', '!=', 'banned')
```

### `>` (Greater Than)

Numeric greater than.

```php
->addRule('age', '>', 18)
```

### `>=` (Greater Than or Equal)

Numeric greater than or equal.

```php
->addRule('income', '>=', 3000)
```

### `<` (Less Than)

Numeric less than.

```php
->addRule('age', '<', 65)
```

### `<=` (Less Than or Equal)

Numeric less than or equal.

```php
->addRule('debt_ratio', '<=', 0.4)
```

## Array Operators

### `in`

Check if value is in array.

```php
->addRule('country', 'in', ['US', 'CA', 'UK'])
```

### `not_in`

Check if value is not in array.

```php
->addRule('status', 'not_in', ['banned', 'suspended'])
```

## Range Operators

### `between`

Check if value is between two numbers (inclusive).

```php
->addRule('age', 'between', [18, 65])
```

## String Operators

### `contains`

Check if string contains substring.

```php
->addRule('email', 'contains', '@example.com')
```

### `starts_with`

Check if string starts with prefix.

```php
->addRule('phone', 'starts_with', '+1')
```

### `ends_with`

Check if string ends with suffix.

```php
->addRule('email', 'ends_with', '.com')
```

## Custom Operators

### Register Custom Operator

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

Eligify::registerOperator('divisible_by', function ($value, $divisor) {
    return $value % $divisor === 0;
});

// Usage
->addRule('points', 'divisible_by', 10)
```

## Related

- [Enums Reference](enums.md)
- [Custom Operators Guide](../../07-advanced-features/README.md#custom-operators)
