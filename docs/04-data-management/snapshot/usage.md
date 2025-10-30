# Snapshot Class

The `Snapshot` class is a powerful, type-safe container for data extracted from Eloquent models. It provides a rich API for accessing, filtering, transforming, and working with data in eligibility evaluation contexts.

## Overview

Instead of working with plain arrays, `Snapshot` wraps your data in an immutable object that provides:

- ✅ **Type Safety** - Strong type hints for better IDE support
- ✅ **Rich API** - Methods for filtering, transformation, and selection
- ✅ **Immutability** - Prevents accidental data modifications
- ✅ **Metadata Tracking** - Automatically tracks extraction context
- ✅ **Multiple Access Patterns** - Property, array, or method-based access
- ✅ **Backward Compatible** - Works seamlessly with existing array-based code

## Basic Usage

### Creating Instances

```php
use CleaniqueCoders\Eligify\Data\Snapshot;
use CleaniqueCoders\Eligify\Data\Extractor;

// Option 1: Direct instantiation
$extracted = new Snapshot([
    'income' => 50000,
    'credit_score' => 720,
    'age' => 35,
]);

// Option 2: Via Extractor (recommended)
$extractor = Extractor::forModel(User::class);
$extracted = $extractor->extract($user);
```

### Accessing Data

```php
// Property syntax (cleanest)
$income = $extracted->income;

// Array syntax (familiar)
$income = $extracted['income'];

// Method with default value (safest)
$income = $extracted->get('income', 0);

// Check existence
if ($extracted->has('credit_score')) {
    // ...
}

// Check with isset
if (isset($extracted->income)) {
    // ...
}
```

## Data Selection

### Get Specific Fields

```php
// Get only financial fields
$financialData = $extracted->only([
    'income',
    'credit_score',
    'debt_ratio',
]);

// Remove sensitive fields
$safeData = $extracted->except([
    'ssn',
    'account_number',
    'password',
]);

// Get all data
$allData = $extracted->all(); // Returns array
```

## Filtering Data

### By Type

```php
// Get only numeric fields
$numbers = $extracted->numericFields();
// Returns: ['income' => 50000, 'age' => 35, 'credit_score' => 720]

// Get only string fields
$strings = $extracted->stringFields();
// Returns: ['name' => 'John', 'email' => 'john@example.com']

// Get only boolean fields
$flags = $extracted->booleanFields();
// Returns: ['is_verified' => true, 'has_loan' => false]
```

### By Pattern

```php
// Get fields matching a regex pattern
$loanFields = $extracted->whereKeyMatches('/^loan_/');
// Returns: ['loan_amount' => 10000, 'loan_term' => 36]

$countFields = $extracted->whereKeyMatches('/_count$/');
// Returns: ['active_loans_count' => 2, 'failed_payments_count' => 0]
```

### Custom Filters

```php
// Filter with custom callback
$highValues = $extracted->filter(function ($value, $key) {
    return is_numeric($value) && $value > 1000;
});

// Keep only positive numbers
$positive = $extracted->filter(fn($value) => is_numeric($value) && $value > 0);
```

## Data Transformation

### Transform Values

```php
// Round all numeric values
$rounded = $extracted->transform(function ($value, $key) {
    return is_numeric($value) ? round($value) : $value;
});

// Normalize strings to uppercase
$uppercase = $extracted->transform(function ($value, $key) {
    return is_string($value) ? strtoupper($value) : $value;
});

// Convert currency to cents
$toCents = $extracted->transform(function ($value, $key) {
    if (in_array($key, ['income', 'loan_amount', 'savings'])) {
        return (int) ($value * 100);
    }
    return $value;
});
```

### Merge Data

```php
// Add computed fields
$withRiskScore = $extracted->merge([
    'risk_score' => calculateRiskScore($extracted->toArray()),
    'approval_probability' => 0.85,
]);
```

## Method Chaining

Chain multiple operations for clean, readable code:

```php
$processedData = $extracted
    ->except(['ssn', 'password'])           // Remove sensitive fields
    ->numericFields()                         // Keep only numbers
    ->filter(fn($v) => $v > 0)               // Keep positive values
    ->transform(fn($v) => round($v, 2));     // Round to 2 decimals
```

## Metadata

Every `Snapshot` instance tracks metadata about the extraction:

```php
// Access metadata
$metadata = $extracted->metadata();
// Returns: ['extracted_at' => '2025-10-30T...', 'field_count' => 10, ...]

// Get specific metadata
$modelClass = $extracted->metadata('model_class');
$modelKey = $extracted->metadata('model_key');
$config = $extracted->metadata('extractor_config');
```

## Integration with Eligify

### Direct Evaluation

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

// Extract data from model
$extracted = Extractor::forModel(User::class)->extract($user);

