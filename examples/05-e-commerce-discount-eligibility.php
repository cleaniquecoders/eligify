<?php

/**
 * Example 05: E-commerce Discount Eligibility System
 *
 * Use Case: Online retail platform needs to determine which customers qualify
 * for various promotional discounts based on loyalty status, purchase history,
 * cart value, and product categories.
 *
 * Features Demonstrated:
 * - Real-time eligibility evaluation
 * - Model integration with HasEligibility trait
 * - Multi-tier discount programs
 * - Cart-based conditional logic
 * - Customer loyalty rewards
 *
 * Business Logic:
 * - VIP members get exclusive access to premium discounts
 * - Cart value thresholds unlock progressive discounts
 * - Product category combinations affect eligibility
 * - Purchase history and account age matter
 * - Seasonal promotions and special events
 *
 * Discount Tiers:
 * - VIP Elite (90-100%): 30% off + Free shipping + Early access
 * - VIP Gold (75-89%): 20% off + Free shipping
 * - Premium (60-74%): 15% off
 * - Standard (50-59%): 10% off
 * - None (<50%): No additional discount
 */

require_once __DIR__.'/bootstrap.php';

use CleaniqueCoders\Eligify\Facades\Eligify;

echo '='.str_repeat('=', 70)."\n";
echo "  E-COMMERCE DISCOUNT ELIGIBILITY SYSTEM\n";
echo '='.str_repeat('=', 70)."\n\n";

// ============================================================================
// STEP 1: Define Discount Eligibility Criteria
// ============================================================================

echo "📋 Setting up discount eligibility criteria...\n\n";

$criteria = Eligify::criteria('vip_discount_program_2025')
    ->description('VIP Customer Discount Program - Holiday Season 2025')

    // LOYALTY STATUS (35% weight)
    ->addRule('membership_tier', '>=', 2, 20)  // 1=Basic, 2=Premium, 3=VIP
    ->addRule('account_age_months', '>=', 6, 10)
    ->addRule('lifetime_orders', '>=', 5, 5)

    // PURCHASE BEHAVIOR (30% weight)
    ->addRule('cart_value', '>=', 100, 15)
    ->addRule('avg_order_value', '>=', 75, 10)
    ->addRule('returns_rate', '<=', 10, 5)  // Less than 10% return rate

    // ENGAGEMENT (20% weight)
    ->addRule('has_email_subscription', '==', true, 5)
    ->addRule('review_count', '>=', 3, 5)
    ->addRule('social_media_follower', '==', true, 5)
    ->addRule('referral_count', '>=', 1, 5)

    // CURRENT CART (15% weight)
    ->addRule('cart_item_count', '>=', 3, 5)
    ->addRule('has_premium_products', '==', true, 10)

    ->passThreshold(50)

    // Tier-based discount callbacks
    ->onScoreRange(90, 100, function ($customer, $result) {
        $discount = 30;
        echo "\n🎉 VIP ELITE DISCOUNT UNLOCKED!\n";
        echo "   Customer: {$customer['name']}\n";
        echo "   Discount: {$discount}%\n";
        echo "   Benefits:\n";
        echo "      • 30% off entire order\n";
        echo "      • Free express shipping\n";
        echo "      • Early access to new products\n";
        echo "      • Exclusive VIP lounge access\n";
        echo '   Cart Value: $'.number_format($customer['cart_value'], 2)."\n";
        echo '   You Save: $'.number_format($customer['cart_value'] * 0.30, 2)."\n";
    })

    ->onScoreRange(75, 89, function ($customer, $result) {
        $discount = 20;
        echo "\n🌟 VIP GOLD DISCOUNT ACTIVATED!\n";
        echo "   Customer: {$customer['name']}\n";
        echo "   Discount: {$discount}%\n";
        echo "   Benefits:\n";
        echo "      • 20% off entire order\n";
        echo "      • Free standard shipping\n";
        echo "      • Priority customer support\n";
        echo '   Cart Value: $'.number_format($customer['cart_value'], 2)."\n";
        echo '   You Save: $'.number_format($customer['cart_value'] * 0.20, 2)."\n";
    })

    ->onScoreRange(60, 74, function ($customer, $result) {
        $discount = 15;
        echo "\n💎 PREMIUM DISCOUNT APPLIED!\n";
        echo "   Customer: {$customer['name']}\n";
        echo "   Discount: {$discount}%\n";
        echo "   Benefits:\n";
        echo "      • 15% off entire order\n";
        echo "      • Free shipping on orders $150+\n";
        echo '   Cart Value: $'.number_format($customer['cart_value'], 2)."\n";
        echo '   You Save: $'.number_format($customer['cart_value'] * 0.15, 2)."\n";
    })

    ->onScoreRange(50, 59, function ($customer, $result) {
        $discount = 10;
        echo "\n🎁 STANDARD DISCOUNT AVAILABLE!\n";
        echo "   Customer: {$customer['name']}\n";
        echo "   Discount: {$discount}%\n";
        echo "   Benefits:\n";
        echo "      • 10% off entire order\n";
        echo '   Cart Value: $'.number_format($customer['cart_value'], 2)."\n";
        echo '   You Save: $'.number_format($customer['cart_value'] * 0.10, 2)."\n";
    })

    ->onFail(function ($customer, $result) {
        echo "\n📦 NO ADDITIONAL DISCOUNT\n";
        echo "   Customer: {$customer['name']}\n";
        echo "   Score: {$result['score']}%\n";
        echo "   → Continue shopping to unlock discounts!\n";
        echo "   → Tips to qualify:\n";

        $tips = [];
        if ($customer['cart_value'] < 100) {
            $tips[] = 'Add $'.(100 - $customer['cart_value']).' more to cart';
        }
        if ($customer['lifetime_orders'] < 5) {
            $tips[] = 'Complete '.(5 - $customer['lifetime_orders']).' more orders';
        }
        if (! $customer['has_email_subscription']) {
            $tips[] = 'Subscribe to our newsletter';
        }
        if ($customer['review_count'] < 3) {
            $tips[] = 'Write product reviews';
        }

        foreach (array_slice($tips, 0, 3) as $tip) {
            echo "      • {$tip}\n";
        }
    })

    ->save();

