# Snapshot Data Structure

This document describes the structure and properties of Snapshot objects in Eligify.

## Overview

Snapshots are immutable data objects that capture the state of a subject at a specific point in time. They preserve data for audit trails, historical evaluation, and compliance.

## Snapshot Object

### Structure

```php
CleaniqueCoders\Eligify\Data\Snapshot
```

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `id` | string | Unique snapshot identifier (UUID) |
| `subjectType` | string | Class name of the subject |
| `subjectId` | mixed | Subject identifier (ID) |
| `data` | array | Captured data at snapshot time |
| `metadata` | array | Additional metadata |
| `createdAt` | Carbon | Snapshot creation timestamp |
| `expiresAt` | ?Carbon | Optional expiry timestamp |

### Example

```php
$snapshot = Snapshot::create($user);

/*
Snapshot {
    +id: "550e8400-e29b-41d4-a716-446655440000"
    +subjectType: "App\Models\User"
    +subjectId: 123
    +data: [
        "income" => 5000,
        "credit_score" => 750,
        "age" => 35,
        "employment_years" => 8,
    ]
    +metadata: [
        "ip_address" => "192.168.1.1",
        "user_agent" => "Mozilla/5.0...",
        "session_id" => "abc123",
    ]
    +createdAt: Carbon @1635724800
    +expiresAt: Carbon @1638316800
}
*/
```

## Creating Snapshots

### From Model

```php
use CleaniqueCoders\Eligify\Data\Snapshot;

$user = User::find(1);
$snapshot = Snapshot::create($user);
```

### From Array

```php
$data = [
    'income' => 5000,
    'credit_score' => 750,
];

$snapshot = Snapshot::fromArray($data, metadata: [
    'source' => 'manual_entry',
]);
```

### With Metadata

```php
$snapshot = Snapshot::create($user, metadata: [
    'ip_address' => request()->ip(),
    'user_agent' => request()->userAgent(),
    'evaluated_by' => auth()->id(),
]);
```

### With Expiry

```php
$snapshot = Snapshot::create($user, expiresAt: now()->addDays(30));
```

## Accessing Snapshot Data

### Get All Data

```php
$data = $snapshot->data;
```

### Get Specific Field

```php
$income = $snapshot->get('income');
$creditScore = $snapshot->get('credit_score');
```

### Get with Default

```php
$phone = $snapshot->get('phone', 'N/A');
```

### Check if Field Exists

```php
if ($snapshot->has('credit_score')) {
    // Field exists
}
```

### Dot Notation Access

```php
$country = $snapshot->get('address.country');
$city = $snapshot->get('profile.location.city');
```

## Metadata

### Common Metadata Fields

```php
$snapshot = Snapshot::create($user, metadata: [
    // Request information
    'ip_address' => request()->ip(),
    'user_agent' => request()->userAgent(),
    'url' => request()->fullUrl(),

    // User information
    'evaluated_by' => auth()->id(),
    'session_id' => session()->getId(),

    // Context
    'reason' => 'loan_application',
    'department' => 'underwriting',
    'notes' => 'Expedited review requested',

    // System information
    'app_version' => config('app.version'),
    'environment' => app()->environment(),
]);
```

### Accessing Metadata

```php
$ipAddress = $snapshot->metadata['ip_address'] ?? null;
$evaluatedBy = $snapshot->metadata['evaluated_by'] ?? null;
```

## Persistence

### Save to Database

```php
$snapshot = Snapshot::create($user);
$snapshot->save();
```

Snapshots are stored in the `eligify_snapshots` table:

```php
Schema::create('eligify_snapshots', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('subject_type');
    $table->unsignedBigInteger('subject_id')->nullable();
    $table->json('data');
    $table->json('metadata')->nullable();
    $table->timestamp('created_at');
    $table->timestamp('expires_at')->nullable();

    $table->index(['subject_type', 'subject_id']);
    $table->index('created_at');
    $table->index('expires_at');
});
```

### Load from Database

```php
$snapshot = Snapshot::find($id);
```

