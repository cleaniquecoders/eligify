# SaaS Plan Upgrade Eligibility

Automatic plan upgrade suggestions based on usage patterns.

## Implementation

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

$result = Eligify::criteria('enterprise_upgrade')
    ->addRule('monthly_active_users', '>=', 1000)
    ->addRule('api_calls_per_day', '>=', 10000)
    ->addRule('team_size', '>=', 10)
    ->addRule('feature_usage_rate', '>=', 0.8)
    ->addRule('support_tickets_per_month', '>=', 5)
    ->evaluate($account);

if ($result->passed()) {
    // Suggest enterprise plan upgrade
    $account->notify(new UpgradeRecommendationNotification('Enterprise'));
}
```

## Related

- [Membership Tiers](membership-tiers.md)
