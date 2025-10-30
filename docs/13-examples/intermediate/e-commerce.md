# E-commerce Discount Eligibility

Dynamic discount eligibility for customers based on loyalty and purchase history.

## Use Case

E-commerce platform wants to automatically offer discounts to loyal customers.

## Implementation

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

$result = Eligify::criteria('vip_discount')
    ->addRule('total_purchases', '>=', 1000)
    ->addRule('loyalty_tier', 'in', ['gold', 'platinum'])
    ->addRule('account_age_months', '>=', 6)
    ->addRule('reviews_count', '>=', 5)
    ->evaluate($customer);

if ($result->passed()) {
    $discountPercent = match($customer->loyalty_tier) {
        'platinum' => 20,
        'gold' => 15,
        default => 10,
    };
}
```

## Related

- [Membership Tiers](../advanced/membership-tiers.md)
