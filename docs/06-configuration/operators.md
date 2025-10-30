# Operators Reference

Complete reference guide for all available operators in Eligify.

## Overview

Operators define how field values are compared during rule evaluation. Eligify includes comprehensive built-in operators and supports custom operators.

## Comparison Operators

### Equals

**Operators:** `==`, `equals`

**Description:** Exact match comparison

**Example:**
```php
->addRule('status', '==', 'active')
->addRule('verified', 'equals', true)
```

**Supported Types:**
- String
- Number
- Boolean
- Null

---

### Not Equals

**Operators:** `!=`, `not_equals`, `<>`

**Description:** Not equal comparison

**Example:**
```php
->addRule('status', '!=', 'banned')
->addRule('type', 'not_equals', 'guest')
```

**Supported Types:**
- String
- Number
- Boolean
- Null

---

### Greater Than

**Operators:** `>`, `greater_than`, `gt`

**Description:** Numeric greater than

**Example:**
```php
->addRule('age', '>', 18)
->addRule('score', 'greater_than', 100)
```

**Supported Types:**
- Number
- Date (as timestamp)

---

### Greater or Equal

**Operators:** `>=`, `gte`, `greater_than_or_equal`

**Description:** Numeric greater than or equal to

**Example:**
```php
->addRule('income', '>=', 3000)
->addRule('points', 'gte', 1000)
```

**Supported Types:**
- Number
- Date (as timestamp)

---

### Less Than

**Operators:** `<`, `less_than`, `lt`

**Description:** Numeric less than

**Example:**
```php
->addRule('debt', '<', 10000)
->addRule('attempts', 'less_than', 3)
```

**Supported Types:**
- Number
- Date (as timestamp)

---

### Less or Equal

**Operators:** `<=`, `lte`, `less_than_or_equal`

**Description:** Numeric less than or equal to

**Example:**
```php
->addRule('age', '<=', 65)
->addRule('loans', 'lte', 2)
```

**Supported Types:**
- Number
- Date (as timestamp)

## Membership Operators

### In Array

**Operators:** `in`, `contains`, `in_array`

**Description:** Value exists in array

**Example:**
```php
->addRule('country', 'in', ['US', 'CA', 'UK'])
->addRule('role', 'contains', ['admin', 'editor'])
```

**Supported Types:**
- String
- Number
- Mixed

---

### Not In Array

**Operators:** `not_in`, `not_contains`

**Description:** Value not in array

**Example:**
```php
->addRule('status', 'not_in', ['banned', 'suspended'])
```

**Supported Types:**
- String
- Number
- Mixed

---

### Between

**Operators:** `between`

**Description:** Value within range (inclusive)

**Example:**
```php
->addRule('age', 'between', [18, 65])
->addRule('score', 'between', [0, 100])
```

**Supported Types:**
- Number
- Date

## Existence Operators

### Empty

**Operators:** `empty`, `is_empty`

**Description:** Field is empty, null, or zero

**Example:**
```php
->addRule('notes', 'empty')
->addRule('warnings', 'is_empty')
```

**Considered Empty:**
- `null`
- `""`
- `[]`
- `0`
- `false`

---

### Not Empty

**Operators:** `not_empty`, `filled`

**Description:** Field has a value

**Example:**
```php
->addRule('email', 'not_empty')
->addRule('phone', 'filled')
```

**Considered Not Empty:**
- Any value except those in "Empty" list

---

### Exists

**Operators:** `exists`, `has`

**Description:** Field exists in data

**Example:**
```php
->addRule('profile', 'exists')
->addRule('settings', 'has')
```

---

### Not Exists

**Operators:** `not_exists`, `missing`

**Description:** Field doesn't exist in data

**Example:**
```php
->addRule('deleted_at', 'not_exists')
```

## String Operators

### Starts With

**Operators:** `starts_with`, `begins_with`

**Description:** String begins with value

**Example:**
```php
->addRule('email', 'starts_with', 'admin@')
->addRule('code', 'begins_with', 'PRE-')
```

**Case Sensitive:** No (by default)

---

### Ends With

**Operators:** `ends_with`

**Description:** String ends with value

**Example:**
```php
->addRule('email', 'ends_with', '@company.com')
->addRule('filename', 'ends_with', '.pdf')
```

**Case Sensitive:** No (by default)

---

### Contains Text

**Operators:** `contains_text`, `includes`, `has_substring`

**Description:** String contains substring

**Example:**
```php
->addRule('description', 'contains_text', 'premium')
->addRule('tags', 'includes', 'featured')
```

