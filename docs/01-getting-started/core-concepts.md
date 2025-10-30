# Core Concepts

Understanding the fundamental concepts of Eligify will help you build powerful eligibility systems.

## The Eligibility Model

Eligify follows a simple but powerful model:

```plaintext
Criteria → Rules → Evaluation → Result → Action
```

### 1. Criteria

A **criterion** (plural: criteria) is a named collection of rules that define what makes something eligible.

```php
$criteria = Eligify::criteria('Loan Approval');
```

**Key characteristics:**

- Has a unique name/identifier
- Contains one or more rules
- Defines a scoring method
- Can have workflow callbacks

### 2. Rules

A **rule** is a single condition that must be checked during evaluation.

```php
->addRule('income', '>=', 3000, weight: 30)
```

**Components of a rule:**

- **Field**: The data field to check (`'income'`)
- **Operator**: The comparison operator (`'>='`)
- **Value**: The expected value (`3000`)
- **Weight** (optional): Importance in scoring (`30`)

### 3. Evaluation

**Evaluation** is the process of checking whether an entity meets the criteria.

```php
$result = $criteria->evaluate($applicant);
```

**What happens during evaluation:**

1. Data is extracted from the subject
2. Each rule is checked against the data
3. Scores are calculated based on the method
4. Results are compiled into a result object
5. Audit log is created (if enabled)
6. Workflow callbacks are triggered

### 4. Result

A **result** object contains the outcome of the evaluation.

```php
$result->passed();          // true/false
$result->score();           // 0-100 or custom
$result->failedRules();     // array of failed rule identifiers
$result->passedRules();     // array of passed rule identifiers
$result->details();         // detailed breakdown
```

### 5. Actions/Workflows

**Workflows** are callbacks that execute based on evaluation results.

```php
->onPass(fn($subject) => $subject->approve())
->onFail(fn($subject) => $subject->reject())
```

## Data Flow

### Input Data Formats

Eligify accepts multiple data formats:

#### Array Data

```php
$data = ['income' => 5000, 'credit_score' => 750];
$result = $criteria->evaluate($data);
```

#### Eloquent Models

```php
$user = User::find(1);
$result = $criteria->evaluate($user);
```

#### Custom Objects

```php
$applicant = new LoanApplicant();
$result = $criteria->evaluate($applicant);
```

### Data Extraction

Eligify uses **Model Mappers** to extract data:

```php
use CleaniqueCoders\Eligify\Support\Mappers\BaseMapper;

class UserMapper extends BaseMapper
{
    protected function mapping(): array
    {
        return [
            'income' => $this->model->income,
            'credit_score' => $this->model->credit_score,
            'active_loans' => $this->model->loans()->active()->count(),
        ];
    }
}
```

## Scoring Methods

### Weighted Scoring (Default)

Each rule has a weight, total score is out of 100:

```php
->setScoring('weighted')
->addRule('income', '>=', 3000, weight: 40)
->addRule('credit_score', '>=', 650, weight: 60)

// Passing both rules = 100
// Passing income only = 40
// Passing credit_score only = 60
```

### Pass/Fail Scoring

All rules must pass to succeed:

```php
->setScoring('pass_fail')

// All rules pass = 100
// Any rule fails = 0
```

### Sum Scoring

Score is the sum of all rule weights:

```php
->setScoring('sum')
->addRule('feature_a', 'exists', weight: 10)
->addRule('feature_b', 'exists', weight: 20)
->addRule('feature_c', 'exists', weight: 15)

// If all pass, score = 45
```

### Average Scoring

Score is the average of passed rules:

```php
->setScoring('average')

// 3 rules, 2 pass = 66.67
// 3 rules, 3 pass = 100
```

## Operators

### Comparison Operators

- `==`, `equals` - Exact match
- `!=`, `not_equals` - Not equal
- `>`, `greater_than` - Greater than
- `>=`, `gte` - Greater or equal
- `<`, `less_than` - Less than
- `<=`, `lte` - Less or equal