echo "✓ Discount criteria configured!\n";
echo "  - Program: VIP Discount 2025\n";
echo "  - Tiers: Elite, Gold, Premium, Standard\n\n";

// ============================================================================
// STEP 2: Prepare Customer Data
// ============================================================================

echo "🛒 Processing customer carts...\n\n";

$customers = [
    // CASE 1: VIP Elite customer - Maximum benefits
    [
        'name' => 'Sophia Williams',
        'membership_tier' => 3,  // VIP
        'account_age_months' => 24,
        'lifetime_orders' => 45,
        'cart_value' => 350.00,
        'avg_order_value' => 185.50,
        'returns_rate' => 3,
        'has_email_subscription' => true,
        'review_count' => 18,
        'social_media_follower' => true,
        'referral_count' => 5,
        'cart_item_count' => 8,
        'has_premium_products' => true,
    ],

    // CASE 2: VIP Gold customer - Strong loyalty
    [
        'name' => 'Michael Chen',
        'membership_tier' => 2,  // Premium
        'account_age_months' => 15,
        'lifetime_orders' => 20,
        'cart_value' => 225.00,
        'avg_order_value' => 120.00,
        'returns_rate' => 5,
        'has_email_subscription' => true,
        'review_count' => 8,
        'social_media_follower' => true,
        'referral_count' => 2,
        'cart_item_count' => 5,
        'has_premium_products' => true,
    ],

    // CASE 3: Premium customer - Good engagement
    [
        'name' => 'Emma Rodriguez',
        'membership_tier' => 2,
        'account_age_months' => 8,
        'lifetime_orders' => 12,
        'cart_value' => 150.00,
        'avg_order_value' => 95.00,
        'returns_rate' => 8,
        'has_email_subscription' => true,
        'review_count' => 5,
        'social_media_follower' => false,
        'referral_count' => 1,
        'cart_item_count' => 4,
        'has_premium_products' => true,
    ],

    // CASE 4: Standard customer - Borderline
    [
        'name' => 'James Taylor',
        'membership_tier' => 1,  // Basic
        'account_age_months' => 6,
        'lifetime_orders' => 6,
        'cart_value' => 110.00,
        'avg_order_value' => 75.00,
        'returns_rate' => 12,
        'has_email_subscription' => true,
        'review_count' => 3,
        'social_media_follower' => false,
        'referral_count' => 0,
        'cart_item_count' => 3,
        'has_premium_products' => false,
    ],

    // CASE 5: New customer - No discount yet
    [
        'name' => 'Olivia Brown',
        'membership_tier' => 1,
        'account_age_months' => 2,
        'lifetime_orders' => 2,
        'cart_value' => 75.00,
        'avg_order_value' => 65.00,
        'returns_rate' => 0,
        'has_email_subscription' => false,
        'review_count' => 0,
        'social_media_follower' => false,
        'referral_count' => 0,
        'cart_item_count' => 2,
        'has_premium_products' => false,
    ],
];

