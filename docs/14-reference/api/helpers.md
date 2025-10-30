# Helper Functions

Eligify provides convenient helper functions to simplify common eligibility operations without needing to instantiate classes directly.

## Available Helpers

### `eligify_snapshot()`

Creates a snapshot of a model's data for evaluation purposes.

**Signature:**

```php
eligify_snapshot(string $model, Model $data): Snapshot
```

**Parameters:**

- `$model` (string) - Fully qualified class name of the model
- `$data` (Model) - Eloquent model instance to extract data from

**Returns:**

- `Snapshot` - Data snapshot object containing extracted model data

**Throws:**

- `Exception` - If the provided model class doesn't exist

**Usage Example:**

```php
use App\Models\User;

$user = User::find(1);

// Create a snapshot of the user's data
$snapshot = eligify_snapshot(User::class, $user);

// The snapshot can now be used for evaluation
```

**Use Cases:**

- Preparing model data for eligibility evaluation
- Creating audit-friendly snapshots of entity state
- Extracting relevant data from complex models

---

### `eligify_evaluate()`

Evaluates a criteria against a data snapshot to determine eligibility.

**Signature:**

```php
eligify_evaluate(string|Criteria $criteria, Snapshot $snapshot): mixed
```

**Parameters:**

- `$criteria` (string|Criteria) - Criteria name (string) or Criteria model instance
- `$snapshot` (Snapshot) - Data snapshot to evaluate against

**Returns:**

- Evaluation result (structure depends on criteria configuration)

**Behavior:**

- When using string: internally calls `eligify_find_criteria()` which uses `firstOrNew()`
- Returns new unsaved Criteria instance if not found (graceful handling)

**Usage Example:**

```php
use App\Models\User;
use CleaniqueCoders\Eligify\Models\Criteria;

$user = User::find(1);
$snapshot = eligify_snapshot(User::class, $user);

// Evaluate using criteria name (string)
$result = eligify_evaluate('Loan Approval', $snapshot);

// Or evaluate using Criteria model instance
$criteria = Criteria::where('name', 'Loan Approval')->first();
$result = eligify_evaluate($criteria, $snapshot);
```

**Configuration:**

- `saveEvaluation: false` - Evaluations are not persisted to database
- `useCache: false` - Results are not cached

**Use Cases:**

- Quick eligibility checks in controllers or services
- One-off evaluations without persistence requirements
- Testing eligibility rules programmatically

---

### `eligify_find_criteria()`

Finds or creates a new criteria instance by searching a specific field.

**Signature:**

```php
eligify_find_criteria(string $keyword, string $field = 'name'): Criteria
```

**Parameters:**

- `$keyword` (string) - The value to search for
- `$field` (string) - The field to search in (defaults to 'name')

**Returns:**

- `Criteria` - Existing criteria instance or new unsaved instance if not found

**Usage Example:**

```php
use CleaniqueCoders\Eligify\Models\Criteria;

// Find criteria by name (default field)
$criteria = eligify_find_criteria('Loan Approval');

// Find criteria by custom field
$criteria = eligify_find_criteria('LOAN-001', 'code');

// Returns a new instance if not found (not saved to database)
if (!$criteria->exists) {
    // Criteria doesn't exist yet
    $criteria->description = 'Auto-created criteria';
    $criteria->save();
}
```

**Use Cases:**

- Quick criteria lookup without throwing exceptions
- Creating criteria on-the-fly during evaluations
- Flexible criteria retrieval by different fields (code, slug, name)
- Safe criteria operations that won't fail on missing records

**Notes:**

- Uses `firstOrNew()` - returns new instance if not found (not persisted)
- Does not throw exceptions if criteria doesn't exist
- Useful for graceful degradation in evaluation workflows

---

## Combined Usage Pattern

The helper functions work seamlessly together for streamlined eligibility checks:

```php
use App\Models\LoanApplication;

// Get your model
$application = LoanApplication::find($id);

// Create snapshot and evaluate in one flow
$result = eligify_evaluate(
    'Loan Approval',
    eligify_snapshot(LoanApplication::class, $application)
);

// Handle result
if ($result->passed) {
    // Approve the loan
    $application->approve();
} else {
    // Reject with reasons
    $application->reject($result->failed_rules);
}
```

