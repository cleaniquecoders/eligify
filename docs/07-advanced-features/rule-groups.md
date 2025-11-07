# Rule Groups

**Define criteria using logical groups of rules with advanced combination strategies.**

Rule Groups organize your eligibility rules into meaningful collections, enabling you to build sophisticated decision logic with clear, maintainable structure.

## Introduction

### Why Rule Groups

Consider a loan approval system with different evaluation logic for different aspects:

- **Identity Verification**: ALL checks must pass
- **Financial Requirements**: ALL conditions must be met
- **Contact Methods**: AT LEAST 2 of 3 must verify

Without groups, this intent gets lost in a flat rule list.

### Without Groups (Unclear)

```php
$result = Eligify::criteria('Loan Approval')
    ->addRule('ssn_verified', '==', true)
    ->addRule('address_verified', '==', true)
    ->addRule('credit_score', '>=', 650)
    ->addRule('income', '>=', 30000)
    ->addRule('email_verified', '==', true)
    ->addRule('phone_verified', '==', true)
    ->evaluate($applicant);
```

### With Groups (Clear)

```php
$result = Eligify::criteria('Loan Approval')
    ->addGroup('identity', fn($g) => $g
        ->addRule('ssn_verified', '==', true)
        ->addRule('address_verified', '==', true)
        ->requireAll()
    )
    ->addGroup('financial', fn($g) => $g
        ->addRule('credit_score', '>=', 650)
        ->addRule('income', '>=', 30000)
        ->requireAll()
    )
    ->addGroup('verification', fn($g) => $g
        ->addRule('email_verified', '==', true)
        ->addRule('phone_verified', '==', true)
        ->requireMin(2)
    )
    ->requireAllGroups(['identity', 'financial'])
    ->evaluate($applicant);
```

## Creating Groups

### Basic Syntax

```php
$criteria = Eligify::criteria('Loan Approval')
    ->addGroup('identity', function ($group) {
        $group->addRule('ssn_verified', '==', true);
        $group->addRule('address_verified', '==', true);
        $group->requireAll();
    })
    ->addGroup('financial', function ($group) {
        $group->addRule('credit_score', '>=', 650);
        $group->addRule('income', '>=', 30000);
        $group->requireAll();
    });
```

### Fluent Syntax

```php
$criteria = Eligify::criteria('Premium Membership')
    ->addGroup('payment', fn($g) => $g
        ->addRule('payment_verified', '==', true)
        ->addRule('no_failed_charges', '==', true)
        ->weight(1.5)
        ->requireAll()
    )
    ->addGroup('activity', fn($g) => $g
        ->addRule('login_count', '>=', 10)
        ->addRule('last_active', '>', now()->subDays(30))
        ->requireAll()
    );
```

## Group Logic

### Logic Types

**AND Logic** - All rules must pass:

```php
->addGroup('identity', fn($g) => $g
    ->addRule('ssn_verified', '==', true)
    ->addRule('address_verified', '==', true)
    ->requireAll()
)
```

**OR Logic** - At least one rule must pass:

```php
->addGroup('contact', fn($g) => $g
    ->addRule('phone_verified', '==', true)
    ->addRule('email_verified', '==', true)
    ->requireAny()
)
```

**Minimum N** - At least N rules must pass:

```php
->addGroup('verification', fn($g) => $g
    ->addRule('email_verified', '==', true)
    ->addRule('phone_verified', '==', true)
    ->addRule('sms_verified', '==', true)
    ->requireMin(2)
)
```

**Majority** - More than half must pass:

```php
->addGroup('checks', fn($g) => $g
    ->addRule('fraud_check', '==', true)
    ->addRule('identity_match', '==', true)
    ->addRule('address_match', '==', true)
    ->requireMajority()
)
```

### Group Combination

**All Groups Required:**

```php
->requireAllGroups(['identity', 'financial', 'credit'])
```

**At Least One Group:**

```php
->requireAnyGroup(['academic', 'test_scores', 'athletics'])
```

**Boolean Logic:**

```php
->requireGroupLogic('(a AND b) OR c')
->requireGroupLogic('(a OR b) AND (c OR d)')
->requireGroupLogic('NOT (a OR b)')
```

## Advanced Features

### Group Weights

For scoring calculations:

```php
$result = Eligify::criteria('Loan Approval')
    ->scoringMethod('weighted')
    ->addGroup('identity', fn($g) => $g
        ->addRule('ssn_verified', '==', true)
        ->weight(2.0)
    )
    ->addGroup('financial', fn($g) => $g
        ->addRule('income', '>=', 30000)
        ->weight(3.0)
    )
    ->evaluate($applicant);
```

### Group Callbacks

Execute code when groups pass/fail:

```php
->addGroup('identity', fn($g) => $g
    ->addRule('ssn_verified', '==', true)
    ->onPass(fn($result) => Log::info('Identity verified'))
    ->onFail(fn($result) => Log::warning('Identity failed'))
)
```

### Group Metadata

Store arbitrary data:

```php
->addGroup('identity', fn($g) => $g
    ->addRule('ssn_verified', '==', true)
    ->meta('department', 'compliance')
    ->meta('review_frequency', 'quarterly')
)
```

## Evaluation

### Basic Evaluation

