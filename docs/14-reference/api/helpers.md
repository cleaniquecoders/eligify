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

- `InvalidArgumentException` - If the provided model class doesn't exist

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
eligify_evaluate(string|Criteria $criteria, Snapshot $snapshot, bool $saveEvaluation = false, bool $useCache = false): mixed
```

**Parameters:**

- `$criteria` (string|Criteria) - Criteria name (string) or Criteria model instance
- `$snapshot` (Snapshot) - Data snapshot to evaluate against

**Returns:**

- Evaluation result (structure depends on criteria configuration)

**Behavior:**

- When using string: internally calls `eligify_find_criteria()` which searches by slug first, then by exact name
- If not found, returns a new unsaved Criteria instance (graceful handling)

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

**Flags:**

- `saveEvaluation` (bool) - Persist evaluations for audit (default: false)
- `useCache` (bool) - Cache evaluation results (default: false)

**Use Cases:**

- Quick eligibility checks in controllers or services
- One-off evaluations without persistence requirements
- Testing eligibility rules programmatically

---

### `eligify_find_criteria()`

Smart criteria finder that prefers slug, then falls back to exact name.

**Signature:**

```php
eligify_find_criteria(string $keyword, bool $createIfMissing = false): Criteria
```

**Parameters:**

- `$keyword` (string) - Slug or exact name to search for
- `$createIfMissing` (bool) - When true, returns a new (unsaved) instance prefilled if not found

**Returns:**

- `Criteria` - Existing instance or a new unsaved instance if not found

**Usage Example:**

```php
use CleaniqueCoders\Eligify\Models\Criteria;

// Find by slug or exact name
$criteria = eligify_find_criteria('loan-approval');
$criteria = eligify_find_criteria('Loan Approval');

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

- Searches slug first, then exact name
- Returns a new (unsaved) Criteria when not found (non-throwing)
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

### Smart finding by slug or name

```php
// Slug or name both work
$criteria = eligify_find_criteria('premium-membership');
$criteria = eligify_find_criteria('Premium Membership');

if (! $criteria->exists) {
    // Create and persist on demand
    $criteria->description = 'Auto-created';
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
| Exception Handling | Graceful (non-throwing finder) | Explicit (firstOrFail) |
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

---

## Related Eloquent helper trait

While not a global helper, Eligify ships a small Eloquent trait to attach criteria to any model:

```php
use CleaniqueCoders\Eligify\Concerns\HasCriteria;

class User extends Model {
    use HasCriteria;
}

// Attach criteria IDs
$user->attachCriteria([$criteriaId]);

// Query by type or tags
$user->criteriaOfType(['subscription', 'feature'])->get();
$user->criteriaTagged(['beta'])->get();
```

See the full model reference: [Models API](models.md).

---

## Additional convenience helpers

### `eligify_criteria_of()`

Query a model's attached criteria (requires the model to use HasCriteria). Supports optional filters: type, group, category, tags.

**Signature:**

```php
eligify_criteria_of(Model $model, array $filters = []): \Illuminate\Database\Eloquent\Relations\MorphToMany
```

**Usage:**

```php
// All criteria
$query = eligify_criteria_of($user);

// Filtered
$query = eligify_criteria_of($user, [
    'type' => ['subscription', 'feature'],
    'tags' => ['beta'],
]);

$criteria = $query->get();
```

### `eligify_attach_criteria()`

Attach criteria to a model without detaching existing. Accepts IDs, slugs/names, or Criteria instances.

**Signature:**

```php
eligify_attach_criteria(Model $model, array|string|int|Criteria $criteria): void
```

**Usage:**

```php
eligify_attach_criteria($user, [$criteriaId]);
eligify_attach_criteria($user, 'loan-approval');
eligify_attach_criteria($user, Criteria::first());
```