---

## Best Practices

### 1. **Use Type-Safe Parameters**

```php
// Good - explicit class reference
$snapshot = eligify_snapshot(User::class, $user);

// Avoid - string literals prone to typos
$snapshot = eligify_snapshot('App\\Models\\User', $user);
```

### 2. **Check for Criteria Existence**

```php
// eligify_find_criteria() returns a new instance if not found
$criteria = eligify_find_criteria('Loan Approval');

if (!$criteria->exists) {
    // Handle missing criteria gracefully
    Log::warning("Criteria 'Loan Approval' not found");
    return;
}

$result = eligify_evaluate($criteria, $snapshot);
```

### 3. **Cache Snapshots for Multiple Evaluations**

```php
$snapshot = eligify_snapshot(User::class, $user);

// Pre-load criteria for better performance
$loanCriteria = eligify_find_criteria('Loan Eligibility');
$creditCriteria = eligify_find_criteria('Credit Card Eligibility');
$mortgageCriteria = eligify_find_criteria('Mortgage Eligibility');

// Reuse snapshot for multiple criteria
$loanResult = eligify_evaluate($loanCriteria, $snapshot);
$creditResult = eligify_evaluate($creditCriteria, $snapshot);
$mortgageResult = eligify_evaluate($mortgageCriteria, $snapshot);
```

### 4. **Consider Performance**

The helper functions use `saveEvaluation: false` and `useCache: false` for simplicity. For production scenarios with high volume:

```php
// For persistent evaluations and caching, use the full API
use CleaniqueCoders\Eligify\Eligify;

$result = (new Eligify)->evaluate(
    criteria: $criteria,
    data: $snapshot,
    saveEvaluation: true,  // Persist for audit
    useCache: true         // Enable caching
);
```

---

## Additional Examples

### Finding Criteria by Different Fields

```php
// Find by name (default)
$criteria = eligify_find_criteria('Premium Membership');

// Find by code
$criteria = eligify_find_criteria('PREM-001', 'code');

// Find by slug
$criteria = eligify_find_criteria('premium-membership', 'slug');

// Check if criteria was found
if ($criteria->exists) {
    echo "Found: {$criteria->name}";
} else {
    echo "Creating new criteria";
    $criteria->name = 'Premium Membership';
    $criteria->save();
}
```

### Complete Evaluation Workflow

```php
use App\Models\User;

// Step 1: Get the entity
$user = User::find($userId);

// Step 2: Create snapshot
$snapshot = eligify_snapshot(User::class, $user);

// Step 3: Find or prepare criteria
$criteria = eligify_find_criteria('VIP Access');

// Step 4: Evaluate
if ($criteria->exists) {
    $result = eligify_evaluate($criteria, $snapshot);

    // Step 5: Act on result
    if ($result->passed) {
        $user->grantVipAccess();
    }
} else {
    Log::warning("VIP Access criteria not configured");
}
```

---

## Comparison: Helpers vs. Full API

| Feature | Helper Functions | Full API |
|---------|-----------------|----------|
| Ease of Use | ⭐⭐⭐⭐⭐ Simple | ⭐⭐⭐ More verbose |
| Persistence | ❌ No | ✅ Optional |
| Caching | ❌ No | ✅ Optional |
| Customization | ⭐⭐ Limited | ⭐⭐⭐⭐⭐ Full control |
| Exception Handling | Graceful (firstOrNew) | Explicit (firstOrFail) |
| Use Case | Quick checks | Production systems |

---

## Related Documentation

- [Evaluation Engine](/docs/03-core-features/evaluation-engine.md) - Full evaluation API
- [Data Snapshots](/docs/04-data-management/snapshots.md) - Snapshot system details
- [Criteria Builder](/docs/03-core-features/criteria-builder.md) - Creating criteria
- [Quick Start Guide](/docs/01-getting-started/quick-start.md) - Getting started with Eligify

---

## Notes

- Helper functions are auto-loaded via Composer's `files` autoload
- Available globally without importing
- Ideal for prototyping and simple use cases
- For production systems, consider using the full API for better control over persistence and caching
