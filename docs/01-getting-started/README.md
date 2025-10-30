# Getting Started with Eligify

Welcome to Eligify! This section will help you get up and running quickly with the package.

## What is Eligify?

Eligify is a Laravel package that provides a flexible rule and criteria engine for determining entity eligibility. **"Define criteria. Enforce rules. Decide eligibility."**

## Documentation in this Section

- **[Installation & Setup](installation.md)** - How to install and configure Eligify
- **[Quick Start Guide](quick-start.md)** - Get started in 5 minutes
- **[Usage Guide](usage-guide.md)** - Comprehensive usage examples
- **[Core Concepts](core-concepts.md)** - Understanding criteria, rules, and evaluation

## Quick Example

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

$result = Eligify::criteria('Loan Approval')
    ->addRule('income', '>=', 3000)
    ->addRule('credit_score', '>=', 650)
    ->addRule('active_loans', '<=', 2)
    ->evaluate($applicant);

if ($result->passed()) {
    // Approve the loan
}
```

## Next Steps

After completing this section, you should:

1. Understand what Eligify is and what problems it solves
2. Have Eligify installed in your Laravel project
3. Be able to create basic eligibility criteria and rules
4. Know where to find more advanced features

## Need Help?

- Check the [FAQ](../15-appendix/faq.md)
- Review [Examples](../13-examples/)
- See [Configuration Reference](../06-configuration/)
