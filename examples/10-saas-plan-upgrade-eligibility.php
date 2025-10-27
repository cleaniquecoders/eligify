<?php

/**
 * Example 10: SaaS Plan Upgrade Eligibility System
 *
 * Use Case: Software-as-a-Service platform needs to determine which customers
 * are eligible for plan upgrades based on usage metrics, account tenure,
 * feature adoption, and engagement patterns.
 *
 * Features Demonstrated:
 * - Automated upgrade recommendations
 * - API integration patterns
 * - CLI command usage for batch processing
 * - Usage-based scoring
 * - Feature adoption tracking
 *
 * Business Logic:
 * - Usage metrics (API calls, storage, users)
 * - Account health score
 * - Feature adoption rate
 * - Support ticket patterns
 * - Payment history
 * - Engagement frequency
 *
 * Upgrade Paths:
 * - Enterprise (90-100%): Custom solutions, dedicated support
 * - Professional (75-89%): Advanced features, priority support
 * - Plus (60-74%): Enhanced limits, standard support
 * - Stay on Current (<60%): Not ready for upgrade
 */

require_once __DIR__.'/bootstrap.php';

use CleaniqueCoders\Eligify\Facades\Eligify;

echo '='.str_repeat('=', 70)."\n";
echo "  SaaS PLAN UPGRADE ELIGIBILITY SYSTEM\n";
echo '='.str_repeat('=', 70)."\n\n";

// ============================================================================
// STEP 1: Define Upgrade Eligibility Criteria
// ============================================================================

echo "📋 Setting up upgrade eligibility criteria...\n\n";

$criteria = Eligify::criteria('saas_upgrade_eligibility_2025')
    ->description('SaaS Platform - Automated Upgrade Recommendation Engine')

    // USAGE METRICS (40% weight)
    ->addRule('api_usage_percentage', '>=', 80, 15)  // Using 80%+ of plan limits
    ->addRule('storage_usage_percentage', '>=', 70, 10)
    ->addRule('active_users_ratio', '>=', 75, 10)  // 75%+ of seats used
    ->addRule('monthly_active_days', '>=', 20, 5)  // Active 20+ days/month

    // FEATURE ADOPTION (25% weight)
    ->addRule('features_adopted_percentage', '>=', 60, 15)  // Using 60%+ of features
    ->addRule('advanced_features_used', '>=', 3, 10)  // Using advanced features

    // ACCOUNT HEALTH (20% weight)
    ->addRule('account_tenure_months', '>=', 3, 10)
    ->addRule('payment_success_rate', '>=', 98, 5)
    ->addRule('support_satisfaction_score', '>=', 4.0, 5)  // 1-5 scale

    // ENGAGEMENT (15% weight)
    ->addRule('team_collaboration_score', '>=', 70, 10)
    ->addRule('integration_count', '>=', 2, 5)  // Connected integrations

    ->passThreshold(60)

    // Upgrade tier callbacks
    ->onScoreRange(90, 100, function ($account, $result) {
        echo "\n🚀 ENTERPRISE UPGRADE RECOMMENDED!\n";
        echo "   Account: {$account['company_name']}\n";
        echo "   Current Plan: {$account['current_plan']}\n";
        echo "   Upgrade Score: {$result['score']}%\n";
        echo "   \n";
        echo "   💎 ENTERPRISE BENEFITS:\n";
        echo "      • Unlimited API calls\n";
        echo "      • Unlimited storage\n";
        echo "      • Unlimited users\n";
        echo "      • Dedicated account manager\n";
        echo "      • 24/7 priority support\n";
        echo "      • Custom SLA (99.99% uptime)\n";
        echo "      • Advanced security features\n";
        echo "      • White-label options\n";
        echo "      • Custom integrations\n";
        echo "   \n";
        echo "   💰 PRICING: Custom (estimated $999+/month)\n";
        echo "   📈 ROI: High-value features for power users\n";
        echo "   \n";
        echo "   → Sales team will contact within 24 hours\n";
    })

    ->onScoreRange(75, 89, function ($account, $result) {
        $currentPrice = getPlanPrice($account['current_plan']);
        $newPrice = getPlanPrice('professional');
        echo "\n⭐ PROFESSIONAL UPGRADE RECOMMENDED!\n";
        echo "   Account: {$account['company_name']}\n";
        echo "   Current Plan: {$account['current_plan']} (\${$currentPrice}/mo)\n";
        echo "   Upgrade Score: {$result['score']}%\n";
        echo "   \n";
        echo "   🎯 PROFESSIONAL BENEFITS:\n";
        echo "      • 10x API call limit\n";
        echo "      • 5x storage increase\n";
        echo "      • Up to 50 users\n";
        echo "      • Priority support (24/5)\n";
        echo "      • Advanced analytics\n";
        echo "      • Custom workflows\n";
        echo "      • Webhook support\n";
        echo "      • SSO/SAML\n";
        echo "   \n";
        echo "   💰 PRICING: \${$newPrice}/month\n";
        echo "   📊 You'll save \$".round(($currentPrice * 12) * 0.15)."/year with annual billing\n";
        echo "   \n";
        echo "   → Upgrade available in your dashboard\n";
    })

    ->onScoreRange(60, 74, function ($account, $result) {
        $currentPrice = getPlanPrice($account['current_plan']);
        $newPrice = getPlanPrice('plus');
        echo "\n✨ PLUS UPGRADE RECOMMENDED!\n";
        echo "   Account: {$account['company_name']}\n";
        echo "   Current Plan: {$account['current_plan']} (\${$currentPrice}/mo)\n";
        echo "   Upgrade Score: {$result['score']}%\n";
        echo "   \n";
        echo "   ⚡ PLUS BENEFITS:\n";
        echo "      • 3x API call limit\n";
        echo "      • 2x storage increase\n";
        echo "      • Up to 20 users\n";
        echo "      • Standard support (8/5)\n";
        echo "      • Basic analytics\n";
        echo "      • Email support\n";
        echo "   \n";
        echo "   💰 PRICING: \${$newPrice}/month\n";
        echo "   📈 Perfect for growing teams\n";
        echo "   \n";
        echo "   → Upgrade with one click in your dashboard\n";
    })

    ->onFail(function ($account, $result) {
        echo "\n💡 STAY ON CURRENT PLAN\n";
        echo "   Account: {$account['company_name']}\n";
        echo "   Current Plan: {$account['current_plan']}\n";
        echo "   Score: {$result['score']}%\n";
        echo "   \n";
        echo "   → You're not fully utilizing your current plan yet.\n";
        echo "   \n";
        echo "   📊 TO MAXIMIZE YOUR PLAN:\n";

        $tips = [];
        if ($account['features_adopted_percentage'] < 60) {
            $tips[] = "Explore more features (currently using {$account['features_adopted_percentage']}%)";
        }
        if ($account['api_usage_percentage'] < 50) {
            $tips[] = 'Increase API usage to maximize value';
        }
        if ($account['active_users_ratio'] < 50) {
            $tips[] = "Invite more team members (using {$account['active_users_ratio']}% of seats)";
        }
        if ($account['integration_count'] < 2) {
            $tips[] = 'Connect more integrations to unlock workflows';
        }

        foreach (array_slice($tips, 0, 3) as $tip) {
            echo "      • {$tip}\n";
        }

        echo "   \n";
        echo "   → We'll check again next month!\n";
    })

    ->save();

