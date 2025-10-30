# Comparison with Alternatives

How Eligify compares to other solutions.

## Overview

While you could build eligibility logic manually or use generic rule engines, Eligify is purpose-built for Laravel applications requiring traceable, data-driven eligibility decisions.

## Eligify vs Manual Implementation

### Manual Implementation

```php
// Manual approach
if ($applicant->income >= 3000 && $applicant->credit_score >= 650) {
    $applicant->approve();
    Log::info('Loan approved', ['id' => $applicant->id]);
} else {
    $applicant->reject();
    Log::info('Loan rejected', ['id' => $applicant->id]);
}
```

**Issues:**
- Hard-coded thresholds
- No audit trail
- Not reusable
- Difficult to test
- No scoring system
- Can't change without code deployment

### Eligify Approach

```php
$result = Eligify::criteria('Loan Approval')
    ->addRule('income', '>=', 3000)
    ->addRule('credit_score', '>=', 650)
    ->onPass(fn($e) => $e->approve())
    ->onFail(fn($e) => $e->reject())
    ->evaluate($applicant);
```

**Benefits:**
- ✅ Data-driven (can store in database)
- ✅ Automatic audit trail
- ✅ Reusable across codebase
- ✅ Easy to test
- ✅ Flexible scoring methods
- ✅ Can update without deployment

## Eligify vs Generic Rule Engines

### Generic Rule Engine (e.g., Laravel Rules, JSON Logic)

**Pros:**
- Flexible for any use case
- Language-agnostic

**Cons:**
- No built-in audit trail
- No Laravel model integration
- No caching strategy
- No workflow automation
- Generic, not eligibility-focused

### Eligify

**Pros:**
- ✅ Purpose-built for eligibility
- ✅ Deep Laravel integration
- ✅ Built-in audit trail
- ✅ Caching & performance optimization
- ✅ Workflow automation (onPass/onFail)
- ✅ Snapshot system for compliance

**Cons:**
- Laravel-specific
- Eligibility-focused (not general-purpose)

## Eligibility Feature Matrix

| Feature | Manual Code | Generic Rule Engine | Eligify |
|---------|-------------|---------------------|---------|
| Define criteria | ❌ Hard-coded | ✅ Yes | ✅ Yes |
| Store in database | ❌ No | ⚠️ Manual | ✅ Built-in |
| Audit trail | ⚠️ Manual | ❌ No | ✅ Automatic |
| Scoring methods | ❌ No | ⚠️ Manual | ✅ Multiple |
| Weighted rules | ❌ No | ⚠️ Manual | ✅ Built-in |
| Caching | ⚠️ Manual | ❌ No | ✅ Built-in |
| Workflow callbacks | ⚠️ Manual | ❌ No | ✅ Built-in |
| Snapshots | ❌ No | ❌ No | ✅ Built-in |
| Laravel integration | ⚠️ Custom | ⚠️ Partial | ✅ Deep |
| Testing helpers | ❌ No | ⚠️ Limited | ✅ Comprehensive |
| UI for criteria | ❌ No | ❌ No | ✅ Optional |

## When to Use Eligify

### ✅ Use Eligify When:

- Building loan/credit approval systems
- Implementing scholarship eligibility
- Creating job candidate screening
- Handling insurance underwriting
- Managing subscription tier upgrades
- Any eligibility-based decision system
- Need audit trails for compliance
- Want data-driven criteria
- Require traceable decisions

### ❌ Consider Alternatives When:

- Simple true/false checks
- One-off eligibility logic
- Non-Laravel applications
- Need non-eligibility rule engine
- Building workflow automation (use Laravel Workflow packages)
- Complex business process management (use BPM tools)

## Migration from Manual Implementation

### Before (Manual)

```php
class LoanService
{
    public function evaluate(User $applicant): bool
    {
        if ($applicant->income < 3000) {
            return false;
        }

        if ($applicant->credit_score < 650) {
            return false;
        }

        if ($applicant->employment_months < 12) {
            return false;
        }

        return true;
    }
}
```

### After (Eligify)

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

class LoanService
{
    public function evaluate(User $applicant): EvaluationResult
    {
        return Eligify::criteria('Loan Approval')
            ->addRule('income', '>=', 3000)
            ->addRule('credit_score', '>=', 650)
            ->addRule('employment_months', '>=', 12)
            ->evaluate($applicant);
    }
}
```

## Performance Comparison

### Eligify Performance

- **Cold evaluation**: ~50-100ms
- **Cached evaluation**: ~1-5ms
- **Batch (100 entities)**: ~2-3s
- **Memory usage**: ~5-10MB per 1000 evaluations

### Optimizations

- Built-in caching
- Batch processing
- Query optimization
- Lazy loading

See [Performance Testing](../09-testing/performance-testing.md).

## Cost Comparison

| Solution | Setup Time | Maintenance | Compliance | Total Cost |
|----------|-----------|-------------|------------|------------|
| Manual | Low | High | High | High |
| Generic | Medium | Medium | Medium | Medium |
| **Eligify** | **Low** | **Low** | **Low** | **Low** |

## Community & Support

- **Active Development**: Regular updates
- **Documentation**: Comprehensive guides
- **Examples**: Real-world use cases
- **Testing**: Well-tested package
- **Support**: GitHub issues & discussions

## Related

- [Getting Started](../01-getting-started/installation.md)
- [Examples](../13-examples/README.md)
- [Roadmap](roadmap.md)