// Evaluate directly (accepts Snapshot)
$result = app('eligify')->evaluate('loan_approval', $extracted);
```

### With Data Preprocessing

```php
// Extract and filter for specific evaluation
$financialData = Extractor::forModel(User::class)
    ->extract($user)
    ->only(['income', 'credit_score', 'debt_ratio']);

// Evaluate with filtered data
$result = app('eligify')->evaluate('quick_prescreen', $financialData);
```

### Backward Compatibility

The engine accepts both `Snapshot` and plain arrays:

```php
// Both work identically
$result1 = app('eligify')->evaluate('criteria', $extracted);
$result2 = app('eligify')->evaluate('criteria', $extracted->toArray());
$result3 = app('eligify')->evaluate('criteria', ['income' => 50000]);
```

## Immutability

`Snapshot` is immutable. All transformation methods return new instances:

```php
$original = new Snapshot(['income' => 50000]);
$filtered = $original->only(['income']);

// Original is unchanged
count($original); // Still has all original fields

// Attempting to modify throws exception
$original->income = 60000; // ❌ BadMethodCallException
$original['income'] = 60000; // ❌ BadMethodCallException
```

## Conversion & Export

### To Array

```php
// Get raw array
$array = $extracted->toArray();

// Use with legacy code
legacy_function($extracted->toArray());
```

### To JSON

```php
// Export as JSON string
$json = $extracted->toJson();

// Pretty JSON
$pretty = $extracted->toJson(JSON_PRETTY_PRINT);

// JSON includes data + metadata
/*
{
    "data": {
        "income": 50000,
        "credit_score": 720
    },
    "metadata": {
        "extracted_at": "2025-10-30T12:00:00Z",
        "field_count": 2,
        "model_class": "App\\Models\\User"
    }
}
*/
```

### To String

```php
// Automatic JSON conversion
echo $extracted; // Pretty-printed JSON
```

## Interfaces Implemented

`Snapshot` implements standard PHP and Laravel interfaces:

- `Illuminate\Contracts\Support\Arrayable`
- `Illuminate\Contracts\Support\Jsonable`
- `JsonSerializable`
- `ArrayAccess`
- `Countable`

This ensures compatibility with Laravel collections, JSON responses, and array functions.

## Performance Considerations

### Memory

`Snapshot` adds minimal overhead:

- Object wrapper: ~1KB
- Metadata array: ~0.5KB
- Total overhead: **~1.5KB per instance**

### Speed

Performance difference is negligible:

- Array access: ~0.15ms per 10,000 operations
- Property access: ~0.18ms per 10,000 operations
- Method access: ~0.22ms per 10,000 operations

**For typical use (1-100 evaluations per request), the difference is unmeasurable.**

## When to Use Snapshot

### ✅ Use Snapshot when

- Extracting data from Eloquent models
- Building evaluation pipelines with transformations
- Type safety is important
- You need metadata tracking
- Building APIs that return evaluation data
- Working with complex data filtering

### ⚠️ Use plain arrays when

- Quick one-off evaluations
- Simple test cases
- Performance is absolutely critical (millions of evaluations)
- Legacy code integration requires arrays

## Advanced Examples

### Multi-Stage Evaluation

```php
// Stage 1: Quick pre-screening
$preScreenData = $extracted->only(['credit_score', 'is_verified']);
$preScreenResult = Eligify::evaluate('quick_check', $preScreenData);

if ($preScreenResult['passed']) {
    // Stage 2: Detailed evaluation
    $detailedData = $extracted
        ->except(['ssn', 'password'])
        ->numericFields();

    $detailedResult = Eligify::evaluate('full_check', $detailedData);
}
```

### Conditional Transformation

```php
$processed = $extracted->transform(function ($value, $key) {
    // Different transformations by field type
    if (str_starts_with($key, 'amount_')) {
        return round($value, 2); // Currency
    }
    if (str_ends_with($key, '_score')) {
        return (int) $value; // Scores
    }
    if (str_ends_with($key, '_date')) {
        return Carbon::parse($value)->toDateString(); // Dates
    }
    return $value;
});
```

### Batch Processing

```php
$users = User::with('profile')->get();

$results = $users->map(function ($user) {
    return Extractor::forModel(User::class)
        ->extract($user)
        ->only(['income', 'credit_score'])
        ->numericFields();
})->map(function ($extracted) {
    return app('eligify')->evaluate('loan_approval', $extracted);
});
```

## See Also

- [Extractor Documentation](model-data-extraction.md)
- [Extractor Architecture](extractor-architecture.md)
- [Usage Guide](usage-guide.md)
- [Example 14: Snapshot Usage](../examples/14-snapshot-usage.php)
- [Quick Reference](quick-reference-model-extraction.md)
