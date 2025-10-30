# CLI Commands Reference

Eligify provides a comprehensive set of Artisan commands for managing eligibility criteria, evaluations, and audits.

## Table of Contents

- [Status Commands](#status-commands)
- [Criteria Management](#criteria-management)
- [Evaluation Commands](#evaluation-commands)
- [Audit Commands](#audit-commands)
- [Code Generation](#code-generation)
  - [eligify:make-mapping](#eligifymake-mapping) - Generate model mapping class
- [Maintenance Commands](#maintenance-commands)
  - [eligify:benchmark](#eligifybenchmark) - Performance benchmarking
  - [eligify:cleanup-evaluations](#eligifycleanup-evaluations) - Clean old evaluations
  - [eligify:optimize](#eligifyoptimize) - Optimize database and cache
  - [eligify:export](#eligifyexport) - Export data
  - [eligify:import](#eligifyimport) - Import data
  - [eligify:seed](#eligifyseed) - Seed sample data
- [Scheduling Commands](#scheduling-commands)
- [Command Aliases](#command-aliases)
- [Tips and Tricks](#tips-and-tricks)

## Status Commands

### eligify

View package status, statistics, and health information.

```bash
php artisan eligify [action] [--format=]
```

**Actions:**

- `status` (default) - Show package status and recent criteria
- `stats` - Display detailed statistics
- `health` - Run system health checks

**Options:**

- `--format` - Output format: `table`, `json`, `csv` (default: `table`)

**Examples:**

```bash
# Show status
php artisan eligify status

# View statistics
php artisan eligify stats

# Health check
php artisan eligify health

# Export as JSON
php artisan eligify stats --format=json
```

**Output Example:**

```
🎯 Eligify Package Status

Component      Count  Status
────────────────────────────────
Criteria       12     ✅ Active
Rules          48     ✅ Active
Evaluations    1,234  ✅ Active
Audit Logs     5,678  ✅ Active

📋 Recent Criteria:
Name                 Slug                 Rules      Status     Created
─────────────────────────────────────────────────────────────────────────
Loan Approval       loan-approval        5 rules    ✅ Active  2025-10-27
Scholarship         scholarship          8 rules    ✅ Active  2025-10-26
```

## Criteria Management

### eligify:criteria

Manage eligibility criteria.

```bash
php artisan eligify:criteria {action} [arguments] [options]
```

**Actions:**

#### create

Create a new criteria interactively:

```bash
php artisan eligify:criteria create
```

**Interactive Prompts:**

```
Criteria name: Loan Approval
Description: Standard loan approval process
Pass threshold (0-100): 75
Add rules? (yes/no): yes

Rule 1:
Field name: credit_score
Operator (>=, <=, ==, etc.): >=
Value: 650
Weight (1-10): 8

Add another rule? (yes/no): yes
...
```

#### list

List all criteria:

```bash
php artisan eligify:criteria list [options]
```

**Options:**

- `--active` - Show only active criteria
- `--inactive` - Show only inactive criteria
- `--with-rules` - Include rule counts
- `--format=table|json|csv` - Output format

**Examples:**

```bash
# List all criteria
php artisan eligify:criteria list

# Active criteria only
php artisan eligify:criteria list --active

# With rule counts
php artisan eligify:criteria list --with-rules

# Export as JSON
php artisan eligify:criteria list --format=json
```

#### show

Show detailed information about a criteria:

```bash
php artisan eligify:criteria show {slug}
```

**Example:**

```bash
php artisan eligify:criteria show loan-approval
```

**Output:**

```
📋 Criteria: Loan Approval
Slug: loan-approval
Status: ✅ Active
Pass Threshold: 75%
Created: 2025-10-27 10:30:00

Rules (5):
1. credit_score >= 650 (weight: 8)
2. income >= 30000 (weight: 7)
3. employment_status in ['employed', 'self-employed'] (weight: 6)
4. debt_to_income_ratio <= 43 (weight: 5)
5. active_loans <= 3 (weight: 4)

Recent Evaluations (10):
ID    Score  Passed  Evaluated At
─────────────────────────────────────
123   85%    ✅      2025-10-27 10:00
122   62%    ❌      2025-10-27 09:45
...
```

#### activate

Activate a criteria:

```bash
php artisan eligify:criteria activate {slug}
```

#### deactivate

Deactivate a criteria:

```bash
php artisan eligify:criteria deactivate {slug}
```

#### delete

Delete a criteria (with confirmation):

```bash
php artisan eligify:criteria delete {slug} [--force]
```

**Options:**

- `--force` - Skip confirmation

#### duplicate

Create a copy of an existing criteria:

```bash
php artisan eligify:criteria duplicate {slug} {new-name}
```

**Example:**

```bash
php artisan eligify:criteria duplicate loan-approval loan-approval-tier-2
```

## Evaluation Commands

### eligify:evaluate

Evaluate eligibility from the command line.

```bash
php artisan eligify:evaluate {criteria} [options]
```

**Options:**

- `--data=` - JSON string of data to evaluate
- `--file=` - Path to JSON file with data
- `--interactive` - Interactive data entry
- `--save` - Save evaluation to database (default: true)
- `--no-save` - Don't save evaluation
- `--user=` - User ID to associate with evaluation
- `--verbose` - Show detailed output

**Examples:**

#### Inline Data

```bash
php artisan eligify:evaluate loan-approval \
    --data='{"credit_score":720,"income":55000,"employment":"employed"}'
```

#### From File

```bash
php artisan eligify:evaluate loan-approval --file=applicant-data.json
```

#### Interactive Mode

```bash
php artisan eligify:evaluate loan-approval --interactive
```

**Interactive Prompts:**

```
Enter value for credit_score: 720
Enter value for income: 55000
Enter value for employment: employed
Enter value for debt_to_income_ratio: 0.35
Enter value for active_loans: 2

Evaluating...

✅ PASSED
Score: 87%
Decision: Approved

Rule Results:
✅ credit_score >= 650 (actual: 720)
✅ income >= 30000 (actual: 55000)
✅ employment in ['employed','self-employed'] (actual: employed)
✅ debt_to_income_ratio <= 43 (actual: 0.35)
✅ active_loans <= 3 (actual: 2)

Evaluation saved with ID: 456
```

#### Batch Evaluation

Evaluate multiple records from a JSON file:

```bash
php artisan eligify:evaluate loan-approval --file=batch-applicants.json --batch
```

**JSON Format:**

```json
[
  {
    "credit_score": 720,
    "income": 55000,
    "employment": "employed"
  },
  {
    "credit_score": 680,
    "income": 45000,
    "employment": "self-employed"
  }
]
```

## Audit Commands

### eligify:audit

Query and export audit logs.

```bash
php artisan eligify:audit [options]
```

**Options:**

- `--event=` - Filter by event type
- `--from=` - Start date (Y-m-d)
- `--to=` - End date (Y-m-d)
- `--user=` - Filter by user ID
- `--limit=` - Number of records (default: 100)
- `--export=` - Export to file (csv, json)
- `--format=` - Output format (table, json, csv)

**Examples:**

#### View Recent Logs

```bash
php artisan eligify:audit --limit=50
```

#### Filter by Event

```bash
php artisan eligify:audit --event=evaluation_completed
```

#### Date Range

```bash
php artisan eligify:audit --from=2025-10-01 --to=2025-10-27
```

#### Filter by User

```bash
php artisan eligify:audit --user=123
```

#### Export to CSV

```bash
php artisan eligify:audit --export=audit-report.csv --from=2025-10-01
```

#### Export to JSON

```bash
php artisan eligify:audit --export=audit-logs.json --format=json
```

**Output Example:**

```
📊 Audit Logs

ID    Event                 Auditable        User  IP Address    Created At
──────────────────────────────────────────────────────────────────────────────
123   evaluation_completed  Evaluation #456  101   192.168.1.1   2025-10-27 10:00
122   rule_created         Rule #89          101   192.168.1.1   2025-10-27 09:45
121   criteria_activated   Criteria #5       102   192.168.1.2   2025-10-27 09:30
```

### eligify:cleanup-audit

Clean up old audit logs.

```bash
php artisan eligify:cleanup-audit [options]
```

**Options:**

- `--days=` - Delete logs older than X days (default: from config)
- `--dry-run` - Preview what would be deleted
- `--force` - Skip confirmation
- `--event=` - Only clean specific event types

**Examples:**

#### Default Cleanup

```bash
php artisan eligify:cleanup-audit
```

**Output:**

```
🗑️  Cleaning audit logs older than 365 days...

Found 1,234 audit logs to delete.
Continue? (yes/no): yes

✅ Deleted 1,234 audit logs.
```

#### Custom Retention

```bash
php artisan eligify:cleanup-audit --days=90
```

#### Dry Run

```bash
php artisan eligify:cleanup-audit --dry-run
```

**Output:**

```
🔍 Dry run mode - no data will be deleted

Would delete:
- 450 evaluation_completed logs
- 234 rule_executed logs
- 156 criteria_modified logs
─────────────────────────────────
Total: 840 logs
```

#### Specific Events

```bash
php artisan eligify:cleanup-audit --event=evaluation_completed --days=180
```

## Code Generation

### eligify:make-mapping

Generate a model mapping class for Eligify data extraction.

```bash
php artisan eligify:make-mapping {model} [options]
```

**Arguments:**

- `model` - Fully qualified model class name (e.g., `App\Models\User`)

**Options:**

- `--name=` - Custom name for the mapping class (in kebab-case)
- `--force` - Overwrite existing mapping class
- `--namespace=` - Custom namespace (default: `App\Eligify\Mappings`)

**Examples:**

```bash
# Generate mapping for User model
php artisan eligify:make-mapping "App\Models\User"

# Custom name
php artisan eligify:make-mapping "App\Models\User" --name=premium-user

# Custom namespace
php artisan eligify:make-mapping "App\Models\Order" --namespace="App\CustomMappings"

# Force overwrite
php artisan eligify:make-mapping "App\Models\User" --force
```

**Generated Output:**

The command automatically:

- Analyzes model database schema and attributes
- Detects and maps timestamp fields (created_at, updated_at, etc.)
- Discovers model relationships
- Creates common computed fields (is_verified, is_active, etc.)
- Generates field mappings for business-friendly names

**Example Generated Class:**

```php
namespace App\Eligify\Mappings;

use CleaniqueCoders\Eligify\Data\Mappings\AbstractModelMapping;

class UserMapping extends AbstractModelMapping
{
    public function getModelClass(): string
    {
        return 'App\Models\User';
    }

    protected array $fieldMappings = [
        'created_at' => 'registration_date',
        'email_verified_at' => 'email_verified_timestamp',
    ];

    protected array $relationshipMappings = [
        'orders.count' => 'orders_count',
        'orders.sum:total' => 'total_order_value',
    ];

    protected array $computedFields = [
        'is_verified' => null,
    ];

    public function __construct()
    {
        $this->computedFields = [
            'is_verified' => fn ($model) => !is_null($model->email_verified_at),
        ];
    }
}
```

**See Also:**

- [Model Mapping Generator Guide](make-mapping-command.md) - Detailed documentation
- [Model Mappings Overview](model-mappings.md) - Mapping concepts
- [Model Data Extraction](model-data-extraction.md) - Usage guide

## Maintenance Commands

### eligify:benchmark

Run performance benchmarks for Eligify.

```bash
php artisan eligify:benchmark [options]
```

**Options:**

- `--iterations=` - Number of iterations to run (default: 100)
- `--type=` - Benchmark type: `simple`, `complex`, `batch`, `cache`, `all` (default: all)
- `--format=` - Output format: `table`, `json` (default: table)

**Benchmark Types:**

- `simple` - Basic evaluation with 3 rules
- `complex` - Complex evaluation with 8+ rules
- `batch` - Batch processing (100 and 1000 items)
- `cache` - Cache performance comparison
- `all` - Run all benchmarks

**Examples:**

```bash
# Run all benchmarks (100 iterations)
php artisan eligify:benchmark

# Quick test with fewer iterations
php artisan eligify:benchmark --iterations=10

# Accurate test with more iterations
php artisan eligify:benchmark --iterations=1000

# Test specific benchmark type
php artisan eligify:benchmark --type=simple
php artisan eligify:benchmark --type=complex
php artisan eligify:benchmark --type=batch
php artisan eligify:benchmark --type=cache

# Get JSON output for CI/CD
php artisan eligify:benchmark --format=json --iterations=100

# Combined options
php artisan eligify:benchmark --type=batch --iterations=500
```

**Output Example:**

```
🚀 Eligify Performance Benchmarks
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📊 Iterations: 100
⚡ Environment: production
🐘 PHP Version: 8.4.0
📦 Laravel Version: 11.9.0

📈 Testing: Simple Evaluation - 3 basic rules

Metric          Value
────────────────────────
Average Time    12.45 ms
Min Time        8.23 ms
Max Time        25.67 ms
Median Time     11.89 ms
Throughput      80.32 req/s
Avg Memory      2.5 MB
Peak Memory     3.1 MB
Iterations      100

   ⏱️  Average: 12.45 ms
   ⚡ Throughput: 80.32 req/s
   💾 Memory: 2.5 MB (peak: 3.1 MB)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✅ Benchmark Summary

   📊 Total tests run: 4
   ⏱️  Overall average: 35.21 ms

💡 Tip: Run with --iterations=1000 for more accurate results
📖 Docs: See docs/performance-benchmarking.md for optimization tips
```

**Use Cases:**

- Development testing
- Pre-production validation
- CI/CD performance regression testing
- Production monitoring
- Performance optimization verification

**See Also:** [Performance Benchmarking Documentation](performance-benchmarking.md)

---

### eligify:cleanup-evaluations

Clean up old evaluation records.

```bash
php artisan eligify:cleanup-evaluations [options]
```

**Options:**

- `--days=` - Delete evaluations older than X days (default: 730)
- `--passed-only` - Only delete passed evaluations
- `--failed-only` - Only delete failed evaluations
- `--dry-run` - Preview deletions
- `--force` - Skip confirmation

**Examples:**

```bash
# Clean evaluations older than 2 years
php artisan eligify:cleanup-evaluations --days=730

# Clean old passed evaluations only
php artisan eligify:cleanup-evaluations --days=365 --passed-only

# Dry run
php artisan eligify:cleanup-evaluations --dry-run
```

### eligify:optimize

Optimize database tables and cache.

```bash
php artisan eligify:optimize [options]
```

**Options:**

- `--tables` - Optimize database tables
- `--cache` - Clear and rebuild cache
- `--all` - Optimize everything

**Examples:**

```bash
# Optimize tables
php artisan eligify:optimize --tables

# Clear and rebuild cache
php artisan eligify:optimize --cache

# Full optimization
php artisan eligify:optimize --all
```

### eligify:export

Export criteria, rules, and evaluations.

```bash
php artisan eligify:export {file} [options]
```

**Options:**

- `--type=` - What to export: `criteria`, `rules`, `evaluations`, `all` (default: all)
- `--criteria=` - Export specific criteria (by slug)
- `--format=` - Export format: `json`, `csv` (default: json)
- `--with-evaluations` - Include evaluations
- `--from=` - Start date for evaluations
- `--to=` - End date for evaluations

**Examples:**

```bash
# Export all data
php artisan eligify:export backup.json

# Export specific criteria
php artisan eligify:export loan-data.json --criteria=loan-approval

# Export evaluations to CSV
php artisan eligify:export evaluations.csv --type=evaluations --format=csv

# Export with date range
php artisan eligify:export report.json --from=2025-10-01 --to=2025-10-27
```

### eligify:import

Import criteria and rules from file.

```bash
php artisan eligify:import {file} [options]
```

**Options:**

- `--validate` - Validate before importing
- `--dry-run` - Preview import without saving
- `--overwrite` - Overwrite existing criteria
- `--force` - Skip confirmation

**Examples:**

```bash
# Import from file
php artisan eligify:import criteria.json

# Validate before import
php artisan eligify:import criteria.json --validate

# Dry run
php artisan eligify:import criteria.json --dry-run

# Overwrite existing
php artisan eligify:import criteria.json --overwrite --force
```

### eligify:seed

Seed database with sample data.

```bash
php artisan eligify:seed [options]
```

**Options:**

- `--criteria=` - Number of criteria to create (default: 5)
- `--rules=` - Rules per criteria (default: 5)
- `--evaluations=` - Evaluations per criteria (default: 20)

**Example:**

```bash
php artisan eligify:seed --criteria=10 --rules=8 --evaluations=50
```

## Scheduling Commands

Add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Clean audit logs daily
    $schedule->command('eligify:cleanup-audit')
        ->daily()
        ->at('02:00');

    // Clean old evaluations weekly
    $schedule->command('eligify:cleanup-evaluations --days=365')
        ->weekly()
        ->sundays()
        ->at('03:00');

    // Optimize monthly
    $schedule->command('eligify:optimize --all')
        ->monthly()
        ->at('04:00');

    // Generate weekly report
    $schedule->command('eligify:audit --export=weekly-audit.csv --from=-7days')
        ->weekly()
        ->mondays()
        ->at('08:00');
}
```

## Command Aliases

Add aliases to `.bashrc` or `.zshrc`:

```bash
alias eli='php artisan eligify'
alias eli:crit='php artisan eligify:criteria'
alias eli:eval='php artisan eligify:evaluate'
alias eli:audit='php artisan eligify:audit'
```

**Usage:**

```bash
eli status
eli:crit list --active
eli:eval loan-approval --interactive
eli:audit --event=evaluation_completed
```

## Tips and Tricks

### 1. JSON Piping

```bash
# Pipe evaluation results to jq
php artisan eligify:evaluate loan-approval --data='...' --format=json | jq '.score'
```

### 2. Batch Processing

```bash
# Process multiple files
for file in data/*.json; do
    php artisan eligify:evaluate loan-approval --file="$file"
done
```

### 3. Monitoring

```bash
# Watch for new evaluations
watch -n 5 'php artisan eligify stats'
```

### 4. Backup Before Cleanup

```bash
# Export before cleaning
php artisan eligify:export backup-$(date +%Y%m%d).json
php artisan eligify:cleanup-audit --days=90
```

### 5. Cron Integration

```bash
# Daily evaluation report
0 8 * * * cd /path/to/app && php artisan eligify:audit --export=/reports/daily-$(date +\%Y\%m\%d).csv --from=-1day
```

## Next Steps

- [Configuration Guide](configuration.md)
- [Usage Guide](usage-guide.md)
- [Advanced Features](advanced-features.md)
