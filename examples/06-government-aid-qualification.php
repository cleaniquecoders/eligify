<?php

/**
 * Example 06: Government Social Aid Qualification System
 *
 * Use Case: Government agency needs to determine eligibility for social
 * assistance programs based on income, family size, assets, employment
 * status, and special circumstances.
 *
 * Features Demonstrated:
 * - Multi-criteria evaluation for social programs
 * - Sensitive data handling considerations
 * - Comprehensive audit logging
 * - Priority-based qualification
 * - Income bracket calculations
 *
 * Business Logic:
 * - Federal Poverty Level (FPL) calculations
 * - Asset limits and income thresholds
 * - Family composition considerations
 * - Employment and disability status
 * - Special circumstances (elderly, children, veterans)
 *
 * Aid Programs:
 * - Full Assistance (80-100%): Maximum benefits
 * - Standard Assistance (60-79%): Regular benefits
 * - Limited Assistance (50-59%): Partial benefits
 * - Not Eligible (<50%): Refer to other programs
 */

require_once __DIR__.'/bootstrap.php';

use CleaniqueCoders\Eligify\Facades\Eligify;

echo '='.str_repeat('=', 70)."\n";
echo "  GOVERNMENT SOCIAL AID QUALIFICATION SYSTEM\n";
echo '='.str_repeat('=', 70)."\n\n";

// ============================================================================
// STEP 1: Define Aid Qualification Criteria
// ============================================================================

echo "📋 Setting up social aid qualification criteria...\n\n";

// 2025 Federal Poverty Level (example values)
$fplByFamily = [
    1 => 15000,
    2 => 20000,
    3 => 25000,
    4 => 30000,
    5 => 35000,
    6 => 40000,
];

$criteria = Eligify::criteria('social_assistance_program_2025')
    ->description('Federal Social Assistance Program - General Aid')

    // INCOME REQUIREMENTS (40% weight) - Must be below 200% FPL
    ->addRule('income_to_fpl_ratio', '<=', 200, 40)  // Income as % of FPL

    // ASSET LIMITS (20% weight)
    ->addRule('liquid_assets', '<=', 5000, 10)
    ->addRule('total_assets', '<=', 15000, 10)

    // FAMILY COMPOSITION (15% weight)
    ->addRule('dependent_children_count', '>=', 0, 5)  // Has dependents
    ->addRule('has_elderly_members', '==', true, 5)    // 65+ in household
    ->addRule('has_disabled_members', '==', true, 5)   // Disabled members

    // EMPLOYMENT STATUS (15% weight)
    ->addRule('is_unemployed', '==', true, 10)
    ->addRule('months_unemployed', '>=', 3, 5)

    // SPECIAL CIRCUMSTANCES (10% weight)
    ->addRule('is_veteran', '==', true, 5)
    ->addRule('is_homeless', '==', true, 5)

    ->passThreshold(50)

    // Aid tier callbacks
    ->onScoreRange(80, 100, function ($applicant, $result) {
        $monthlyBenefit = calculateBenefit($applicant['family_size'], 'full');
        echo "\n✅ APPROVED - FULL ASSISTANCE\n";
        echo "   Applicant: {$applicant['name']}\n";
        echo "   Family Size: {$applicant['family_size']}\n";
        echo "   Monthly Benefit: \${$monthlyBenefit}\n";
        echo '   Annual Total: $'.number_format($monthlyBenefit * 12)."\n";
        echo "   Additional Benefits:\n";
        echo "      • Food assistance (SNAP)\n";
        echo "      • Healthcare coverage\n";
        echo "      • Utility assistance\n";
        echo "      • Housing voucher eligible\n";
        echo "   → Case manager will contact you within 5 business days.\n";
    })

    ->onScoreRange(60, 79, function ($applicant, $result) {
        $monthlyBenefit = calculateBenefit($applicant['family_size'], 'standard');
        echo "\n✅ APPROVED - STANDARD ASSISTANCE\n";
        echo "   Applicant: {$applicant['name']}\n";
        echo "   Family Size: {$applicant['family_size']}\n";
        echo "   Monthly Benefit: \${$monthlyBenefit}\n";
        echo '   Annual Total: $'.number_format($monthlyBenefit * 12)."\n";
        echo "   Benefits:\n";
        echo "      • Cash assistance\n";
        echo "      • Food assistance (SNAP)\n";
        echo "      • Healthcare coverage\n";
        echo "   → Benefits will start in 2-3 weeks.\n";
    })

    ->onScoreRange(50, 59, function ($applicant, $result) {
        $monthlyBenefit = calculateBenefit($applicant['family_size'], 'limited');
        echo "\n✅ APPROVED - LIMITED ASSISTANCE\n";
        echo "   Applicant: {$applicant['name']}\n";
        echo "   Family Size: {$applicant['family_size']}\n";
        echo "   Monthly Benefit: \${$monthlyBenefit}\n";
        echo '   Annual Total: $'.number_format($monthlyBenefit * 12)."\n";
        echo "   Benefits:\n";
        echo "      • Food assistance (SNAP)\n";
        echo "      • Emergency assistance eligible\n";
        echo "   → Consider applying for additional programs.\n";
    })

    ->onFail(function ($applicant, $result) {
        echo "\n❌ NOT ELIGIBLE FOR THIS PROGRAM\n";
        echo "   Applicant: {$applicant['name']}\n";
        echo "   Qualification Score: {$result['score']}%\n";
        echo "   → Your income/assets exceed program limits.\n";
        echo "   → Alternative resources:\n";
        echo "      • Job training programs\n";
        echo "      • Community food banks\n";
        echo "      • Local charity organizations\n";
        echo "      • Workforce development centers\n";
    })

    ->save();

