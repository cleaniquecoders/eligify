# Multi-Tenant Eligibility System

Tenant-isolated eligibility criteria with separate configurations per tenant.

## Implementation

```php
use CleaniqueCoders\Eligify\Facades\Eligify;
use CleaniqueCoders\Eligify\Models\Criteria;

// Each tenant has their own criteria
$tenant = auth()->user()->tenant;

// Load tenant-specific criteria
$tenantCriteria = Criteria::where('tenant_id', $tenant->id)
    ->where('name', 'loan_approval')
    ->where('is_active', true)
    ->firstOrFail();

$result = Eligify::criteria('Loan Approval')
    ->loadFromDatabase($tenantCriteria)
    ->evaluate($applicant);

// Different tenants can have different thresholds
// Tenant A: income >= 3000
// Tenant B: income >= 5000
```

## Tenant Isolation

```php
// Apply global scope
class Criteria extends Model
{
    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }
}
```

## Related

- [High Traffic](high-traffic.md)
- [Complex Workflows](complex-workflows.md)
