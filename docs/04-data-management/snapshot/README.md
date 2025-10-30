# Snapshot System

The snapshot system captures and preserves entity state at a specific point in time for eligibility evaluation.

## Overview

Snapshots are useful for:
- **Audit Trails**: Preserve exact state during evaluation
- **Historical Analysis**: Re-evaluate past decisions
- **Compliance**: Maintain records for regulatory requirements
- **Debugging**: Understand why a decision was made

## Documentation

- **[Usage Guide](usage.md)** - Complete guide to using snapshots
- **[Data Structure](data-structure.md)** - Snapshot object internals

## Quick Example

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

// Create a snapshot
$snapshot = Eligify::snapshot($user, 'loan_application')
    ->withContext([
        'requested_amount' => 50000,
        'application_date' => now(),
    ])
    ->save();

// Evaluate using the snapshot
$result = $snapshot->evaluate();

// Later: retrieve and re-evaluate
$historical = Snapshot::find($id);
$result = $historical->evaluate(); // Uses preserved state
```

## Key Benefits

1. **Immutability**: Once created, snapshot data doesn't change
2. **Traceability**: Link snapshots to evaluations in audit logs
3. **Reproducibility**: Re-run evaluations with exact same data
4. **Compliance**: Meet regulatory requirements for decision records

## When to Use Snapshots

✅ **Use snapshots when:**
- You need audit trails
- Decisions must be reproducible
- State changes frequently
- Compliance requires historical records

❌ **Don't use snapshots when:**
- Real-time data is required
- Storage is a concern
- Evaluation is non-critical

## Next Steps

Read the [Usage Guide](usage.md) for detailed examples and patterns.