// Helper function for plan pricing
function getPlanPrice(string $plan): int
{
    return match ($plan) {
        'starter' => 29,
        'basic' => 49,
        'plus' => 99,
        'professional' => 299,
        'enterprise' => 999,
        default => 0
    };
}

echo "✓ Upgrade eligibility criteria configured!\n";
echo "  - Automatic recommendations based on usage\n";
echo "  - Plans: Plus, Professional, Enterprise\n\n";

// ============================================================================
// STEP 2: Prepare Account Data
// ============================================================================

echo "📊 Analyzing account usage patterns...\n\n";

$accounts = [
    // CASE 1: Enterprise candidate - Power user
    [
        'company_name' => 'TechCorp Solutions',
        'current_plan' => 'professional',
        'api_usage_percentage' => 95,
        'storage_usage_percentage' => 88,
        'active_users_ratio' => 92,
        'monthly_active_days' => 28,
        'features_adopted_percentage' => 85,
        'advanced_features_used' => 8,
        'account_tenure_months' => 18,
        'payment_success_rate' => 100,
        'support_satisfaction_score' => 4.8,
        'team_collaboration_score' => 90,
        'integration_count' => 6,
    ],

    // CASE 2: Professional candidate - Growing fast
    [
        'company_name' => 'StartupHub Inc',
        'current_plan' => 'plus',
        'api_usage_percentage' => 85,
        'storage_usage_percentage' => 75,
        'active_users_ratio' => 80,
        'monthly_active_days' => 25,
        'features_adopted_percentage' => 70,
        'advanced_features_used' => 5,
        'account_tenure_months' => 9,
        'payment_success_rate' => 100,
        'support_satisfaction_score' => 4.5,
        'team_collaboration_score' => 75,
        'integration_count' => 4,
    ],

    // CASE 3: Plus candidate - Steady growth
    [
        'company_name' => 'Creative Studios',
        'current_plan' => 'basic',
        'api_usage_percentage' => 75,
        'storage_usage_percentage' => 70,
        'active_users_ratio' => 70,
        'monthly_active_days' => 22,
        'features_adopted_percentage' => 65,
        'advanced_features_used' => 3,
        'account_tenure_months' => 6,
        'payment_success_rate' => 98,
        'support_satisfaction_score' => 4.2,
        'team_collaboration_score' => 68,
        'integration_count' => 3,
    ],

    // CASE 4: Stay on current - New account
    [
        'company_name' => 'Freelancer Pro',
        'current_plan' => 'starter',
        'api_usage_percentage' => 45,
        'storage_usage_percentage' => 30,
        'active_users_ratio' => 100,  // Solo user
        'monthly_active_days' => 15,
        'features_adopted_percentage' => 40,
        'advanced_features_used' => 1,
        'account_tenure_months' => 2,
        'payment_success_rate' => 100,
        'support_satisfaction_score' => 4.0,
        'team_collaboration_score' => 50,
        'integration_count' => 1,
    ],

    // CASE 5: Stay on current - Underutilizing
    [
        'company_name' => 'Small Biz Co',
        'current_plan' => 'basic',
        'api_usage_percentage' => 25,
        'storage_usage_percentage' => 20,
        'active_users_ratio' => 30,
        'monthly_active_days' => 10,
        'features_adopted_percentage' => 35,
        'advanced_features_used' => 0,
        'account_tenure_months' => 12,
        'payment_success_rate' => 95,
        'support_satisfaction_score' => 3.5,
        'team_collaboration_score' => 40,
        'integration_count' => 0,
    ],
];

