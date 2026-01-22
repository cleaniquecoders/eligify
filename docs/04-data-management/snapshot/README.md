# Snapshot System

The snapshot system captures and preserves entity state at a specific point in time for eligibility evaluation.

## Overview

Eligify provides two complementary snapshot components:

1. **Snapshot DTO** (`CleaniqueCoders\Eligify\Data\Snapshot`) - An immutable in-memory data object for runtime operations
2. **Snapshot Model** (`CleaniqueCoders\Eligify\Models\Snapshot`) - An Eloquent model for persistent storage

Snapshots are useful for:

- **Audit Trails**: Preserve exact state during evaluation
- **Historical Analysis**: Re-evaluate past decisions with original data
- **Compliance**: Maintain records for regulatory requirements
- **Debugging**: Understand why a decision was made
- **Deduplication**: Avoid storing duplicate snapshots via checksum

## Documentation

- **[Usage Guide](usage.md)** - Complete guide to using snapshots
- **[Data Structure](data-structure.md)** - Snapshot DTO internals
- **[Persistence](persistence.md)** - Database storage and the Snapshot model

## Quick Example

### In-Memory Snapshot (DTO)

```php
use CleaniqueCoders\Eligify\Data\Extractor;

// Extract data from a model
$snapshot = Extractor::forModel(User::class)->extract($user);

// Access data
$income = $snapshot->get('income');
$data = $snapshot->toArray();
```

### Persistent Snapshot (Model)

```php
use CleaniqueCoders\Eligify\Models\Snapshot;

// Create and persist a snapshot
$snapshot = Snapshot::create([
    'uuid' => (string) str()->uuid(),
    'snapshotable_type' => User::class,
    'snapshotable_id' => $user->id,
    'data' => [
        'income' => $user->income,
        'credit_score' => $user->credit_score,
        'age' => $user->age,
    ],
    'meta' => ['source' => 'loan_application'],
]);

// Or use the helper method with deduplication
$snapshot = Snapshot::findOrCreateFromData(
    data: $userData,
    snapshotableType: User::class,
    snapshotableId: $user->id,
);

// Link to evaluation
$evaluation = Evaluation::create([
    'criteria_id' => $criteria->id,
    'snapshot_id' => $snapshot->id,
    'passed' => $result->passed(),
    // ...
]);

// Later: retrieve and verify integrity
$snapshot = Snapshot::find($id);
if ($snapshot->verifyIntegrity()) {
    $result = $criteria->evaluate($snapshot->toSnapshotData());
}
```

## Key Features

### Data Integrity

Each snapshot stores a SHA-256 checksum of its data:

```php
// Verify data hasn't been tampered with
if ($snapshot->verifyIntegrity()) {
    // Data is intact
}

// Checksum is automatically calculated on creation
$checksum = $snapshot->checksum;
```

### Deduplication

Avoid storing duplicate snapshots for the same entity:

```php
// Uses checksum to find existing snapshot or create new one
$snapshot = Snapshot::findOrCreateFromData(
    data: $userData,
    snapshotableType: User::class,
    snapshotableId: $user->id,
);
```

### Evaluation Linkage

Each evaluation can reference the snapshot it was based on:

```php
// Get the snapshot used for an evaluation
$evaluation = Evaluation::find($id);
$snapshot = $evaluation->snapshot;

// Get all evaluations for a snapshot
$snapshot = Snapshot::find($id);
$evaluations = $snapshot->evaluations;
```

## When to Use Snapshots

| Use Snapshots When | Don't Use Snapshots When |
|--------------------|--------------------------|
| You need audit trails | Real-time data is required |
| Decisions must be reproducible | Storage is severely constrained |
| State changes frequently | Evaluation is non-critical |
| Compliance requires historical records | Data is ephemeral |
| You want to re-evaluate with same data | Single-use evaluations only |

## Architecture

```text
User/Application Model
        │
        ▼
┌───────────────────┐
│   Extractor       │ ── extracts data ──▶ Snapshot DTO (in-memory)
└───────────────────┘                              │
                                                   ▼
                                    ┌──────────────────────────┐
                                    │   Snapshot Model         │
                                    │   (eligify_snapshots)    │
                                    └──────────────────────────┘
                                                   │
                                                   ▼
                                    ┌──────────────────────────┐
                                    │   Evaluation Model       │
                                    │   (eligify_evaluations)  │
                                    └──────────────────────────┘
```

## Next Steps

- Read the [Usage Guide](usage.md) for detailed examples
- Learn about the [Data Structure](data-structure.md) for DTO operations
- See [Persistence](persistence.md) for database storage patterns
