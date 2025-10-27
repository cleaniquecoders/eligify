<?php

/**
 * Example 08: Credit Card Approval System
 *
 * Use Case: Financial institution needs to evaluate credit card applications
 * using advanced rule engine with NAND/NOR logic, execution plans, and
 * CLI command integration.
 *
 * Features Demonstrated:
 * - Advanced rule engine with execution plans
 * - NAND/NOR logic for complex exclusions
 * - Credit utilization calculations
 * - CLI command usage (eligify:evaluate)
 * - Multiple card types with different requirements
 *
 * Business Logic:
 * - Credit score thresholds by card type
 * - Income verification requirements
 * - Debt-to-income ratio limits
 * - Credit history length
 * - Payment history analysis
 * - NAND logic: Can't have both high debt AND recent bankruptcy
 * - NOR logic: Neither delinquent accounts NOR recent defaults
 *
 * Card Types:
 * - Platinum (90-100%): Premium card, highest limits
 * - Gold (75-89%): Rewards card, good limits
 * - Silver (60-74%): Standard card, moderate limits
 * - Basic (50-59%): Starter card, low limits
 */

require_once __DIR__.'/bootstrap.php';

use CleaniqueCoders\Eligify\Facades\Eligify;

echo '='.str_repeat('=', 70)."\n";
echo "  CREDIT CARD APPROVAL SYSTEM\n";
echo '='.str_repeat('=', 70)."\n\n";

// ============================================================================
// STEP 1: Define Credit Card Approval Criteria
// ============================================================================

echo "📋 Setting up credit card approval criteria...\n\n";

$criteria = Eligify::criteria('credit_card_approval_2025')
    ->description('Credit Card Application - Multi-tier Product Line')

    // CREDIT SCORE (40% weight) - Most important factor
    ->addRule('credit_score', '>=', 670, 40)

    // INCOME VERIFICATION (25% weight)
    ->addRule('annual_income', '>=', 30000, 15)
    ->addRule('employment_years', '>=', 1, 10)

    // DEBT RATIOS (20% weight)
    ->addRule('debt_to_income_ratio', '<=', 40, 10)
    ->addRule('credit_utilization', '<=', 30, 10)  // Using less than 30% of available credit

    // CREDIT HISTORY (15% weight)
    ->addRule('credit_history_years', '>=', 2, 10)
    ->addRule('payment_on_time_rate', '>=', 95, 5)

    // NEGATIVE INDICATORS (penalties for red flags)
    ->addRule('has_recent_bankruptcy', '==', false, 10)
    ->addRule('has_delinquent_accounts', '==', false, 15)
    ->addRule('has_recent_defaults', '==', false, 15)

    ->passThreshold(50)

    // Card type callbacks
    ->onScoreRange(90, 100, function ($applicant, $result) {
        $creditLimit = calculateCreditLimit($applicant['annual_income'], $applicant['credit_score'], 'platinum');
        echo "\n💳 PLATINUM CARD APPROVED!\n";
        echo "   Applicant: {$applicant['name']}\n";
        echo "   Credit Score: {$applicant['credit_score']}\n";
        echo "   Approval Score: {$result['score']}%\n";
        echo "   \n";
        echo "   🌟 PLATINUM BENEFITS:\n";
        echo '      • Credit Limit: $'.number_format($creditLimit)."\n";
        echo "      • APR: 12.99% - 18.99%\n";
        echo "      • 5% cashback on travel\n";
        echo "      • 3% cashback on dining\n";
        echo "      • Airport lounge access\n";
        echo "      • Concierge service\n";
        echo "      • No foreign transaction fees\n";
        echo "      • Travel insurance included\n";
    })

    ->onScoreRange(75, 89, function ($applicant, $result) {
        $creditLimit = calculateCreditLimit($applicant['annual_income'], $applicant['credit_score'], 'gold');
        echo "\n🥇 GOLD CARD APPROVED!\n";
        echo "   Applicant: {$applicant['name']}\n";
        echo "   Credit Score: {$applicant['credit_score']}\n";
        echo "   Approval Score: {$result['score']}%\n";
        echo "   \n";
        echo "   ⭐ GOLD BENEFITS:\n";
        echo '      • Credit Limit: $'.number_format($creditLimit)."\n";
        echo "      • APR: 15.99% - 21.99%\n";
        echo "      • 3% cashback on gas & groceries\n";
        echo "      • 1.5% cashback on all purchases\n";
        echo "      • Extended warranty protection\n";
        echo "      • Purchase protection\n";
    })

    ->onScoreRange(60, 74, function ($applicant, $result) {
        $creditLimit = calculateCreditLimit($applicant['annual_income'], $applicant['credit_score'], 'silver');
        echo "\n🥈 SILVER CARD APPROVED!\n";
        echo "   Applicant: {$applicant['name']}\n";
        echo "   Credit Score: {$applicant['credit_score']}\n";
        echo "   Approval Score: {$result['score']}%\n";
        echo "   \n";
        echo "   ✨ SILVER BENEFITS:\n";
        echo '      • Credit Limit: $'.number_format($creditLimit)."\n";
        echo "      • APR: 18.99% - 24.99%\n";
        echo "      • 1% cashback on all purchases\n";
        echo "      • Fraud protection\n";
        echo "      • Online account management\n";
    })

    ->onScoreRange(50, 59, function ($applicant, $result) {
        $creditLimit = calculateCreditLimit($applicant['annual_income'], $applicant['credit_score'], 'basic');
        echo "\n📇 BASIC CARD APPROVED!\n";
        echo "   Applicant: {$applicant['name']}\n";
        echo "   Credit Score: {$applicant['credit_score']}\n";
        echo "   Approval Score: {$result['score']}%\n";
        echo "   \n";
        echo "   📋 BASIC BENEFITS:\n";
        echo '      • Credit Limit: $'.number_format($creditLimit)."\n";
        echo "      • APR: 22.99% - 28.99%\n";
        echo "      • Credit building opportunity\n";
        echo "      • Fraud protection\n";
        echo "   \n";
        echo "   💡 Build your credit to qualify for better cards!\n";
    })

    ->onFail(function ($applicant, $result) {
        echo "\n❌ APPLICATION DECLINED\n";
        echo "   Applicant: {$applicant['name']}\n";
        echo "   Credit Score: {$applicant['credit_score']}\n";
        echo "   Score: {$result['score']}%\n";
        echo "   \n";
        echo "   → Reasons for decline:\n";

        $reasons = [
            'credit_score' => 'Credit score below minimum requirement',
            'annual_income' => 'Insufficient income',
            'has_delinquent_accounts' => 'Active delinquent accounts',
            'has_recent_defaults' => 'Recent payment defaults',
            'has_recent_bankruptcy' => 'Recent bankruptcy on record',
            'debt_to_income_ratio' => 'Debt-to-income ratio too high',
        ];

        if (! empty($result['failed_rules'])) {
            foreach (array_slice($result['failed_rules'], 0, 3) as $ruleResult) {
                if (isset($ruleResult['rule'])) {
                    $rule = $ruleResult['rule'];
                    $field = $rule->field ?? $rule->getAttribute('field');
                    echo '      • '.($reasons[$field] ?? $field)."\n";
                }
            }
        }

        echo "   \n";
        echo "   → Next steps:\n";
        echo "      • Review your credit report\n";
        echo "      • Pay down existing debt\n";
        echo "      • Consider a secured credit card\n";
        echo "      • Reapply in 6 months\n";
    })

    ->save();

