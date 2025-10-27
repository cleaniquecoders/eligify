<?php

/**
 * Example 07: Gym Membership Tier System
 *
 * Use Case: Fitness center needs to automatically assign membership tiers
 * and pricing based on age, commitment level, attendance patterns, and
 * participation in classes/programs.
 *
 * Features Demonstrated:
 * - XOR logic for exclusive tier selection
 * - Dynamic tier calculations
 * - Age-based pricing rules
 * - Attendance rewards system
 * - Exclusive benefit rules
 *
 * Business Logic:
 * - Age categories: Youth, Adult, Senior
 * - Commitment levels: Month-to-month, 6-month, Annual
 * - Attendance tracking for loyalty rewards
 * - Class participation scoring
 * - Tier upgrades and downgrades
 *
 * Membership Tiers:
 * - Elite (90-100%): Premium access, unlimited classes, personal training
 * - Premium (75-89%): All facilities, most classes, guest passes
 * - Standard (60-74%): Basic access, limited classes
 * - Basic (50-59%): Off-peak only, no classes
 */

require_once __DIR__.'/bootstrap.php';

use CleaniqueCoders\Eligify\Facades\Eligify;

echo '='.str_repeat('=', 70)."\n";
echo "  GYM MEMBERSHIP TIER QUALIFICATION SYSTEM\n";
echo '='.str_repeat('=', 70)."\n\n";

// ============================================================================
// STEP 1: Define Membership Tier Criteria
// ============================================================================

echo "📋 Setting up membership tier criteria...\n\n";

