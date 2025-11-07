# Rule Versioning

Rule versioning allows you to track changes to your criteria over time and evaluate against historical versions. This is useful for:

- **Audit Trails**: See exactly what rules were in effect for past evaluations
- **A/B Testing**: Compare how different rule versions affect outcomes
- **Compliance**: Meet regulatory requirements for decision traceability
- **Rollback**: Revert to previous rule configurations if needed

## Table of Contents

- [Creating Versions](#creating-versions)
- [Accessing Versions](#accessing-versions)
- [Evaluating Against Historical Versions](#evaluating-against-historical-versions)
- [Comparing Versions](#comparing-versions)
- [Version Metadata](#version-metadata)
- [Version Workflow](#version-workflow)

## Creating Versions

Automatically create a version snapshot whenever you modify criteria:

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

// Create initial criteria
$builder = Eligify::criteria('Loan Approval')
    ->description('Initial version of loan approval rules')
    ->addRule('credit_score', '>=', 650)
    ->addRule('income', '>=', 30000)
    ->passThreshold(70)
    ->save();

// Create version snapshot (automatically increments version number)
$criteria = $builder->getCriteria();
$version1 = $criteria->createVersion('Initial rules for Q1 2024');

// Later, modify the criteria
$builder->addRule('employment_months', '>=', 12)->save();

// Create new version snapshot
$version2 = $criteria->createVersion('Added employment verification for Q2 2024');

// Version history is automatically maintained
$allVersions = $criteria->getVersionNumbers(); // [1, 2]
```

## Accessing Versions

Retrieve specific versions or the latest:

```php
// Get a specific version by number
$version1 = $criteria->version(1);
$rules = $version1->getRulesSnapshot(); // Array of rules as they were

// Get the latest version
$latestVersion = $criteria->latestVersion();

// Check if a version exists
if ($criteria->hasVersion(2)) {
    // Version 2 exists
}

// Get all version numbers
$versionNumbers = $criteria->getVersionNumbers(); // [1, 2, 3]
```

## Evaluating Against Historical Versions

Evaluate data using rules from a specific version:

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

$applicant = [
    'credit_score' => 680,
    'income' => 35000,
    'employment_months' => 6,
];

// Evaluate using current rules (version 2)
$currentResult = Eligify::evaluate('Loan Approval', $applicant);

// Evaluate using version 1 rules (without employment requirement)
$version1Result = Eligify::evaluateVersion('Loan Approval', 1, $applicant);

// Compare outcomes
if ($currentResult['passed'] !== $version1Result['passed']) {
    echo "Rule change affected this applicant's eligibility";
}
```

## Comparing Versions

Understand what changed between versions:

```php
// Compare two versions
$differences = $criteria->compareVersions(1, 2);

// Get added rules
$addedRules = $differences['added'];

// Get removed rules
$removedRules = $differences['removed'];

// Get modified rules (with before/after snapshots)
$modifiedRules = $differences['modified'];

foreach ($modifiedRules as $change) {
    echo "Rule ID {$change['rule_id']} was modified:";
    echo "Before: {$change['before']['field']} {$change['before']['operator']} {$change['before']['value']}";
    echo "After: {$change['after']['field']} {$change['after']['operator']} {$change['after']['value']}";
}
```

## Version Metadata

Store additional information about each version:

```php
$version = $criteria->createVersion(
    'Updated for compliance with new regulations',
);

// Add or update metadata
$version->update([
    'meta' => [
        'created_by' => auth()->id(),
        'approval_status' => 'approved',
        'effective_date' => now()->addDays(7),
        'compliance_notes' => 'Meets FCRA requirements',
        'tags' => ['compliance', 'q2-2024'],
    ],
]);

// Retrieve metadata
$metadata = $version->meta;
$createdBy = $version->meta['created_by'];
$effectiveDate = $version->meta['effective_date'];
```

## Version Workflow

Common patterns for managing versions:

### Track Evaluation Against Specific Version in Audit Logs

```php
$evaluation = Eligify::evaluate('Loan Approval', $applicant);

// Log which version was used
AuditLog::create([
    'event' => 'evaluation_with_version',
    'auditable_type' => 'Loan Application',
    'auditable_id' => $application->id,
    'meta' => [
        'criteria_version' => $criteria->current_version,
        'score' => $evaluation['score'],
        'passed' => $evaluation['passed'],
    ],
]);
```

### Implement Version-Aware Policy Decisions

```php
// Store version used in evaluation record
$evaluation = Eligify::evaluate('Loan Approval', $applicant);

Evaluation::create([
    'criteria_id' => $criteria->id,
    'criteria_version' => $criteria->current_version,
    'passed' => $evaluation['passed'],
    'score' => $evaluation['score'],
    'meta' => [
        'rules_count' => count($evaluation['rules']),
    ],
]);

// Later, query how version affected outcomes
$passRateByVersion = Evaluation::where('criteria_id', $criteria->id)
    ->groupBy('meta->version')
    ->selectRaw('meta->version as version, avg(CASE WHEN passed THEN 1 ELSE 0 END) as pass_rate')
    ->get();
```

### Archive Old Versions and Rules

```php
// Keep only last 10 versions
$criteria->versions()
    ->orderByDesc('version')
    ->offset(10)
    ->delete();

// Or soft delete for compliance
$criteria->versions()
    ->where('version', '<', $oldVersionThreshold)
    ->delete();
```

## Next Steps

- [Advanced Features](README.md) - Back to Advanced Features guide
- [Examples](../13-examples/README.md) - See real-world implementations
- [API Reference](../14-reference/api-reference.md) - Complete API documentation
