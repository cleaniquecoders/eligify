# Examples

Real-world examples demonstrating Eligify usage patterns.

## Overview

Learn from practical examples organized by complexity level.

## Documentation in this Section

### Basic Examples

- **[Loan Approval](basic/loan-approval.md)** - Simple loan eligibility
- **[Scholarship](basic/scholarship.md)** - Student scholarship criteria
- **[Job Screening](basic/job-screening.md)** - Basic candidate filtering

### Intermediate Examples

- **[Insurance](intermediate/insurance.md)** - Insurance underwriting
- **[E-commerce](intermediate/e-commerce.md)** - Discount eligibility
- **[Government Aid](intermediate/government-aid.md)** - Aid qualification

### Advanced Examples

- **[Membership Tiers](advanced/membership-tiers.md)** - Multi-tier system
- **[Credit Card](advanced/credit-card.md)** - Complex credit evaluation
- **[Rental Screening](advanced/rental-screening.md)** - Comprehensive tenant checks
- **[SaaS Upgrade](advanced/saas-upgrade.md)** - Plan upgrade logic

### Real-World Examples

- **[Multi-Tenant](real-world/multi-tenant.md)** - Multi-tenant eligibility
- **[High Traffic](real-world/high-traffic.md)** - High-traffic optimization
- **[Complex Workflows](real-world/complex-workflows.md)** - Multi-stage workflows

## Example Structure

Each example includes:

- **Use Case Description** - What problem it solves
- **Business Rules** - The eligibility criteria
- **Implementation** - Complete working code
- **Testing** - How to test the implementation
- **Variations** - Alternative approaches

## Quick Start Example

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

// Simple loan approval
$result = Eligify::criteria('loan_approval')
    ->addRule('income', '>=', 3000)
    ->addRule('credit_score', '>=', 650)
    ->addRule('employment_months', '>=', 6)
    ->scoringMethod('weighted')
    ->onPass(function ($applicant) {
        $applicant->notify(new LoanApprovedNotification());
    })
    ->onFail(function ($applicant, $result) {
        $applicant->notify(new LoanRejectedNotification($result));
    })
    ->evaluate($applicant);
```

## Example Source Code

All examples are available as runnable PHP files in the `examples/` directory:

```bash
php examples/01-loan-approval.php
php examples/02-scholarship-eligibility.php
# ... etc
```

## Learning Path

1. **Start with Basic** - Understand core concepts
2. **Move to Intermediate** - Learn advanced features
3. **Study Advanced** - Master complex patterns
4. **Review Real-World** - Production-ready implementations

## Contributing Examples

Have a great use case? Submit a PR with:

- Clear business requirements
- Complete implementation
- Tests
- Documentation

## Related Sections

- [Core Features](../03-core-features/) - Feature documentation
- [Data Management](../04-data-management/) - Data patterns used in examples
- [Advanced Features](../07-advanced-features/) - Advanced patterns