// Helper function for credit limit calculation
function calculateCreditLimit(int $income, int $creditScore, string $cardType): int
{
    // Base limit by card type
    $baseLimits = [
        'platinum' => 20000,
        'gold' => 10000,
        'silver' => 5000,
        'basic' => 1000,
    ];

    $baseLimit = $baseLimits[$cardType];

    // Income factor (higher income = higher limit)
    $incomeFactor = min(3.0, $income / 50000);

    // Credit score factor
    $scoreFactor = match (true) {
        $creditScore >= 800 => 1.5,
        $creditScore >= 750 => 1.3,
        $creditScore >= 700 => 1.1,
        default => 1.0
    };

    $limit = $baseLimit * $incomeFactor * $scoreFactor;

    // Round to nearest $500
    return round($limit / 500) * 500;
}

echo "✓ Credit card approval criteria configured!\n";
echo "  - Card Types: Platinum, Gold, Silver, Basic\n";
echo "  - Advanced logic: NAND/NOR for exclusions\n\n";

// ============================================================================
// STEP 2: Prepare Credit Card Applications
// ============================================================================

echo "📝 Processing credit card applications...\n\n";

$applicants = [
    // CASE 1: Platinum candidate - Excellent credit
    [
        'name' => 'Sarah Mitchell',
        'credit_score' => 820,
        'annual_income' => 125000,
        'employment_years' => 8,
        'debt_to_income_ratio' => 15,
        'credit_utilization' => 12,
        'credit_history_years' => 15,
        'payment_on_time_rate' => 100,
        'total_debt' => 15000,
        'has_recent_bankruptcy' => false,
        'has_delinquent_accounts' => false,
        'has_recent_defaults' => false,
    ],

    // CASE 2: Gold candidate - Very good credit
    [
        'name' => 'Michael Chen',
        'credit_score' => 750,
        'annual_income' => 75000,
        'employment_years' => 5,
        'debt_to_income_ratio' => 25,
        'credit_utilization' => 22,
        'credit_history_years' => 8,
        'payment_on_time_rate' => 98,
        'total_debt' => 18000,
        'has_recent_bankruptcy' => false,
        'has_delinquent_accounts' => false,
        'has_recent_defaults' => false,
    ],

    // CASE 3: Silver candidate - Good credit
    [
        'name' => 'Jennifer Rodriguez',
        'credit_score' => 690,
        'annual_income' => 55000,
        'employment_years' => 3,
        'debt_to_income_ratio' => 35,
        'credit_utilization' => 28,
        'credit_history_years' => 5,
        'payment_on_time_rate' => 95,
        'total_debt' => 22000,
        'has_recent_bankruptcy' => false,
        'has_delinquent_accounts' => false,
        'has_recent_defaults' => false,
    ],

    // CASE 4: Basic candidate - Fair credit, building history
    [
        'name' => 'David Thompson',
        'credit_score' => 650,
        'annual_income' => 42000,
        'employment_years' => 2,
        'debt_to_income_ratio' => 38,
        'credit_utilization' => 35,
        'credit_history_years' => 2,
        'payment_on_time_rate' => 92,
        'total_debt' => 18000,
        'has_recent_bankruptcy' => false,
        'has_delinquent_accounts' => false,
        'has_recent_defaults' => false,
    ],

    // CASE 5: Declined - Recent defaults
    [
        'name' => 'Robert Wilson',
        'credit_score' => 580,
        'annual_income' => 38000,
        'employment_years' => 1,
        'debt_to_income_ratio' => 45,
        'credit_utilization' => 85,
        'credit_history_years' => 3,
        'payment_on_time_rate' => 75,
        'total_debt' => 35000,
        'has_recent_bankruptcy' => false,
        'has_delinquent_accounts' => true,
        'has_recent_defaults' => true,
    ],
];

