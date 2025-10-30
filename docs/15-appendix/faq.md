# Frequently Asked Questions

Common questions about Eligify.

## General

### What is Eligify?

Eligify is a Laravel package that provides a flexible rule and criteria engine for determining entity eligibility. It helps you build data-driven decision systems with traceable outcomes.

### Who should use Eligify?

Developers building systems that require:

- Automated decision-making (loan approvals, scholarships, etc.)
- Complex eligibility criteria
- Audit trails for compliance
- Dynamic rule engines

### Is Eligify production-ready?

Yes! Eligify is actively maintained and used in production environments. See the [Production Guide](../10-deployment/production.md).

## Installation & Setup

### What are the requirements?

- PHP 8.4+
- Laravel 11.x or 12.x
- Database (MySQL, PostgreSQL, SQLite, etc.)

### How do I install it?

```bash
composer require cleaniquecoders/eligify
php artisan migrate
```

See [Installation Guide](../01-getting-started/installation.md).

### Do I need to publish config?

Optional. Publish only if you want to customize:

```bash
php artisan vendor:publish --tag=eligify-config
```

## Usage

### How do I define criteria?

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

$result = Eligify::criteria('Loan Approval')
    ->addRule('income', '>=', 3000)
    ->addRule('credit_score', '>=', 650)
    ->evaluate($applicant);
```

### Can I store criteria in the database?

Yes! Create criteria records and load them:

```php
$criteria = Criteria::create([
    'name' => 'loan_approval',
    'rules' => [
        ['field' => 'income', 'operator' => '>=', 'value' => 3000],
    ],
]);

$result = Eligify::criteria('Loan Approval')
    ->loadFromDatabase($criteria)
    ->evaluate($applicant);
```

### How do I add custom operators?

```php
Eligify::registerOperator('divisible_by', function ($value, $divisor) {
    return $value % $divisor === 0;
});

$result = Eligify::criteria('Test')
    ->addRule('points', 'divisible_by', 10)
    ->evaluate($entity);
```

### Can I evaluate relationships?

Yes, use dot notation:

```php
->addRule('profile.verified', '==', true)
->addRule('employment.months', '>=', 12)
```

## Performance

### Does Eligify support caching?

Yes! Enable caching per criteria:

```php
$result = Eligify::criteria('Loan Approval')
    ->cacheFor(3600) // 1 hour
    ->evaluate($applicant);
```

### Can I queue evaluations?

Yes, for async processing:

```php
$result = Eligify::criteria('Heavy Computation')
    ->evaluateAsync($applicant);
```

### How do I handle high traffic?

See [High-Traffic Optimization](../13-examples/real-world/high-traffic.md) and [Optimization Guide](../10-deployment/optimization.md).

## Features

### Does it support weighted scoring?

Yes:

```php
$result = Eligify::criteria('Loan')
    ->addRule('income', '>=', 3000, 0.4)
    ->addRule('credit_score', '>=', 650, 0.6)
    ->scoringMethod('weighted')
    ->evaluate($applicant);
```

### Can I trigger actions on pass/fail?

Yes, use callbacks:

```php
$result = Eligify::criteria('Loan')
    ->onPass(fn($entity) => $entity->approve())
    ->onFail(fn($entity) => $entity->reject())
    ->evaluate($applicant);
```

### Is there an audit trail?

Yes, all evaluations are automatically logged:

```php
$audits = Audit::where('criteria_name', 'loan_approval')->get();
```

### Does it support multi-tenancy?

Yes! See [Multi-Tenant Example](../13-examples/real-world/multi-tenant.md).

## Testing

### How do I test eligibility logic?

```php
test('qualified applicant passes', function () {
    $applicant = User::factory()->create(['income' => 5000]);

    $result = Eligify::criteria('Test')
        ->addRule('income', '>=', 3000)
        ->evaluate($applicant);

    expect($result->passed())->toBeTrue();
});
```

See [Testing Guide](../09-testing/README.md).

## Troubleshooting

### Why aren't my rules evaluating correctly?

Check:

1. Field names are correct
2. Data types match operator expectations
3. Entity has the field or relationship
4. Use `->debug()` to see evaluation details

See [Troubleshooting Guide](../10-deployment/troubleshooting.md).

### Cache not working?

Verify:

1. Cache driver is configured
2. `'cache.enabled' => true` in config
3. Using `->cacheFor()` on builder

### Audit logs not created?

Check:

1. `'audit.enabled' => true` in config
2. Database migrations ran
3. No errors in logs

## Security

### Is input validated?

Yes, but always validate user inputs in your controllers/requests. See [Input Validation](../11-security/input-validation.md).

### Who can evaluate criteria?

Use Laravel's authorization:

```php
$this->authorize('evaluate-eligibility');
```

See [Authorization Guide](../11-security/authorization.md).

### How do I report security issues?

Email: <security@cleaniquecoders.com>

See [Vulnerability Reporting](../11-security/vulnerability-reporting.md).

## Support

### Where can I get help?

- [Documentation](../README.md)
- [GitHub Issues](https://github.com/cleaniquecoders/eligify/issues)
- [Discussions](https://github.com/cleaniquecoders/eligify/discussions)

### How do I contribute?

See [Contributing Guide](../../CONTRIBUTING.md).

### Is there a changelog?

Yes! See [CHANGELOG.md](../../CHANGELOG.md).

## Related

- [Glossary](glossary.md)
- [Comparison](comparison.md)
- [Roadmap](roadmap.md)