### Membership Operators

- `in`, `contains` - Value in array
- `not_in` - Value not in array
- `between` - Value in range (inclusive)

### Existence Operators

- `empty` - Field is empty/null
- `not_empty` - Field has a value
- `exists` - Field exists
- `not_exists` - Field doesn't exist

### Pattern Operators

- `matches` - Regular expression match
- `starts_with` - String starts with
- `ends_with` - String ends with
- `contains_text` - String contains substring

## Persistence

### Saving Criteria

```php
$criteria = Eligify::criteria('Loan Approval')
    ->addRule('income', '>=', 3000)
    ->save();
```

This stores the criteria in the database for reuse.

### Loading Criteria

```php
$criteria = Eligify::load('loan_approval');
$result = $criteria->evaluate($applicant);
```

### Updating Criteria

```php
$criteria = Eligify::load('loan_approval')
    ->addRule('employment_status', '==', 'employed')
    ->save();
```

## Audit Trail

Every evaluation creates an audit record (if enabled):

```php
'audit' => [
    'enabled' => true,
    'retention_days' => 90,
],
```

**Audit records include:**

- Criteria used
- Subject identifier
- Input data snapshot
- Evaluation result
- Timestamp
- User (if authenticated)

Access audit logs:

```php
use CleaniqueCoders\Eligify\Models\Evaluation;

$logs = Evaluation::where('criteria_name', 'loan_approval')
    ->where('passed', true)
    ->get();
```

## Snapshots

**Snapshots** capture the state of data at evaluation time:

```php
use CleaniqueCoders\Eligify\Data\Snapshot;

$snapshot = Snapshot::create($user);

// Later, evaluate using the snapshot
$result = $criteria->evaluate($snapshot);
```

**Benefits:**

- Preserve data state for compliance
- Re-evaluate with historical data
- Audit trail with original values

## Events

Eligify emits events at key points:

```php
use CleaniqueCoders\Eligify\Events\CriteriaCreated;
use CleaniqueCoders\Eligify\Events\EvaluationCompleted;
use CleaniqueCoders\Eligify\Events\RuleAdded;

Event::listen(EvaluationCompleted::class, function ($event) {
    Log::info('Evaluation completed', [
        'criteria' => $event->criteria->name,
        'passed' => $event->result->passed(),
    ]);
});
```

## Best Practices

### 1. Name Criteria Clearly

```php
// Good
Eligify::criteria('Premium Membership Upgrade')

// Bad
Eligify::criteria('Check1')
```

### 2. Use Weights Strategically

```php
// Important criteria get higher weights
->addRule('identity_verified', '==', true, weight: 50)
->addRule('email_confirmed', '==', true, weight: 30)
->addRule('profile_complete', '==', true, weight: 20)
```

### 3. Handle Edge Cases

```php
->addRule('income', '>=', 3000)
->addRule('income', 'not_empty') // Ensure field exists
```

### 4. Use Model Mappers

Don't access model attributes directly in rules. Use mappers for complex logic:

```php
// In mapper
'debt_to_income_ratio' => $this->model->total_debt / $this->model->annual_income

// In criteria
->addRule('debt_to_income_ratio', '<=', 0.4)
```

### 5. Test Thoroughly

```php
test('loan approval criteria', function () {
    $criteria = Eligify::criteria('Loan Approval')
        ->addRule('income', '>=', 3000)
        ->addRule('credit_score', '>=', 650);

    $qualified = ['income' => 5000, 'credit_score' => 750];
    expect($criteria->evaluate($qualified)->passed())->toBeTrue();

    $unqualified = ['income' => 2000, 'credit_score' => 600];
    expect($criteria->evaluate($unqualified)->passed())->toBeFalse();
});
```

## Next Steps

- [Usage Guide](usage-guide.md) - Comprehensive examples
- [Core Features](../03-core-features/) - Advanced features
- [Architecture](../02-architecture/) - System design
- [Examples](../13-examples/) - Real-world use cases