// Helper function for benefit calculation
function calculateBenefit(int $familySize, string $tier): int
{
    $baseAmounts = [
        'full' => [1 => 800, 2 => 1200, 3 => 1600, 4 => 2000, 5 => 2400, 6 => 2800],
        'standard' => [1 => 500, 2 => 800, 3 => 1100, 4 => 1400, 5 => 1700, 6 => 2000],
        'limited' => [1 => 250, 2 => 400, 3 => 550, 4 => 700, 5 => 850, 6 => 1000],
    ];

    $size = min($familySize, 6);

    return $baseAmounts[$tier][$size] ?? 0;
}

echo "✓ Aid qualification criteria configured!\n";
echo "  - Program: Social Assistance 2025\n";
echo "  - Minimum Score: 50%\n\n";

// ============================================================================
// STEP 2: Prepare Applicant Data
// ============================================================================

echo "📝 Processing aid applications...\n\n";

$applicants = [
    // CASE 1: Full assistance - Homeless veteran with children
    [
        'name' => 'Robert Johnson',
        'family_size' => 3,
        'monthly_income' => 0,
        'income_to_fpl_ratio' => 0,  // No income
        'liquid_assets' => 200,
        'total_assets' => 500,
        'dependent_children_count' => 2,
        'has_elderly_members' => false,
        'has_disabled_members' => false,
        'is_unemployed' => true,
        'months_unemployed' => 8,
        'is_veteran' => true,
        'is_homeless' => true,
    ],

    // CASE 2: Full assistance - Elderly couple with disability
    [
        'name' => 'Mary Thompson',
        'family_size' => 2,
        'monthly_income' => 1500,
        'income_to_fpl_ratio' => 90,  // 90% of FPL
        'liquid_assets' => 1200,
        'total_assets' => 3500,
        'dependent_children_count' => 0,
        'has_elderly_members' => true,
        'has_disabled_members' => true,
        'is_unemployed' => true,
        'months_unemployed' => 24,
        'is_veteran' => false,
        'is_homeless' => false,
    ],

    // CASE 3: Standard assistance - Single parent
    [
        'name' => 'Jennifer Martinez',
        'family_size' => 4,
        'monthly_income' => 3200,
        'income_to_fpl_ratio' => 128,  // 128% of FPL
        'liquid_assets' => 800,
        'total_assets' => 5000,
        'dependent_children_count' => 3,
        'has_elderly_members' => false,
        'has_disabled_members' => false,
        'is_unemployed' => false,
        'months_unemployed' => 0,
        'is_veteran' => false,
        'is_homeless' => false,
    ],

    // CASE 4: Limited assistance - Working but struggling
    [
        'name' => 'David Kim',
        'family_size' => 2,
        'monthly_income' => 3500,
        'income_to_fpl_ratio' => 175,  // 175% of FPL
        'liquid_assets' => 2500,
        'total_assets' => 8000,
        'dependent_children_count' => 1,
        'has_elderly_members' => false,
        'has_disabled_members' => false,
        'is_unemployed' => false,
        'months_unemployed' => 0,
        'is_veteran' => false,
        'is_homeless' => false,
    ],

    // CASE 5: Not eligible - Income too high
    [
        'name' => 'Sarah Anderson',
        'family_size' => 3,
        'monthly_income' => 6500,
        'income_to_fpl_ratio' => 312,  // 312% of FPL
        'liquid_assets' => 8000,
        'total_assets' => 25000,
        'dependent_children_count' => 2,
        'has_elderly_members' => false,
        'has_disabled_members' => false,
        'is_unemployed' => false,
        'months_unemployed' => 0,
        'is_veteran' => false,
        'is_homeless' => false,
    ],
];