// ============================================================================
// STEP 3: Evaluate Each Customer Cart
// ============================================================================

echo "🔍 Evaluating discount eligibility...\n";
echo str_repeat('-', 72)."\n\n";

$discountResults = [];
$eligify = app(\CleaniqueCoders\Eligify\Eligify::class);

foreach ($customers as $index => $customer) {
    echo 'CUSTOMER '.($index + 1).": {$customer['name']}\n";
    echo str_repeat('-', 72)."\n";
    echo 'Membership: '.['', 'Basic', 'Premium', 'VIP'][$customer['membership_tier']]."\n";
    echo "Account Age: {$customer['account_age_months']} months\n";
    echo "Lifetime Orders: {$customer['lifetime_orders']}\n";
    echo 'Cart Value: $'.number_format($customer['cart_value'], 2)."\n";
    echo 'Average Order: $'.number_format($customer['avg_order_value'], 2)."\n";
    echo "Return Rate: {$customer['returns_rate']}%\n";
    echo "Reviews Written: {$customer['review_count']}\n";

    // Evaluate with callbacks
    $result = $eligify->evaluateWithCallbacks($criteria, $customer);

    // Determine discount tier
    $tier = match (true) {
        $result['score'] >= 90 => ['name' => 'VIP Elite', 'discount' => 30],
        $result['score'] >= 75 => ['name' => 'VIP Gold', 'discount' => 20],
        $result['score'] >= 60 => ['name' => 'Premium', 'discount' => 15],
        $result['score'] >= 50 => ['name' => 'Standard', 'discount' => 10],
        default => ['name' => 'None', 'discount' => 0]
    };

    $savings = $customer['cart_value'] * ($tier['discount'] / 100);
    $finalPrice = $customer['cart_value'] - $savings;

    $discountResults[] = [
        'name' => $customer['name'],
        'score' => $result['score'],
        'tier' => $tier['name'],
        'discount' => $tier['discount'],
        'cart_value' => $customer['cart_value'],
        'savings' => $savings,
        'final_price' => $finalPrice,
    ];

    echo "\n📊 DISCOUNT SUMMARY:\n";
    echo "   Eligibility Score: {$result['score']}%\n";
    echo "   Discount Tier: {$tier['name']}\n";
    echo "   Discount Rate: {$tier['discount']}%\n";
    echo '   Original: $'.number_format($customer['cart_value'], 2)."\n";
    echo '   You Save: $'.number_format($savings, 2)."\n";
    echo '   Final Price: $'.number_format($finalPrice, 2)."\n";

    echo "\n".str_repeat('=', 72)."\n\n";
}

// ============================================================================
// STEP 4: Discount Program Summary
// ============================================================================

echo "📊 DISCOUNT PROGRAM SUMMARY\n";
echo str_repeat('-', 72)."\n\n";

printf("%-20s | %-10s | %-12s | %8s | %10s\n",
    'Customer', 'Score', 'Tier', 'Discount', 'Savings');
echo str_repeat('-', 72)."\n";

$totalSavings = 0;

foreach ($discountResults as $result) {
    printf("%-20s | %7.1f%% | %-12s | %7d%% | $%8.2f\n",
        $result['name'],
        $result['score'],
        $result['tier'],
        $result['discount'],
        $result['savings']
    );

    $totalSavings += $result['savings'];
}

echo str_repeat('-', 72)."\n";
echo 'Total Customer Savings: $'.number_format($totalSavings, 2)."\n";
echo 'Average Discount Rate: '.round(array_sum(array_column($discountResults, 'discount')) / count($discountResults), 1)."%\n\n";

// ============================================================================
// STEP 5: Real-time Laravel Integration
// ============================================================================

echo "💡 REAL-TIME ELIGIBILITY IN LARAVEL CHECKOUT:\n";
echo str_repeat('-', 72)."\n";
echo <<<'LARAVEL'

// App/Models/Customer.php
use CleaniqueCoders\Eligify\Concerns\HasEligibility;

class Customer extends Model
{
    use HasEligibility;