### Find by Subject

```php
$snapshots = Snapshot::forSubject($user)->get();
```

### Find Latest

```php
$snapshot = Snapshot::forSubject($user)->latest()->first();
```

## Using Snapshots in Evaluation

### Evaluate with Snapshot

```php
$snapshot = Snapshot::create($user);

$result = Eligify::criteria('Loan Approval')
    ->evaluate($snapshot);
```

### Benefits

1. **Audit Trail**: Original data preserved
2. **Historical Evaluation**: Re-evaluate with past data
3. **Compliance**: Meet regulatory requirements
4. **Debugging**: Reproduce evaluation conditions

## Snapshot Lifecycle

### Creation

```php
$snapshot = Snapshot::create($user);
```

### Storage

```php
$snapshot->save();
```

### Retrieval

```php
$snapshot = Snapshot::find($id);
```

### Evaluation

```php
$result = $criteria->evaluate($snapshot);
```

### Expiry

```php
// Automatically expired snapshots are not retrieved
Snapshot::query()->whereNotExpired()->get();
```

### Cleanup

```php
// Delete expired snapshots
Snapshot::deleteExpired();

// Delete old snapshots
Snapshot::olderThan(90)->delete();
```

## Snapshot Methods

### Instance Methods

| Method | Return Type | Description |
|--------|-------------|-------------|
| `get($key, $default = null)` | mixed | Get data field |
| `has($key)` | bool | Check if field exists |
| `toArray()` | array | Convert to array |
| `save()` | bool | Save to database |
| `delete()` | bool | Delete snapshot |
| `isExpired()` | bool | Check if expired |
| `expiresIn()` | ?int | Days until expiry |

### Static Methods

| Method | Return Type | Description |
|--------|-------------|-------------|
| `create($subject, $metadata = [], $expiresAt = null)` | Snapshot | Create new snapshot |
| `fromArray($data, $metadata = [])` | Snapshot | Create from array |
| `find($id)` | ?Snapshot | Find by ID |
| `forSubject($subject)` | Builder | Query for subject |
| `deleteExpired()` | int | Delete expired |
| `olderThan($days)` | Builder | Query old snapshots |

## Example Usage

### Complete Workflow

```php
// 1. Create snapshot
$snapshot = Snapshot::create($user, metadata: [
    'reason' => 'loan_application',
    'ip_address' => request()->ip(),
]);

// 2. Save for audit
$snapshot->save();

// 3. Evaluate
$result = Eligify::criteria('Loan Approval')
    ->evaluate($snapshot);

// 4. Store result with snapshot reference
Evaluation::create([
    'snapshot_id' => $snapshot->id,
    'criteria_id' => $criteria->id,
    'result' => $result,
]);

// 5. Later: Re-evaluate with same data
$historicalResult = $criteria->evaluate($snapshot);
```

## Best Practices

### 1. Always Include Context

```php
$snapshot = Snapshot::create($user, metadata: [
    'purpose' => 'loan_approval_evaluation',
    'evaluated_by' => auth()->id(),
    'timestamp' => now()->toIso8601String(),
]);
```

### 2. Set Appropriate Expiry

```php
// Short-term (debugging)
$snapshot = Snapshot::create($user, expiresAt: now()->addDays(7));

// Long-term (compliance)
$snapshot = Snapshot::create($user, expiresAt: now()->addYears(7));

// Permanent
$snapshot = Snapshot::create($user); // No expiry
```

### 3. Clean Up Regularly

```php
// Schedule in Laravel
Schedule::command('eligify:cleanup-snapshots')->daily();
```

### 4. Use for Compliance

```php
// Preserve data for regulatory requirements
$snapshot = Snapshot::create($application, metadata: [
    'regulation' => 'GDPR',
    'retention_period' => '7_years',
    'data_classification' => 'sensitive',
]);
```

## Related Documentation

- [Snapshot Usage Guide](usage.md) - How to use snapshots
- [Data Management](../) - Overview
- [Audit System](../../07-advanced-features/) - Audit logging