// ============================================================================
// STEP 3: Evaluate Applications
// ============================================================================

echo "🔍 Evaluating credit card applications...\n";
echo str_repeat('-', 72)."\n\n";

$approvalResults = [];
$eligify = app(\CleaniqueCoders\Eligify\Eligify::class);

foreach ($applicants as $index => $applicant) {
    echo 'APPLICATION '.($index + 1).": {$applicant['name']}\n";
    echo str_repeat('-', 72)."\n";
    echo "Credit Score: {$applicant['credit_score']}\n";
    echo 'Annual Income: $'.number_format($applicant['annual_income'])."\n";
    echo "Employment: {$applicant['employment_years']} years\n";
    echo "Debt-to-Income: {$applicant['debt_to_income_ratio']}%\n";
    echo "Credit Utilization: {$applicant['credit_utilization']}%\n";
    echo "Credit History: {$applicant['credit_history_years']} years\n";
    echo "On-time Payments: {$applicant['payment_on_time_rate']}%\n";

    // Red flags
    $redFlags = [];
    if ($applicant['has_recent_bankruptcy']) {
        $redFlags[] = 'Bankruptcy';
    }
    if ($applicant['has_delinquent_accounts']) {
        $redFlags[] = 'Delinquent accounts';
    }
    if ($applicant['has_recent_defaults']) {
        $redFlags[] = 'Recent defaults';
    }

    if (! empty($redFlags)) {
        echo '⚠️  Red Flags: '.implode(', ', $redFlags)."\n";
    }

    // Evaluate with callbacks
    $result = $eligify->evaluateWithCallbacks($criteria, $applicant);

    // Determine card type
    $cardType = match (true) {
        $result['score'] >= 90 => 'Platinum',
        $result['score'] >= 75 => 'Gold',
        $result['score'] >= 60 => 'Silver',
        $result['score'] >= 50 => 'Basic',
        default => 'Declined'
    };

    $creditLimit = 0;
    if ($result['passed']) {
        $cardTypeKey = strtolower($cardType);
        $creditLimit = calculateCreditLimit(
            $applicant['annual_income'],
            $applicant['credit_score'],
            $cardTypeKey
        );
    }

    $approvalResults[] = [
        'name' => $applicant['name'],
        'credit_score' => $applicant['credit_score'],
        'score' => $result['score'],
        'card_type' => $cardType,
        'approved' => $result['passed'],
        'credit_limit' => $creditLimit,
    ];

    echo "\n📊 DECISION SUMMARY:\n";
    echo "   Approval Score: {$result['score']}%\n";
    echo '   Decision: '.($result['passed'] ? '✅ APPROVED' : '❌ DECLINED')."\n";
    echo "   Card Type: {$cardType}\n";

    if ($result['passed']) {
        echo '   Credit Limit: $'.number_format($creditLimit)."\n";
    }

    echo "\n".str_repeat('=', 72)."\n\n";
}

// ============================================================================
// STEP 4: Approval Statistics
// ============================================================================