$criteria = Eligify::criteria('gym_membership_tiers_2025')
    ->description('FitLife Gym - Membership Tier Assignment System')

    // COMMITMENT LEVEL (30% weight)
    ->addRule('contract_months', '>=', 12, 30)  // Annual commitment

    // ATTENDANCE PATTERN (25% weight)
    ->addRule('avg_monthly_visits', '>=', 12, 15)  // 3x per week
    ->addRule('attendance_consistency', '>=', 80, 10)  // Consistent visits

    // ENGAGEMENT (20% weight)
    ->addRule('class_participation_count', '>=', 8, 10)  // Classes per month
    ->addRule('has_personal_trainer', '==', true, 10)

    // REFERRALS & LOYALTY (15% weight)
    ->addRule('referral_count', '>=', 2, 10)
    ->addRule('membership_tenure_months', '>=', 6, 5)

    // PAYMENT HISTORY (10% weight)
    ->addRule('payment_on_time_rate', '>=', 95, 10)

    ->passThreshold(50)

    // Tier-based callbacks
    ->onScoreRange(90, 100, function ($member, $result) {
        $pricing = calculateMembershipPrice($member['age'], 'elite', $member['contract_months']);
        echo "\n🏆 ELITE MEMBERSHIP\n";
        echo "   Member: {$member['name']}\n";
        echo "   Age: {$member['age']} years\n";
        echo "   Score: {$result['score']}%\n";
        echo "   Monthly Rate: \${$pricing['monthly']}\n";
        echo "   \n";
        echo "   ✨ ELITE BENEFITS:\n";
        echo "      • 24/7 facility access\n";
        echo "      • Unlimited group classes\n";
        echo "      • 4 personal training sessions/month\n";
        echo "      • Free guest passes (4/month)\n";
        echo "      • Spa and sauna access\n";
        echo "      • Nutrition consultation\n";
        echo "      • Priority equipment reservation\n";
        echo "      • Exclusive member events\n";
    })

    ->onScoreRange(75, 89, function ($member, $result) {
        $pricing = calculateMembershipPrice($member['age'], 'premium', $member['contract_months']);
        echo "\n💎 PREMIUM MEMBERSHIP\n";
        echo "   Member: {$member['name']}\n";
        echo "   Age: {$member['age']} years\n";
        echo "   Score: {$result['score']}%\n";
        echo "   Monthly Rate: \${$pricing['monthly']}\n";
        echo "   \n";
        echo "   ⭐ PREMIUM BENEFITS:\n";
        echo "      • 5am-11pm facility access\n";
        echo "      • Up to 20 group classes/month\n";
        echo "      • 2 personal training sessions/month\n";
        echo "      • 2 guest passes/month\n";
        echo "      • Sauna access\n";
        echo "      • Equipment reservation\n";
    })

    ->onScoreRange(60, 74, function ($member, $result) {
        $pricing = calculateMembershipPrice($member['age'], 'standard', $member['contract_months']);
        echo "\n🌟 STANDARD MEMBERSHIP\n";
        echo "   Member: {$member['name']}\n";
        echo "   Age: {$member['age']} years\n";
        echo "   Score: {$result['score']}%\n";
        echo "   Monthly Rate: \${$pricing['monthly']}\n";
        echo "   \n";
        echo "   ✅ STANDARD BENEFITS:\n";
        echo "      • 6am-10pm facility access\n";
        echo "      • Up to 8 group classes/month\n";
        echo "      • 1 guest pass/month\n";
        echo "      • Standard equipment access\n";
    })

    ->onScoreRange(50, 59, function ($member, $result) {
        $pricing = calculateMembershipPrice($member['age'], 'basic', $member['contract_months']);
        echo "\n📋 BASIC MEMBERSHIP\n";
        echo "   Member: {$member['name']}\n";
        echo "   Age: {$member['age']} years\n";
        echo "   Score: {$result['score']}%\n";
        echo "   Monthly Rate: \${$pricing['monthly']}\n";
        echo "   \n";
        echo "   📍 BASIC BENEFITS:\n";
        echo "      • Off-peak hours (10am-4pm weekdays)\n";
        echo "      • Standard equipment access\n";
        echo "      • Locker room facilities\n";
        echo "   \n";
        echo "   💡 TIP: Increase attendance to unlock more benefits!\n";
    })

    ->onFail(function ($member, $result) {
        echo "\n⚠️  MEMBERSHIP REVIEW REQUIRED\n";
        echo "   Member: {$member['name']}\n";
        echo "   Score: {$result['score']}%\n";
        echo "   → Low engagement detected\n";
        echo "   → Consider:\n";
        echo "      • Freezing membership temporarily\n";
        echo "      • Switching to pay-per-visit\n";
        echo "      • Speaking with membership advisor\n";
    })

    ->save();

// Helper function for pricing
function calculateMembershipPrice(int $age, string $tier, int $contractMonths): array
{
    // Base prices by tier
    $basePrices = [
        'elite' => 120,
        'premium' => 80,
        'standard' => 50,
        'basic' => 30,
    ];

    $basePrice = $basePrices[$tier];

    // Age discounts
    $ageMultiplier = match (true) {
        $age < 18 => 0.75,      // 25% youth discount
        $age >= 65 => 0.80,     // 20% senior discount
        default => 1.0
    };

    // Contract commitment discounts
    $contractMultiplier = match ($contractMonths) {
        12 => 0.85,  // 15% discount for annual
        6 => 0.90,   // 10% discount for 6-month
        default => 1.0
    };

    $monthlyPrice = round($basePrice * $ageMultiplier * $contractMultiplier);

    return [
        'monthly' => $monthlyPrice,
        'total' => $monthlyPrice * $contractMonths,
    ];
}

echo "✓ Membership tier criteria configured!\n";
echo "  - Tiers: Elite, Premium, Standard, Basic\n";
echo "  - Dynamic pricing based on age and commitment\n\n";

// ============================================================================
// STEP 2: Prepare Member Data
// ============================================================================

echo "👥 Evaluating member tier assignments...\n\n";