// ============================================================================
// STEP 3: Evaluate Each Application
// ============================================================================

echo "🔍 Evaluating aid applications...\n";
echo str_repeat('-', 72)."\n\n";

$qualificationResults = [];
$eligify = app(\CleaniqueCoders\Eligify\Eligify::class);

foreach ($applicants as $index => $applicant) {
    echo 'APPLICATION '.($index + 1).": {$applicant['name']}\n";
    echo str_repeat('-', 72)."\n";
    echo "Family Size: {$applicant['family_size']}\n";
    echo 'Monthly Income: $'.number_format($applicant['monthly_income'])." ({$applicant['income_to_fpl_ratio']}% FPL)\n";
    echo 'Liquid Assets: $'.number_format($applicant['liquid_assets'])."\n";
    echo 'Total Assets: $'.number_format($applicant['total_assets'])."\n";
    echo "Dependent Children: {$applicant['dependent_children_count']}\n";
    echo 'Employment Status: '.($applicant['is_unemployed'] ? "Unemployed ({$applicant['months_unemployed']} months)" : 'Employed')."\n";

    $circumstances = [];
    if ($applicant['has_elderly_members']) {
        $circumstances[] = 'Elderly member(s)';
    }
    if ($applicant['has_disabled_members']) {
        $circumstances[] = 'Disabled member(s)';
    }
    if ($applicant['is_veteran']) {
        $circumstances[] = 'Veteran';
    }
    if ($applicant['is_homeless']) {
        $circumstances[] = 'Homeless';
    }

    if (! empty($circumstances)) {
        echo 'Special Circumstances: '.implode(', ', $circumstances)."\n";
    }

    // Evaluate with callbacks
    $result = $eligify->evaluateWithCallbacks($criteria, $applicant);

    // Determine aid tier
    $tier = match (true) {
        $result['score'] >= 80 => 'Full Assistance',
        $result['score'] >= 60 => 'Standard Assistance',
        $result['score'] >= 50 => 'Limited Assistance',
        default => 'Not Eligible'
    };

    $monthlyBenefit = 0;
    if ($result['passed']) {
        $benefitTier = match ($tier) {
            'Full Assistance' => 'full',
            'Standard Assistance' => 'standard',
            'Limited Assistance' => 'limited',
            default => null
        };
        if ($benefitTier) {
            $monthlyBenefit = calculateBenefit($applicant['family_size'], $benefitTier);
        }
    }

    $qualificationResults[] = [
        'name' => $applicant['name'],
        'family_size' => $applicant['family_size'],
        'score' => $result['score'],
        'tier' => $tier,
        'approved' => $result['passed'],
        'monthly_benefit' => $monthlyBenefit,
    ];

    echo "\n📊 QUALIFICATION RESULT:\n";
    echo "   Score: {$result['score']}%\n";
    echo '   Status: '.($result['passed'] ? '✅ APPROVED' : '❌ NOT ELIGIBLE')."\n";
    echo "   Tier: {$tier}\n";

    if ($result['passed']) {
        echo "   Monthly Benefit: \${$monthlyBenefit}\n";
        echo '   Annual Total: $'.number_format($monthlyBenefit * 12)."\n";
    }

    echo "\n".str_repeat('=', 72)."\n\n";
}