// ============================================================================
// STEP 3: Evaluate Each Account
// ============================================================================

echo "🔍 Evaluating upgrade eligibility...\n";
echo str_repeat('-', 72)."\n\n";

$upgradeResults = [];
$eligify = app(\CleaniqueCoders\Eligify\Eligify::class);

foreach ($accounts as $index => $account) {
    echo 'ACCOUNT '.($index + 1).": {$account['company_name']}\n";
    echo str_repeat('-', 72)."\n";
    echo "Current Plan: {$account['current_plan']}\n";
    echo "Account Age: {$account['account_tenure_months']} months\n";
    echo "\nUsage Metrics:\n";
    echo "  • API Usage: {$account['api_usage_percentage']}%\n";
    echo "  • Storage: {$account['storage_usage_percentage']}%\n";
    echo "  • Active Users: {$account['active_users_ratio']}%\n";
    echo "  • Active Days: {$account['monthly_active_days']}/month\n";
    echo "\nEngagement:\n";
    echo "  • Features Adopted: {$account['features_adopted_percentage']}%\n";
    echo "  • Advanced Features: {$account['advanced_features_used']}\n";
    echo "  • Integrations: {$account['integration_count']}\n";
    echo "  • Collaboration Score: {$account['team_collaboration_score']}%\n";

    // Evaluate with callbacks
    $result = $eligify->evaluateWithCallbacks($criteria, $account);

    // Determine recommended plan
    $recommendation = match (true) {
        $result['score'] >= 90 => 'Enterprise',
        $result['score'] >= 75 => 'Professional',
        $result['score'] >= 60 => 'Plus',
        default => 'Stay on '.ucfirst($account['current_plan'])
    };

    $upgradeResults[] = [
        'company' => $account['company_name'],
        'current_plan' => $account['current_plan'],
        'score' => $result['score'],
        'recommendation' => $recommendation,
        'should_upgrade' => $result['passed'],
        'api_usage' => $account['api_usage_percentage'],
    ];

    echo "\n📊 UPGRADE ANALYSIS:\n";
    echo "   Eligibility Score: {$result['score']}%\n";
    echo "   Recommendation: {$recommendation}\n";
    echo '   Action: '.($result['passed'] ? '🚀 UPGRADE AVAILABLE' : '💡 STAY ON CURRENT')."\n";

    echo "\n".str_repeat('=', 72)."\n\n";
}

// ============================================================================
// STEP 4: Upgrade Recommendations Summary
// ============================================================================

echo "📊 UPGRADE RECOMMENDATIONS SUMMARY\n";
echo str_repeat('-', 72)."\n\n";

printf("%-25s | %-12s | %-10s | %-20s\n",
    'Company', 'Current', 'Score', 'Recommendation');
echo str_repeat('-', 72)."\n";

$upgradeCount = 0;
$revenueOpportunity = 0;

foreach ($upgradeResults as $result) {
    printf("%-25s | %-12s | %7.1f%% | %-20s\n",
        $result['company'],
        ucfirst($result['current_plan']),
        $result['score'],
        $result['recommendation']
    );

    if ($result['should_upgrade']) {
        $upgradeCount++;
        $currentPrice = getPlanPrice($result['current_plan']);
        $targetPlan = strtolower(str_replace('Stay on ', '', $result['recommendation']));
        $newPrice = getPlanPrice($targetPlan);
        $revenueOpportunity += ($newPrice - $currentPrice);
    }
}