$members = [
    // CASE 1: Elite member - Dedicated fitness enthusiast
    [
        'name' => 'Amanda Stevens',
        'age' => 32,
        'contract_months' => 12,
        'avg_monthly_visits' => 18,
        'attendance_consistency' => 95,
        'class_participation_count' => 15,
        'has_personal_trainer' => true,
        'referral_count' => 4,
        'membership_tenure_months' => 24,
        'payment_on_time_rate' => 100,
    ],

    // CASE 2: Premium member - Regular with good engagement
    [
        'name' => 'James Wilson',
        'age' => 45,
        'contract_months' => 12,
        'avg_monthly_visits' => 14,
        'attendance_consistency' => 85,
        'class_participation_count' => 10,
        'has_personal_trainer' => false,
        'referral_count' => 2,
        'membership_tenure_months' => 18,
        'payment_on_time_rate' => 98,
    ],

    // CASE 3: Standard member - Consistent but basic usage
    [
        'name' => 'Maria Garcia',
        'age' => 28,
        'contract_months' => 6,
        'avg_monthly_visits' => 10,
        'attendance_consistency' => 75,
        'class_participation_count' => 5,
        'has_personal_trainer' => false,
        'referral_count' => 1,
        'membership_tenure_months' => 8,
        'payment_on_time_rate' => 95,
    ],

    // CASE 4: Basic member - Low engagement
    [
        'name' => 'Robert Lee',
        'age' => 55,
        'contract_months' => 1,
        'avg_monthly_visits' => 6,
        'attendance_consistency' => 60,
        'class_participation_count' => 2,
        'has_personal_trainer' => false,
        'referral_count' => 0,
        'membership_tenure_months' => 4,
        'payment_on_time_rate' => 90,
    ],

    // CASE 5: Senior with good attendance
    [
        'name' => 'Dorothy Chen',
        'age' => 68,
        'contract_months' => 12,
        'avg_monthly_visits' => 12,
        'attendance_consistency' => 80,
        'class_participation_count' => 8,
        'has_personal_trainer' => false,
        'referral_count' => 1,
        'membership_tenure_months' => 36,
        'payment_on_time_rate' => 100,
    ],
];

// ============================================================================
// STEP 3: Evaluate Each Member
// ============================================================================

echo "🔍 Assigning membership tiers...\n";
echo str_repeat('-', 72)."\n\n";

$tierResults = [];
$eligify = app(\CleaniqueCoders\Eligify\Eligify::class);

foreach ($members as $index => $member) {
    echo 'MEMBER '.($index + 1).": {$member['name']}\n";
    echo str_repeat('-', 72)."\n";
    echo "Age: {$member['age']} years\n";
    echo "Contract: {$member['contract_months']} months\n";
    echo "Avg Visits: {$member['avg_monthly_visits']}/month\n";
    echo "Attendance Consistency: {$member['attendance_consistency']}%\n";
    echo "Class Participation: {$member['class_participation_count']}/month\n";
    echo 'Personal Trainer: '.($member['has_personal_trainer'] ? 'Yes' : 'No')."\n";
    echo "Referrals: {$member['referral_count']}\n";
    echo "Tenure: {$member['membership_tenure_months']} months\n";

    // Evaluate with callbacks
    $result = $eligify->evaluateWithCallbacks($criteria, $member);

    // Determine tier
    $tier = match (true) {
        $result['score'] >= 90 => 'Elite',
        $result['score'] >= 75 => 'Premium',
        $result['score'] >= 60 => 'Standard',
        $result['score'] >= 50 => 'Basic',
        default => 'Review Required'
    };

    $tierKey = strtolower($tier);
    $pricing = in_array($tierKey, ['elite', 'premium', 'standard', 'basic'])
        ? calculateMembershipPrice($member['age'], $tierKey, $member['contract_months'])
        : ['monthly' => 0, 'total' => 0];

    $tierResults[] = [
        'name' => $member['name'],
        'age' => $member['age'],
        'score' => $result['score'],
        'tier' => $tier,
        'monthly_price' => $pricing['monthly'],
        'visits' => $member['avg_monthly_visits'],
    ];

    echo "\n📊 TIER ASSIGNMENT:\n";
    echo "   Engagement Score: {$result['score']}%\n";
    echo "   Assigned Tier: {$tier}\n";

    if ($result['passed']) {
        echo "   Monthly Rate: \${$pricing['monthly']}\n";
        echo '   Contract Total: $'.number_format($pricing['total'])."\n";
    }

    echo "\n".str_repeat('=', 72)."\n\n";
}