// ============================================================================
// STEP 4: Program Statistics
// ============================================================================

echo "📊 PROGRAM STATISTICS\n";
echo str_repeat('-', 72)."\n\n";

printf("%-20s | %-8s | %-10s | %-20s | %-12s\n",
    'Applicant', 'Family', 'Score', 'Tier', 'Monthly Aid');
echo str_repeat('-', 72)."\n";

$totalApproved = 0;
$totalBenefits = 0;
$totalPeople = 0;

foreach ($qualificationResults as $result) {
    printf("%-20s | %6d | %7.1f%% | %-20s | %s\n",
        $result['name'],
        $result['family_size'],
        $result['score'],
        $result['tier'],
        $result['approved'] ? '$'.number_format($result['monthly_benefit']) : 'N/A'
    );

    if ($result['approved']) {
        $totalApproved++;
        $totalBenefits += $result['monthly_benefit'];
        $totalPeople += $result['family_size'];
    }
}

echo str_repeat('-', 72)."\n";
echo "Approved Applications: {$totalApproved}/".count($qualificationResults)."\n";
echo "Total People Served: {$totalPeople}\n";
echo 'Monthly Program Cost: $'.number_format($totalBenefits)."\n";
echo 'Annual Program Cost: $'.number_format($totalBenefits * 12)."\n\n";

// ============================================================================
// STEP 5: Laravel Integration with Audit Logging
// ============================================================================

echo "💡 LARAVEL INTEGRATION WITH COMPREHENSIVE AUDIT:\n";
echo str_repeat('-', 72)."\n";
echo <<<'LARAVEL'

// App/Models/AidApplication.php
use CleaniqueCoders\Eligify\Concerns\HasEligibility;

class AidApplication extends Model
{
    use HasEligibility;

    protected $fillable = [
        'applicant_id', 'family_size', 'monthly_income', 'income_to_fpl_ratio',
        'liquid_assets', 'total_assets', 'dependent_children_count',
        'has_elderly_members', 'has_disabled_members', 'is_unemployed',
        'months_unemployed', 'is_veteran', 'is_homeless',
        'qualification_score', 'aid_tier', 'status', 'monthly_benefit'
    ];

    protected $casts = [
        'has_elderly_members' => 'boolean',
        'has_disabled_members' => 'boolean',
        'is_unemployed' => 'boolean',
        'is_veteran' => 'boolean',
        'is_homeless' => 'boolean',
    ];

    public function getEligibilityData(): array
    {
        return [
            'family_size' => $this->family_size,
            'monthly_income' => $this->monthly_income,
            'income_to_fpl_ratio' => $this->income_to_fpl_ratio,
            'liquid_assets' => $this->liquid_assets,
            'total_assets' => $this->total_assets,
            'dependent_children_count' => $this->dependent_children_count,
            'has_elderly_members' => $this->has_elderly_members,
            'has_disabled_members' => $this->has_disabled_members,
            'is_unemployed' => $this->is_unemployed,
            'months_unemployed' => $this->months_unemployed,
            'is_veteran' => $this->is_veteran,
            'is_homeless' => $this->is_homeless,
        ];
    }

    // Calculate benefit amount
    public function calculateBenefit(string $tier): int
    {
        $baseAmounts = [
            'full' => [1 => 800, 2 => 1200, 3 => 1600, 4 => 2000, 5 => 2400, 6 => 2800],
            'standard' => [1 => 500, 2 => 800, 3 => 1100, 4 => 1400, 5 => 1700, 6 => 2000],
            'limited' => [1 => 250, 2 => 400, 3 => 550, 4 => 700, 5 => 850, 6 => 1000],
        ];

        $size = min($this->family_size, 6);
        return $baseAmounts[$tier][$size] ?? 0;
    }
}

