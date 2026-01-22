# Snapshot Persistence

This document covers the database storage of snapshots using the `Snapshot` Eloquent model.

## Overview

The `Snapshot` model (`CleaniqueCoders\Eligify\Models\Snapshot`) provides persistent storage for point-in-time data captures. Unlike the in-memory DTO, persisted snapshots survive between requests and can be linked to evaluations for audit purposes.

## Database Schema

### eligify_snapshots Table

```php
Schema::create('eligify_snapshots', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique()->index();
    $table->string('snapshotable_type');      // Polymorphic type (e.g., App\Models\User)
    $table->unsignedBigInteger('snapshotable_id');
    $table->json('data');                      // The captured snapshot data
    $table->string('checksum', 64)->index();   // SHA-256 hash for integrity
    $table->json('meta')->nullable();          // Additional metadata
    $table->timestamp('captured_at')->index();
    $table->timestamps();

    $table->index(['snapshotable_type', 'snapshotable_id']);
    $table->index(['checksum', 'snapshotable_type', 'snapshotable_id'], 'eligify_snapshots_dedup_index');
});
```

### Evaluation Link

The `eligify_evaluations` table includes a `snapshot_id` foreign key:

```php
Schema::table('eligify_evaluations', function (Blueprint $table) {
    $table->foreignId('snapshot_id')
        ->nullable()
        ->constrained('eligify_snapshots')
        ->nullOnDelete();
});
```

## Creating Snapshots

### Basic Creation

```php
use CleaniqueCoders\Eligify\Models\Snapshot;

$snapshot = Snapshot::create([
    'uuid' => (string) str()->uuid(),
    'snapshotable_type' => User::class,
    'snapshotable_id' => $user->id,
    'data' => [
        'income' => $user->income,
        'credit_score' => $user->credit_score,
        'age' => $user->age,
        'employment_status' => $user->employment_status,
    ],
    'meta' => [
        'source' => 'loan_application',
        'ip_address' => request()->ip(),
    ],
]);
```

### From Snapshot DTO

Convert an in-memory snapshot to a persistent one:

```php
use CleaniqueCoders\Eligify\Data\Extractor;
use CleaniqueCoders\Eligify\Models\Snapshot;

// Extract data using the Extractor
$snapshotDto = Extractor::forModel(User::class)->extract($user);

// Persist it
$snapshot = Snapshot::fromSnapshotData(
    snapshotData: $snapshotDto,
    snapshotableType: User::class,
    snapshotableId: $user->id,
    meta: ['reason' => 'credit_check'],
);
```

### With Deduplication

Avoid creating duplicate snapshots when data hasn't changed:

```php
// Returns existing snapshot if data matches, or creates new one
$snapshot = Snapshot::findOrCreateFromData(
    data: [
        'income' => $user->income,
        'credit_score' => $user->credit_score,
    ],
    snapshotableType: User::class,
    snapshotableId: $user->id,
    meta: ['source' => 'periodic_check'],
);
```

## Data Integrity

### Checksum Calculation

Each snapshot automatically calculates a SHA-256 checksum on creation:

```php
// Checksum is auto-calculated
$snapshot = Snapshot::create([
    'snapshotable_type' => User::class,
    'snapshotable_id' => 1,
    'data' => ['income' => 50000],
]);

echo $snapshot->checksum;
// "a1b2c3d4e5f6..."
```

### Verifying Integrity

Check if snapshot data has been modified:

```php
$snapshot = Snapshot::find($id);

if ($snapshot->verifyIntegrity()) {
    // Data matches checksum - safe to use
    $result = $criteria->evaluate($snapshot->toSnapshotData());
} else {
    // Data may have been tampered with
    Log::warning('Snapshot integrity check failed', [
        'snapshot_id' => $snapshot->id,
    ]);
}
```

## Relationships

### Snapshotable (Polymorphic)

```php
// Get the original entity
$user = $snapshot->snapshotable;

// Query snapshots for an entity
$snapshots = Snapshot::query()
    ->forSnapshotable(User::class, $userId)
    ->orderByDesc('captured_at')
    ->get();
```

### Evaluations

```php
// Get all evaluations that used this snapshot
$evaluations = $snapshot->evaluations;

// Get evaluation count
$count = $snapshot->evaluations()->count();
```

## Querying Snapshots

### By Entity

```php
// All snapshots for a user
$snapshots = Snapshot::forSnapshotable(User::class, $userId)->get();

// Latest snapshot for a user
$latest = Snapshot::forSnapshotable(User::class, $userId)
    ->orderByDesc('captured_at')
    ->first();
```

### By Date Range

```php
// Snapshots from last 30 days
$recent = Snapshot::capturedBetween(
    now()->subDays(30),
    now()
)->get();
```

### By Checksum

```php
// Find snapshot with specific checksum
$snapshot = Snapshot::byChecksum($checksumValue)->first();
```