// ============================================================================
// STEP 4: Membership Summary
// ============================================================================

echo "📊 MEMBERSHIP TIER SUMMARY\n";
echo str_repeat('-', 72)."\n\n";

printf("%-20s | %-5s | %-10s | %-12s | %-10s | %-8s\n",
    'Member', 'Age', 'Score', 'Tier', 'Monthly', 'Visits');
echo str_repeat('-', 72)."\n";

$tierCounts = ['Elite' => 0, 'Premium' => 0, 'Standard' => 0, 'Basic' => 0];
$totalRevenue = 0;

foreach ($tierResults as $result) {
    printf("%-20s | %3d | %7.1f%% | %-12s | $%8d | %6d\n",
        $result['name'],
        $result['age'],
        $result['score'],
        $result['tier'],
        $result['monthly_price'],
        $result['visits']
    );

    if (isset($tierCounts[$result['tier']])) {
        $tierCounts[$result['tier']]++;
        $totalRevenue += $result['monthly_price'];
    }
}

echo str_repeat('-', 72)."\n";
echo "Tier Distribution:\n";
foreach ($tierCounts as $tier => $count) {
    if ($count > 0) {
        echo "  - {$tier}: {$count}\n";
    }
}
echo 'Monthly Revenue: $'.number_format($totalRevenue)."\n";
echo 'Annual Revenue: $'.number_format($totalRevenue * 12)."\n\n";

// ============================================================================
// STEP 5: Laravel Integration
// ============================================================================

echo "💡 LARAVEL INTEGRATION FOR DYNAMIC TIER MANAGEMENT:\n";
echo str_repeat('-', 72)."\n";
echo <<<'LARAVEL'

// App/Models/GymMember.php
use CleaniqueCoders\Eligify\Concerns\HasEligibility;

class GymMember extends Model
{
    use HasEligibility;

    protected $fillable = [
        'name', 'email', 'age', 'contract_months', 'membership_tier',
        'monthly_rate', 'contract_start', 'contract_end'
    ];

    public function getEligibilityData(): array
    {
        return [
            'age' => $this->age,
            'contract_months' => $this->contract_months,
            'avg_monthly_visits' => $this->calculateAvgMonthlyVisits(),
            'attendance_consistency' => $this->calculateAttendanceConsistency(),
            'class_participation_count' => $this->classAttendances()->thisMonth()->count(),
            'has_personal_trainer' => $this->personalTrainer()->exists(),
            'referral_count' => $this->referrals()->count(),
            'membership_tenure_months' => $this->created_at->diffInMonths(now()),
            'payment_on_time_rate' => $this->calculatePaymentOnTimeRate(),
        ];
    }

    // Relationships
    public function checkIns()
    {
        return $this->hasMany(GymCheckIn::class);
    }

    public function classAttendances()
    {
        return $this->hasMany(ClassAttendance::class);
    }

    public function personalTrainer()
    {
        return $this->belongsTo(Trainer::class, 'trainer_id');
    }

    public function referrals()
    {
        return $this->hasMany(GymMember::class, 'referred_by');
    }

    // Calculate metrics
    protected function calculateAvgMonthlyVisits(): float
    {
        return $this->checkIns()
            ->where('checked_in_at', '>=', now()->subMonths(3))
            ->count() / 3;
    }