echo str_repeat('-', 72)."\n";
echo "Upgrade Opportunities: {$upgradeCount}/".count($upgradeResults)."\n";
echo 'Monthly Revenue Opportunity: $'.number_format($revenueOpportunity)."\n";
echo 'Annual Revenue Opportunity: $'.number_format($revenueOpportunity * 12)."\n\n";

// ============================================================================
// STEP 5: CLI & API Integration
// ============================================================================

echo "💡 CLI COMMAND USAGE FOR BATCH PROCESSING:\n";
echo str_repeat('-', 72)."\n";
echo <<<'CLI'

# Evaluate single account
php artisan eligify:evaluate saas_upgrade_eligibility_2025 \
  --data='{"company_name":"ACME Corp","api_usage_percentage":85}'

# Batch process all accounts from database
php artisan eligify:evaluate saas_upgrade_eligibility_2025 \
  --batch \
  --file=accounts.json

# Export upgrade recommendations
php artisan eligify:evaluate saas_upgrade_eligibility_2025 \
  --batch \
  --export=upgrade-recommendations.json

# Run monthly upgrade analysis
php artisan eligify:criteria list
php artisan eligify:evaluate saas_upgrade_eligibility_2025 --batch

CLI;

echo "\n\n💡 API INTEGRATION PATTERN:\n";
echo str_repeat('-', 72)."\n";
echo <<<'API'

// API Endpoint for upgrade recommendations
// GET /api/v1/accounts/{account}/upgrade-eligibility

public function getUpgradeEligibility(Account $account)
{
    $result = $account->checkEligibility('saas_upgrade_eligibility_2025');

    $recommendation = match(true) {
        $result['score'] >= 90 => [
            'plan' => 'enterprise',
            'priority' => 'high',
            'action' => 'contact_sales',
        ],
        $result['score'] >= 75 => [
            'plan' => 'professional',
            'priority' => 'medium',
            'action' => 'show_upgrade_modal',
        ],
        $result['score'] >= 60 => [
            'plan' => 'plus',
            'priority' => 'low',
            'action' => 'show_banner',
        ],
        default => [
            'plan' => $account->current_plan,
            'priority' => 'none',
            'action' => 'show_tips',
        ]
    };

    return response()->json([
        'eligible' => $result['passed'],
        'score' => $result['score'],
        'current_plan' => $account->current_plan,
        'recommended_plan' => $recommendation['plan'],
        'priority' => $recommendation['priority'],
        'suggested_action' => $recommendation['action'],
        'metrics' => [
            'api_usage' => $account->api_usage_percentage,
            'feature_adoption' => $account->features_adopted_percentage,
            'tenure_months' => $account->tenure_months,
        ],
        'estimated_revenue_increase' => $this->calculateRevenue($recommendation['plan'], $account->current_plan),
    ]);
}

// Automated upgrade notifications
class CheckUpgradeEligibility extends Command
{
    public function handle()
    {
        Account::where('status', 'active')
            ->chunk(100, function ($accounts) {
                foreach ($accounts as $account) {
                    $result = $account->checkEligibility('saas_upgrade_eligibility_2025');

                    if ($result['passed'] && $result['score'] >= 75) {
                        // High-value upgrade opportunity
                        Mail::to($account->owner)->send(
                            new UpgradeRecommendation($account, $result)
                        );

                        // Track in CRM
                        $account->salesTasks()->create([
                            'type' => 'upgrade_opportunity',
                            'priority' => $result['score'] >= 90 ? 'high' : 'medium',
                            'notes' => "Upgrade score: {$result['score']}%",
                        ]);
                    }
                }
            });
    }
}

// In-app upgrade prompts
class UpgradeWidget
{
    public function render(Account $account)
    {
        $eligibility = $account->checkEligibility('saas_upgrade_eligibility_2025');

        if (!$eligibility['passed']) {
            return null; // Don't show widget
        }

        $recommendedPlan = match(true) {
            $eligibility['score'] >= 90 => 'enterprise',
            $eligibility['score'] >= 75 => 'professional',
            default => 'plus'
        };

        return view('components.upgrade-widget', [
            'score' => $eligibility['score'],
            'recommended_plan' => $recommendedPlan,
            'benefits' => $this->getPlanBenefits($recommendedPlan),
            'pricing' => $this->getPlanPricing($recommendedPlan),
        ]);
    }
}

API;

echo "\n".str_repeat('=', 72)."\n";
echo "Example completed! Check upgrade recommendations above.\n";
echo str_repeat('=', 72)."\n";
echo "\n🎉 ALL 10 EXAMPLES COMPLETE!\n";
echo "Explore each example to see different use cases and integration patterns.\n";