    public function getEligibilityData(): array
    {
        return [
            'membership_tier' => $this->membership_tier,
            'account_age_months' => $this->created_at->diffInMonths(now()),
            'lifetime_orders' => $this->orders()->completed()->count(),
            'cart_value' => $this->currentCart->total ?? 0,
            'avg_order_value' => $this->orders()->avg('total') ?? 0,
            'returns_rate' => $this->calculateReturnRate(),
            'has_email_subscription' => $this->email_subscribed,
            'review_count' => $this->reviews()->count(),
            'social_media_follower' => $this->social_accounts()->exists(),
            'referral_count' => $this->referrals()->count(),
            'cart_item_count' => $this->currentCart->items()->count() ?? 0,
            'has_premium_products' => $this->currentCart?->hasPremiumProducts() ?? false,
        ];
    }

    // Real-time discount calculation
    public function getAvailableDiscount(): array
    {
        $result = $this->checkEligibility('vip_discount_program_2025');

        $tier = match(true) {
            $result['score'] >= 90 => ['name' => 'VIP Elite', 'rate' => 30],
            $result['score'] >= 75 => ['name' => 'VIP Gold', 'rate' => 20],
            $result['score'] >= 60 => ['name' => 'Premium', 'rate' => 15],
            $result['score'] >= 50 => ['name' => 'Standard', 'rate' => 10],
            default => ['name' => 'None', 'rate' => 0]
        };

        return [
            'eligible' => $result['passed'],
            'score' => $result['score'],
            'tier' => $tier['name'],
            'discount_rate' => $tier['rate'],
            'discount_amount' => $this->currentCart->total * ($tier['rate'] / 100),
        ];
    }
}

// App/Http/Controllers/CheckoutController.php
class CheckoutController extends Controller
{
    public function showCheckout()
    {
        $customer = auth()->user();
        $discount = $customer->getAvailableDiscount();

        return view('checkout.index', [
            'cart' => $customer->currentCart,
            'discount' => $discount,
            'finalTotal' => $customer->currentCart->total - $discount['discount_amount'],
        ]);
    }

    public function applyDiscount(Request $request)
    {
        $customer = auth()->user();
        $discount = $customer->getAvailableDiscount();

        if (!$discount['eligible']) {
            return back()->with('error', 'You do not qualify for this discount');
        }

        // Apply discount to cart
        $customer->currentCart->update([
            'discount_rate' => $discount['discount_rate'],
            'discount_amount' => $discount['discount_amount'],
            'discount_tier' => $discount['tier'],
        ]);

        return back()->with('success',
            "Congratulations! {$discount['discount_rate']}% {$discount['tier']} discount applied!");
    }
}

// App/Livewire/CartSummary.php (Real-time updates)
use Livewire\Component;

class CartSummary extends Component
{
    public $cart;
    public $discount;

    public function mount()
    {
        $this->updateDiscount();
    }

    // Called when cart items change
    public function updated()
    {
        $this->updateDiscount();
    }

    protected function updateDiscount()
    {
        $customer = auth()->user();
        $this->discount = $customer->getAvailableDiscount();
        $this->cart = $customer->currentCart->fresh();
    }

    public function render()
    {
        return view('livewire.cart-summary', [
            'subtotal' => $this->cart->total,
            'discount' => $this->discount,
            'final' => $this->cart->total - $this->discount['discount_amount'],
        ]);
    }
}

// resources/views/livewire/cart-summary.blade.php
<div>
    <div class="cart-summary">
        <div class="line-item">
            <span>Subtotal:</span>
            <span>${{ number_format($subtotal, 2) }}</span>
        </div>

        @if($discount['eligible'])
            <div class="line-item discount">
                <span>{{ $discount['tier'] }} Discount ({{ $discount['discount_rate'] }}%):</span>
                <span class="text-green">-${{ number_format($discount['discount_amount'], 2) }}</span>
            </div>
        @else
            <div class="eligibility-tip">
                <p>Score: {{ $discount['score'] }}% (Need 50%)</p>
                <p>Keep shopping to unlock discounts!</p>
            </div>
        @endif

        <div class="line-item total">
            <span><strong>Total:</strong></span>
            <span><strong>${{ number_format($final, 2) }}</strong></span>
        </div>
    </div>
</div>

LARAVEL;

echo "\n".str_repeat('=', 72)."\n";
echo "Example completed! Check discount eligibility results above.\n";
echo str_repeat('=', 72)."\n";