## Converting to DTO

Use `toSnapshotData()` to convert a persisted snapshot to the in-memory DTO for evaluation:

```php
$snapshot = Snapshot::find($id);

// Convert to DTO
$snapshotDto = $snapshot->toSnapshotData();

// The DTO includes metadata about the persistent snapshot
$snapshotDto->metadata('snapshot_id');    // Original snapshot ID
$snapshotDto->metadata('checksum');       // Data checksum
$snapshotDto->metadata('captured_at');    // Capture timestamp

// Use in evaluation
$result = Eligify::criteria('Loan Approval')
    ->evaluate($snapshotDto->toArray());
```

## Accessing Snapshot Data

### Get Specific Field

```php
$income = $snapshot->getDataField('income');
$city = $snapshot->getDataField('address.city', 'Unknown');
```

### Check Field Exists

```php
if ($snapshot->hasDataField('credit_score')) {
    // Field exists in snapshot
}
```

### Get Summary

```php
$summary = $snapshot->getSummary();
/*
[
    'uuid' => '550e8400-e29b-41d4-a716-446655440000',
    'snapshotable' => 'App\Models\User:123',
    'field_count' => 5,
    'checksum' => 'a1b2c3...',
    'captured_at' => Carbon instance,
    'evaluations_count' => 3,
]
*/
```

## Linking to Evaluations

### During Evaluation

```php
use CleaniqueCoders\Eligify\Models\Evaluation;
use CleaniqueCoders\Eligify\Models\Snapshot;

// Create snapshot
$snapshot = Snapshot::findOrCreateFromData(
    data: $userData,
    snapshotableType: User::class,
    snapshotableId: $user->id,
);

// Perform evaluation
$result = Eligify::criteria('Loan Approval')
    ->evaluate($snapshot->toSnapshotData()->toArray());

// Store evaluation with snapshot reference
$evaluation = Evaluation::create([
    'criteria_id' => $criteria->id,
    'snapshot_id' => $snapshot->id,
    'evaluable_type' => User::class,
    'evaluable_id' => $user->id,
    'passed' => $result->passed(),
    'score' => $result->score(),
    'failed_rules' => $result->failedRules(),
    'rule_results' => $result->ruleResults(),
    'evaluated_at' => now(),
]);
```

### Retrieving Evaluation's Snapshot

```php
$evaluation = Evaluation::find($id);

// Get the snapshot used
$snapshot = $evaluation->snapshot;

if ($snapshot) {
    // Re-evaluate with same data
    $reResult = Eligify::criteria($evaluation->criteria)
        ->evaluate($snapshot->toSnapshotData()->toArray());
}
```

## Factory (Testing)

Use the factory for testing:

```php
use CleaniqueCoders\Eligify\Models\Snapshot;

// Basic snapshot
$snapshot = Snapshot::factory()->create();

// For specific entity
$snapshot = Snapshot::factory()
    ->forSnapshotable(User::class, $user->id)
    ->create();

// With specific data
$snapshot = Snapshot::factory()
    ->withData([
        'income' => 75000,
        'credit_score' => 720,
    ])
    ->create();

// Preset states
$loanSnapshot = Snapshot::factory()->loanApplication()->create();
$scholarshipSnapshot = Snapshot::factory()->scholarshipApplicant()->create();
$userSnapshot = Snapshot::factory()->userProfile()->create();
```

## Best Practices

### 1. Always Link Evaluations to Snapshots

```php
// Good: Evaluation references snapshot
$evaluation = Evaluation::create([
    'snapshot_id' => $snapshot->id,
    // ...
]);

// Avoid: Evaluation without snapshot (for audit-critical flows)
$evaluation = Evaluation::create([
    'context' => $data, // Data stored inline, not linked
    // ...
]);
```

### 2. Use Deduplication for Frequent Checks

```php
// Efficient: Reuses existing snapshot if data unchanged
$snapshot = Snapshot::findOrCreateFromData($data, $type, $id);
```

### 3. Verify Integrity for Compliance

```php
// For regulated industries
if (!$snapshot->verifyIntegrity()) {
    throw new IntegrityException('Snapshot data may have been modified');
}
```

### 4. Include Meaningful Metadata

```php
$snapshot = Snapshot::create([
    // ...
    'meta' => [
        'source' => 'api_v2',
        'triggered_by' => 'loan_application_submit',
        'ip_address' => request()->ip(),
        'user_agent' => request()->userAgent(),
    ],
]);
```

## Migration

Publish and run the migrations:

```bash
php artisan vendor:publish --tag="eligify-migrations"
php artisan migrate
```

## Related

- [Snapshot Data Structure](data-structure.md) - DTO internals
- [Database Schema](../../14-reference/database-schema.md) - Full schema reference
- [Models API](../../14-reference/api/models.md) - All Eligify models