**Case Sensitive:** No (by default)

---

### Matches Pattern

**Operators:** `matches`, `regex`, `pattern`

**Description:** Matches regular expression

**Example:**
```php
->addRule('phone', 'matches', '/^\+1\d{10}$/')
->addRule('postal_code', 'regex', '/^[A-Z]\d[A-Z]\s?\d[A-Z]\d$/')
```

**Note:** Pattern must include delimiters (`/pattern/`)

## Date/Time Operators

### Before

**Operators:** `before`, `earlier_than`

**Description:** Date is before specified date

**Example:**
```php
->addRule('birth_date', 'before', now()->subYears(18))
->addRule('expires_at', 'before', now())
```

**Supported Formats:**
- Carbon instance
- DateTime instance
- String (Y-m-d format)
- Timestamp

---

### After

**Operators:** `after`, `later_than`

**Description:** Date is after specified date

**Example:**
```php
->addRule('created_at', 'after', now()->subYear())
->addRule('last_login', 'after', now()->subDays(30))
```

**Supported Formats:**
- Carbon instance
- DateTime instance
- String (Y-m-d format)
- Timestamp

---

### Date Between

**Operators:** `date_between`

**Description:** Date within range

**Example:**
```php
->addRule('joined_at', 'date_between', [
    now()->subYear(),
    now()->subMonths(6)
])
```

**Supported Formats:**
- Array of [start, end] dates
- Both dates use same format options as Before/After

## Type Checking Operators

### Is Null

**Operators:** `is_null`, `null`

**Description:** Value is null

**Example:**
```php
->addRule('deleted_at', 'is_null')
```

---

### Not Null

**Operators:** `not_null`, `is_not_null`

**Description:** Value is not null

**Example:**
```php
->addRule('email', 'not_null')
```

---

### Is Boolean

**Operators:** `is_boolean`, `is_bool`

**Description:** Value is boolean

**Example:**
```php
->addRule('verified', 'is_boolean')
```

---

### Is Number

**Operators:** `is_number`, `is_numeric`

**Description:** Value is numeric

**Example:**
```php
->addRule('quantity', 'is_numeric')
```

---

### Is String

**Operators:** `is_string`

**Description:** Value is string

**Example:**
```php
->addRule('name', 'is_string')
```

---

### Is Array

**Operators:** `is_array`

**Description:** Value is array

**Example:**
```php
->addRule('tags', 'is_array')
```

## Custom Operators

Create custom operators for specialized logic:

```php
namespace App\Eligify\Operators;

use CleaniqueCoders\Eligify\Engine\Contracts\OperatorInterface;

class DivisibleByOperator implements OperatorInterface
{
    public function evaluate(mixed $actual, mixed $expected): bool
    {
        if (!is_numeric($actual) || !is_numeric($expected)) {
            return false;
        }

        return $expected != 0 && $actual % $expected === 0;
    }

    public function validate(mixed $value): bool
    {
        return is_numeric($value);
    }

    public function getDescription(): string
    {
        return 'Checks if value is divisible by expected value';
    }
}
```

Register:

```php
// config/eligify.php
'operators' => [
    'divisible_by' => \App\Eligify\Operators\DivisibleByOperator::class,
],
```

Use:

```php
->addRule('quantity', 'divisible_by', 12)
```

## Operator Aliases

Multiple aliases for convenience:

| Primary | Aliases |
|---------|---------|
| `==` | `equals` |
| `!=` | `not_equals`, `<>` |
| `>` | `greater_than`, `gt` |
| `>=` | `gte`, `greater_than_or_equal` |
| `<` | `less_than`, `lt` |
| `<=` | `lte`, `less_than_or_equal` |
| `in` | `contains`, `in_array` |
| `not_in` | `not_contains` |
| `empty` | `is_empty` |
| `not_empty` | `filled` |
| `exists` | `has` |
| `not_exists` | `missing` |

## Operator Configuration

Configure operators:

```php
// config/eligify.php
'operators' => [
    // Enable/disable built-in operators
    'equals' => true,
    'greater_than' => true,

    // Add custom operators
    'custom_operator' => \App\Eligify\Operators\CustomOperator::class,

    // Operator options
    'case_sensitive' => false,
    'strict_types' => false,
],
```

## Related Documentation

- [Rule Engine](../03-core-features/rule-engine.md) - How operators are used
- [Extensibility](../02-architecture/extensibility.md) - Creating custom operators
- [Configuration Reference](reference.md) - Full configuration
