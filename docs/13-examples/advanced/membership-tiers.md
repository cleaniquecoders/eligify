# Membership Tiers Example

Multi-tier membership qualification system with progressive benefits.

## Use Case

Platform with tiered membership (Bronze, Silver, Gold, Platinum) based on engagement and spending.

## Implementation

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

function determineMembershipTier($customer): string
{
    // Check highest tier first
    $platinum = Eligify::criteria('Platinum Tier')
        ->addRule('annual_spend', '>=', 10000)
        ->addRule('referrals_count', '>=', 10)
        ->addRule('account_age_months', '>=', 12)
        ->evaluate($customer);

    if ($platinum->passed()) {
        return 'Platinum';
    }

    $gold = Eligify::criteria('Gold Tier')
        ->addRule('annual_spend', '>=', 5000)
        ->addRule('referrals_count', '>=', 5)
        ->addRule('account_age_months', '>=', 6)
        ->evaluate($customer);

    if ($gold->passed()) {
        return 'Gold';
    }

    $silver = Eligify::criteria('Silver Tier')
        ->addRule('annual_spend', '>=', 1000)
        ->addRule('referrals_count', '>=', 2)
        ->evaluate($customer);

    if ($silver->passed()) {
        return 'Silver';
    }

    return 'Bronze'; // Default tier
}
```

## Related

- [E-commerce Discount](../intermediate/e-commerce.md)
- [SaaS Upgrade](saas-upgrade.md)

## Tip: Organize criteria by type

Classify your membership criteria with `type = 'subscription'` to query all tier-related criteria easily:

```php
use CleaniqueCoders\Eligify\Models\Criteria;

$tierCriteria = Criteria::query()->type('subscription')->get();
```