```php
$result = $criteria->evaluate($applicant);

if ($result->passed()) {
    echo "Approved!";
}

if ($result->groupPassed('identity')) {
    echo "Identity verified";
}

if ($result->groupFailed('financial')) {
    echo "Financial requirements not met";
}
```

### Group Results

```php
$result = $criteria->evaluate($applicant);

foreach ($result->groupResults() as $groupName => $groupResult) {
    echo "$groupName: " . ($groupResult->passed() ? 'PASS' : 'FAIL');

    foreach ($groupResult->rules() as $rule) {
        echo "{$rule->name}: " . ($rule->passed() ? 'PASS' : 'FAIL');
    }
}
```

### Group Statistics

```php
$stats = $result->groupStats();
echo "Identity: {$stats['identity']['passed']}/{$stats['identity']['total']} passed";
```

## Real-World Examples

### E-Commerce Signup

```php
$criteria = Eligify::criteria('Account Signup')
    ->addGroup('age', fn($g) => $g
        ->addRule('age', '>=', 13)
    )
    ->addGroup('email', fn($g) => $g
        ->addRule('email_verified', '==', true)
        ->addRule('email_format', 'regex', '/^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$/')
        ->requireAll()
    )
    ->addGroup('password', fn($g) => $g
        ->addRule('password_length', '>=', 8)
        ->requireAll()
    )
    ->requireAllGroups(['age', 'email', 'password'])
    ->evaluate($signup_data);
```

### Scholarship Eligibility

```php
$criteria = Eligify::criteria('Merit Scholarship')
    ->addGroup('academic', fn($g) => $g
        ->addRule('gpa', '>=', 3.5)
        ->addRule('test_score', '>=', 1400)
        ->requireAll()
        ->weight(3.0)
    )
    ->addGroup('financial', fn($g) => $g
        ->addRule('family_income', '<=', 150000)
        ->weight(2.0)
    )
    ->addGroup('citizenship', fn($g) => $g
        ->addRule('citizenship_status', 'in', ['citizen', 'permanent_resident'])
        ->weight(1.0)
    )
    ->requireAllGroups(['academic', 'citizenship'])
    ->scoringMethod('weighted')
    ->evaluate($student);
```

## Architecture

### Database Relationships

```text
Criteria (1) ──── (many) ──→ RuleGroup
  ├── logic_type: GroupCombination enum
  ├── min_required: for MIN logic type
  ├── boolean_expression: for BOOLEAN logic type
  ├── weight: for scoring calculations
  └── (many) ──→ Rule
       └── group_id: foreign key to RuleGroup
```

### Core Evaluation Flow

```text
Criteria
  ↓
CriteriaBuilder.addGroup()
  ↓
GroupBuilder (fluent API)
  ├── addRule(field, operator, value)
  ├── requireAll/Any/Min/Majority/Logic()
  ├── weight(float)
  └── end()
  ↓
RuleGroup model persisted to database
  ↓
AdvancedRuleEngine.evaluateGroups()
  ↓
GroupEvaluationEngine
  ├── Evaluate each RuleGroup independently
  ├── Apply group logic (ALL/ANY/MIN/MAJORITY/BOOLEAN)
  ├── Calculate group scores
  └── Combine group results based on criteria settings
  ↓
Results array with comprehensive group-level details
```

### Key Evaluation Methods

**GroupEvaluationEngine** provides:

- `evaluateGroups(Criteria $criteria, array $data)` - Main entry point for group evaluation
- `evaluateGroup(RuleGroup $group, array $data)` - Evaluate a single group
- `applyGroupLogic(GroupCombination $logicType, Collection $ruleResults)` - Apply group combination logic
- `evaluateBooleanExpression(string $expression, Collection $ruleResults)` - Parse and evaluate boolean expressions
- `groupPassed(string|int $groupId)` - Check if specific group passed
- `getGroupScore(string|int $groupId)` - Get score for specific group
- `getGroupResults()` - Retrieve all group evaluation results
- `getExecutionLog()` - Access performance tracking data

## Testing

### Basic Tests

```php
use Tests\TestCase;

class RuleGroupTest extends TestCase
{
    public function test_all_group_rules_must_pass()
    {
        $criteria = Eligify::criteria('Test')
            ->addGroup('check', fn($g) => $g
                ->addRule('a', '==', true)
                ->addRule('b', '==', true)
                ->requireAll()
            );

        $this->assertTrue($criteria->evaluate(['a' => true, 'b' => true])->passed());
        $this->assertFalse($criteria->evaluate(['a' => true, 'b' => false])->passed());
    }
}
```

### Test Coverage

The Rule Groups feature includes 14 comprehensive test cases covering:

- ✅ All logic types (ALL, ANY, MAJORITY)
- ✅ Multiple group combinations (ALL groups, ANY group)
- ✅ Group scoring and rule counting
- ✅ Inactive rule handling
- ✅ Group metadata inclusion
- ✅ Error handling and edge cases
- ✅ Group ordering and relationships

## Next Steps

- **Versioning**: Track group changes with [Rule Versioning](rule-versioning.md)
- **Advanced Features**: Explore [Advanced Features](README.md)

---

**Learn more**: [Advanced Features](README.md) | [Rule Versioning](rule-versioning.md)