    protected function calculateAttendanceConsistency(): float
    {
        // Calculate what % of days they should have come vs actually came
        $expectedVisits = ($this->contract_months >= 12) ? 12 : 8;
        $actualVisits = $this->calculateAvgMonthlyVisits();

        return min(100, ($actualVisits / $expectedVisits) * 100);
    }

    protected function calculatePaymentOnTimeRate(): float
    {
        $totalPayments = $this->payments()->count();
        $onTimePayments = $this->payments()->where('paid_on_time', true)->count();

        return $totalPayments > 0 ? ($onTimePayments / $totalPayments) * 100 : 100;
    }

    // Auto-update tier
    public function updateMembershipTier(): void
    {
        $result = $this->checkEligibility('gym_membership_tiers_2025');

        $tier = match(true) {
            $result['score'] >= 90 => 'elite',
            $result['score'] >= 75 => 'premium',
            $result['score'] >= 60 => 'standard',
            $result['score'] >= 50 => 'basic',
            default => 'review'
        };

        $pricing = $this->calculatePricing($tier);

        $this->update([
            'membership_tier' => $tier,
            'tier_score' => $result['score'],
            'monthly_rate' => $pricing['monthly'],
            'last_tier_update' => now(),
        ]);

        // Log tier change
        $this->tierHistory()->create([
            'tier' => $tier,
            'score' => $result['score'],
            'monthly_rate' => $pricing['monthly'],
            'changed_at' => now(),
        ]);
    }

    protected function calculatePricing(string $tier): array
    {
        $basePrices = [
            'elite' => 120,
            'premium' => 80,
            'standard' => 50,
            'basic' => 30,
        ];

        $basePrice = $basePrices[$tier] ?? 30;

        $ageMultiplier = match(true) {
            $this->age < 18 => 0.75,
            $this->age >= 65 => 0.80,
            default => 1.0
        };

        $contractMultiplier = match($this->contract_months) {
            12 => 0.85,
            6 => 0.90,
            default => 1.0
        };

        $monthly = round($basePrice * $ageMultiplier * $contractMultiplier);

        return [
            'monthly' => $monthly,
            'total' => $monthly * $this->contract_months,
        ];
    }
}

// App/Console/Commands/UpdateMembershipTiers.php
class UpdateMembershipTiers extends Command
{
    protected $signature = 'gym:update-tiers';
    protected $description = 'Update membership tiers based on engagement';

    public function handle()
    {
        $members = GymMember::where('status', 'active')->get();

        $this->info("Updating tiers for {$members->count()} members...");

        $changes = ['upgrades' => 0, 'downgrades' => 0, 'unchanged' => 0];

        foreach ($members as $member) {
            $oldTier = $member->membership_tier;
            $member->updateMembershipTier();
            $newTier = $member->membership_tier;

            $tierRank = ['basic' => 1, 'standard' => 2, 'premium' => 3, 'elite' => 4];

            if ($tierRank[$newTier] > $tierRank[$oldTier]) {
                $changes['upgrades']++;
                Mail::to($member)->send(new TierUpgraded($member, $oldTier, $newTier));
            } elseif ($tierRank[$newTier] < $tierRank[$oldTier]) {
                $changes['downgrades']++;
                Mail::to($member)->send(new TierDowngraded($member, $oldTier, $newTier));
            } else {
                $changes['unchanged']++;
            }
        }

        $this->table(
            ['Status', 'Count'],
            [
                ['Upgrades', $changes['upgrades']],
                ['Downgrades', $changes['downgrades']],
                ['Unchanged', $changes['unchanged']],
            ]
        );

        return 0;
    }
}

// Schedule in app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Update tiers monthly
    $schedule->command('gym:update-tiers')->monthly();
}

LARAVEL;

echo "\n".str_repeat('=', 72)."\n";
echo "Example completed! Check membership tier assignments above.\n";
echo str_repeat('=', 72)."\n";