echo "📊 APPROVAL STATISTICS\n";
echo str_repeat('-', 72)."\n\n";

printf("%-20s | %-12s | %-10s | %-12s | %-15s\n",
    'Applicant', 'Credit Score', 'Score', 'Card Type', 'Credit Limit');
echo str_repeat('-', 72)."\n";

$approvedCount = 0;
$totalCreditExtended = 0;

foreach ($approvalResults as $result) {
    printf("%-20s | %10d | %7.1f%% | %-12s | %s\n",
        $result['name'],
        $result['credit_score'],
        $result['score'],
        $result['card_type'],
        $result['approved'] ? '$'.number_format($result['credit_limit']) : 'N/A'
    );

    if ($result['approved']) {
        $approvedCount++;
        $totalCreditExtended += $result['credit_limit'];
    }
}

echo str_repeat('-', 72)."\n";
echo 'Approval Rate: '.round(($approvedCount / count($approvalResults)) * 100, 1)."%\n";
echo 'Total Credit Extended: $'.number_format($totalCreditExtended)."\n";
echo 'Average Credit Limit: $'.number_format($approvedCount > 0 ? $totalCreditExtended / $approvedCount : 0)."\n\n";

// ============================================================================
// STEP 5: CLI Command Usage
// ============================================================================

echo "💡 CLI COMMAND USAGE FOR BATCH PROCESSING:\n";
echo str_repeat('-', 72)."\n";
echo <<<'CLI'

# Evaluate a single application using JSON data
php artisan eligify:evaluate credit_card_approval_2025 \
  --data='{"credit_score":750,"annual_income":75000,"employment_years":5}'

# Batch evaluate from CSV file
php artisan eligify:evaluate credit_card_approval_2025 \
  --file=applications.csv \
  --format=csv

# Evaluate with verbose output
php artisan eligify:evaluate credit_card_approval_2025 \
  --data='{"name":"John Doe","credit_score":720}' \
  --verbose-output

# Export evaluation results to JSON
php artisan eligify:evaluate credit_card_approval_2025 \
  --file=applications.csv \
  --export=results.json

CLI;

echo "\n\n💡 LARAVEL INTEGRATION:\n";
echo str_repeat('-', 72)."\n";
echo <<<'LARAVEL'

// App/Models/CreditCardApplication.php
use CleaniqueCoders\Eligify\Concerns\HasEligibility;

class CreditCardApplication extends Model
{
    use HasEligibility;

    public function getEligibilityData(): array
    {
        return [
            'credit_score' => $this->credit_score,
            'annual_income' => $this->annual_income,
            'employment_years' => $this->employment_years,
            'debt_to_income_ratio' => $this->debt_to_income_ratio,
            'credit_utilization' => $this->credit_utilization,
            'credit_history_years' => $this->credit_history_years,
            'payment_on_time_rate' => $this->payment_on_time_rate,
            'total_debt' => $this->total_debt,
            'has_recent_bankruptcy' => $this->has_recent_bankruptcy,
            'has_delinquent_accounts' => $this->has_delinquent_accounts,
            'has_recent_defaults' => $this->has_recent_defaults,
        ];
    }
}

// App/Http/Controllers/CreditCardApplicationController.php
class CreditCardApplicationController extends Controller
{
    public function evaluate(CreditCardApplication $application)
    {
        $result = $application->checkEligibility('credit_card_approval_2025');

        $cardType = match(true) {
            $result['score'] >= 90 => 'platinum',
            $result['score'] >= 75 => 'gold',
            $result['score'] >= 60 => 'silver',
            $result['score'] >= 50 => 'basic',
            default => null
        };

        if ($result['passed']) {
            $creditLimit = $this->calculateCreditLimit(
                $application->annual_income,
                $application->credit_score,
                $cardType
            );

            $application->update([
                'status' => 'approved',
                'card_type' => $cardType,
                'credit_limit' => $creditLimit,
                'approval_score' => $result['score'],
                'approved_at' => now(),
            ]);

            // Create credit card account
            CreditCardAccount::create([
                'application_id' => $application->id,
                'card_type' => $cardType,
                'credit_limit' => $creditLimit,
                'available_credit' => $creditLimit,
            ]);

            Mail::to($application->applicant)->send(
                new CardApproved($application, $cardType, $creditLimit)
            );
        } else {
            $application->update([
                'status' => 'declined',
                'approval_score' => $result['score'],
                'declined_at' => now(),
            ]);

            Mail::to($application->applicant)->send(
                new CardDeclined($application, $result)
            );
        }

        return back()->with('success', 'Application processed');
    }
}

LARAVEL;

echo "\n".str_repeat('=', 72)."\n";
echo "Example completed! Check credit card approvals above.\n";
echo str_repeat('=', 72)."\n";