// App/Http/Controllers/AidApplicationController.php
use CleaniqueCoders\Eligify\Audit\AuditLogger;

class AidApplicationController extends Controller
{
    protected AuditLogger $auditLogger;

    public function __construct(AuditLogger $auditLogger)
    {
        $this->auditLogger = $auditLogger;
    }

    public function process(AidApplication $application)
    {
        // Evaluate eligibility
        $result = $application->checkEligibility('social_assistance_program_2025');

        // Determine aid tier
        $tier = match(true) {
            $result['score'] >= 80 => 'full',
            $result['score'] >= 60 => 'standard',
            $result['score'] >= 50 => 'limited',
            default => null
        };

        // Calculate benefit
        $monthlyBenefit = $tier ? $application->calculateBenefit($tier) : 0;

        // Update application
        $application->update([
            'qualification_score' => $result['score'],
            'aid_tier' => $tier,
            'status' => $result['passed'] ? 'approved' : 'denied',
            'monthly_benefit' => $monthlyBenefit,
            'processed_at' => now(),
            'processed_by' => auth()->id(),
        ]);

        // Log decision for audit trail (CRITICAL for government programs)
        $this->auditLogger->log([
            'action' => 'aid_application_processed',
            'application_id' => $application->id,
            'applicant_id' => $application->applicant_id,
            'eligibility_score' => $result['score'],
            'decision' => $result['passed'] ? 'approved' : 'denied',
            'aid_tier' => $tier,
            'monthly_benefit' => $monthlyBenefit,
            'criteria_used' => 'social_assistance_program_2025',
            'processed_by' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Send notification
        if ($result['passed']) {
            Mail::to($application->applicant)->send(
                new AidApproved($application, $result)
            );

            // Create benefit payment record
            BenefitPayment::create([
                'application_id' => $application->id,
                'amount' => $monthlyBenefit,
                'start_date' => now()->startOfMonth()->addMonth(),
                'frequency' => 'monthly',
            ]);
        } else {
            Mail::to($application->applicant)->send(
                new AidDenied($application, $result)
            );
        }

        return redirect()
            ->route('applications.show', $application)
            ->with('success', 'Application processed successfully');
    }

    // Batch processing with progress tracking
    public function batchProcess()
    {
        $pending = AidApplication::where('status', 'pending')->get();
        $processed = [];

        foreach ($pending as $application) {
            $result = $application->checkEligibility('social_assistance_program_2025');

            $tier = match(true) {
                $result['score'] >= 80 => 'full',
                $result['score'] >= 60 => 'standard',
                $result['score'] >= 50 => 'limited',
                default => null
            };

            $monthlyBenefit = $tier ? $application->calculateBenefit($tier) : 0;

            $application->update([
                'qualification_score' => $result['score'],
                'aid_tier' => $tier,
                'status' => $result['passed'] ? 'approved' : 'denied',
                'monthly_benefit' => $monthlyBenefit,
                'processed_at' => now(),
                'processed_by' => auth()->id(),
            ]);

            // Audit log
            $this->auditLogger->log([
                'action' => 'batch_aid_application_processed',
                'application_id' => $application->id,
                'decision' => $result['passed'] ? 'approved' : 'denied',
                'aid_tier' => $tier,
            ]);

            $processed[] = [
                'id' => $application->id,
                'approved' => $result['passed'],
                'tier' => $tier,
                'benefit' => $monthlyBenefit,
            ];
        }

        return view('applications.batch-results', compact('processed'));
    }

    // Generate audit report
    public function auditReport(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $applications = AidApplication::whereBetween('processed_at', [$startDate, $endDate])
            ->with('applicant', 'processor')
            ->get();

        return view('applications.audit-report', [
            'applications' => $applications,
            'total_approved' => $applications->where('status', 'approved')->count(),
            'total_denied' => $applications->where('status', 'denied')->count(),
            'total_benefits' => $applications->sum('monthly_benefit'),
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }
}

LARAVEL;

echo "\n".str_repeat('=', 72)."\n";
echo "Example completed! Check qualification results above.\n";
echo str_repeat('=', 72)."\n";
