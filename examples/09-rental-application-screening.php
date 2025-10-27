<?php

/**
 * Example 09: Rental Application Screening System
 *
 * Use Case: Property management company needs to screen rental applicants
 * using income verification, credit checks, rental history, and background
 * checks with workflow automation for async callbacks.
 *
 * Features Demonstrated:
 * - Workflow manager with async callbacks
 * - Event listeners integration
 * - Multi-step verification process
 * - Income-to-rent ratio calculations
 * - Background check integration points
 *
 * Business Logic:
 * - Income must be 3x monthly rent
 * - Credit score minimums
 * - Rental history verification
 * - Criminal background check
 * - Eviction history check
 * - Employment verification
 *
 * Approval Tiers:
 * - Excellent (85-100%): Instant approval, minimal deposit
 * - Good (70-84%): Standard approval, normal deposit
 * - Fair (60-69%): Conditional approval, higher deposit
 * - Review (<60%): Manual review required
 */

require_once __DIR__.'/bootstrap.php';

use CleaniqueCoders\Eligify\Facades\Eligify;

echo '='.str_repeat('=', 70)."\n";
echo "  RENTAL APPLICATION SCREENING SYSTEM\n";
echo '='.str_repeat('=', 70)."\n\n";

// ============================================================================
// STEP 1: Define Rental Screening Criteria
// ============================================================================

echo "📋 Setting up rental screening criteria...\n\n";

// Example property: $2,000/month rent
$monthlyRent = 2000;
$requiredIncome = $monthlyRent * 3; // 3x rule

$criteria = Eligify::criteria('rental_application_screening_2025')
    ->description('Standard Rental Application Screening Process')

    // INCOME VERIFICATION (35% weight)
    ->addRule('monthly_income', '>=', $requiredIncome, 25)
    ->addRule('employment_verified', '==', true, 10)

    // CREDIT CHECK (30% weight)
    ->addRule('credit_score', '>=', 600, 20)
    ->addRule('has_recent_bankruptcy', '==', false, 10)

    // RENTAL HISTORY (20% weight)
    ->addRule('years_rental_history', '>=', 1, 10)
    ->addRule('previous_landlord_rating', '>=', 7, 5)  // 1-10 scale
    ->addRule('has_eviction_history', '==', false, 5)

    // BACKGROUND CHECK (15% weight)
    ->addRule('criminal_background_clear', '==', true, 10)
    ->addRule('identity_verified', '==', true, 5)

    ->passThreshold(60)

    // Workflow callbacks with actions
    ->onScoreRange(85, 100, function ($applicant, $result) use ($monthlyRent) {
        $deposit = $monthlyRent * 1.0; // One month deposit
        echo "\n✅ EXCELLENT - INSTANT APPROVAL\n";
        echo "   Applicant: {$applicant['name']}\n";
        echo "   Score: {$result['score']}%\n";
        echo "   \n";
        echo "   📋 APPROVAL DETAILS:\n";
        echo "      • Status: Instantly Approved\n";
        echo '      • Monthly Rent: $'.number_format($monthlyRent)."\n";
        echo '      • Security Deposit: $'.number_format($deposit)." (1 month)\n";
        echo '      • Move-in Cost: $'.number_format($monthlyRent + $deposit)."\n";
        echo "   \n";
        echo "   🎉 BENEFITS:\n";
        echo "      • No co-signer required\n";
        echo "      • Fast-track processing\n";
        echo "      • Flexible move-in date\n";
        echo "   \n";
        echo "   → Lease ready to sign!\n";

        // Async workflow action
        echo "   [WORKFLOW] Generating lease agreement...\n";
        echo "   [WORKFLOW] Scheduling property walk-through...\n";
        echo "   [WORKFLOW] Sending welcome package...\n";
    })

    ->onScoreRange(70, 84, function ($applicant, $result) use ($monthlyRent) {
        $deposit = $monthlyRent * 1.5; // 1.5 month deposit
        echo "\n✅ GOOD - STANDARD APPROVAL\n";
        echo "   Applicant: {$applicant['name']}\n";
        echo "   Score: {$result['score']}%\n";
        echo "   \n";
        echo "   📋 APPROVAL DETAILS:\n";
        echo "      • Status: Approved\n";
        echo '      • Monthly Rent: $'.number_format($monthlyRent)."\n";
        echo '      • Security Deposit: $'.number_format($deposit)." (1.5 months)\n";
        echo '      • Move-in Cost: $'.number_format($monthlyRent + $deposit)."\n";
        echo "   \n";
        echo "   📝 REQUIREMENTS:\n";
        echo "      • Standard lease terms\n";
        echo "      • Normal processing time (3-5 days)\n";
        echo "   \n";
        echo "   → Lease documents will be emailed within 24 hours.\n";

        // Async workflow action
        echo "   [WORKFLOW] Preparing lease documents...\n";
        echo "   [WORKFLOW] Scheduling reference checks...\n";
    })

    ->onScoreRange(60, 69, function ($applicant, $result) use ($monthlyRent) {
        $deposit = $monthlyRent * 2.0; // Two month deposit
        echo "\n⚠️  FAIR - CONDITIONAL APPROVAL\n";
        echo "   Applicant: {$applicant['name']}\n";
        echo "   Score: {$result['score']}%\n";
        echo "   \n";
        echo "   📋 CONDITIONAL TERMS:\n";
        echo "      • Status: Approved with conditions\n";
        echo '      • Monthly Rent: $'.number_format($monthlyRent)."\n";
        echo '      • Security Deposit: $'.number_format($deposit)." (2 months)\n";
        echo '      • Move-in Cost: $'.number_format($monthlyRent + $deposit)."\n";
        echo "   \n";
        echo "   ⚠️  ADDITIONAL REQUIREMENTS:\n";
        echo "      • Co-signer may be required\n";
        echo "      • Additional documentation needed\n";
        echo "      • Monthly income verification\n";
        echo "      • Longer processing time (5-7 days)\n";
        echo "   \n";
        echo "   → Property manager will contact you.\n";

        // Async workflow action
        echo "   [WORKFLOW] Flagging for manager review...\n";
        echo "   [WORKFLOW] Requesting additional documentation...\n";
    })

    ->onFail(function ($applicant, $result) {
        echo "\n❌ REQUIRES MANUAL REVIEW\n";
        echo "   Applicant: {$applicant['name']}\n";
        echo "   Score: {$result['score']}%\n";
        echo "   \n";
        echo "   → Application does not meet automatic approval criteria.\n";
        echo "   → Manual review process initiated.\n";
        echo "   \n";
        echo "   📝 CONCERNS IDENTIFIED:\n";

        $concerns = [];
        if (! empty($result['failed_rules'])) {
            foreach (array_slice($result['failed_rules'], 0, 4) as $rule) {
                $messages = [
                    'monthly_income' => 'Income below 3x rent requirement',
                    'credit_score' => 'Credit score below minimum',
                    'employment_verified' => 'Employment verification needed',
                    'has_eviction_history' => 'Previous eviction on record',
                    'criminal_background_clear' => 'Background check concerns',
                    'years_rental_history' => 'Limited rental history',
                ];
                $concerns[] = $messages[$rule['field']] ?? $rule['field'];
            }
        }

        foreach ($concerns as $concern) {
            echo "      • {$concern}\n";
        }

        echo "   \n";
        echo "   💡 OPTIONS:\n";
        echo "      • Provide a qualified co-signer\n";
        echo "      • Offer larger security deposit\n";
        echo "      • Provide additional references\n";
        echo "      • Consider alternative properties\n";
        echo "   \n";
        echo "   → Decision within 7-10 business days.\n";

        // Async workflow action
        echo "   [WORKFLOW] Escalating to senior property manager...\n";
        echo "   [WORKFLOW] Scheduling applicant interview...\n";
    })

    ->save();

echo "✓ Rental screening criteria configured!\n";
echo '  - Property: $'.number_format($monthlyRent)."/month\n";
echo '  - Required Income: $'.number_format($requiredIncome)."/month\n\n";

// ============================================================================
// STEP 2: Prepare Rental Applications
// ============================================================================

echo "📝 Processing rental applications...\n\n";

$applicants = [
    // CASE 1: Excellent applicant - Perfect profile
    [
        'name' => 'Emily Johnson',
        'monthly_income' => 8500,
        'employment_verified' => true,
        'credit_score' => 780,
        'has_recent_bankruptcy' => false,
        'years_rental_history' => 5,
        'previous_landlord_rating' => 10,
        'has_eviction_history' => false,
        'criminal_background_clear' => true,
        'identity_verified' => true,
    ],

    // CASE 2: Good applicant - Strong income
    [
        'name' => 'Marcus Williams',
        'monthly_income' => 7200,
        'employment_verified' => true,
        'credit_score' => 710,
        'has_recent_bankruptcy' => false,
        'years_rental_history' => 3,
        'previous_landlord_rating' => 8,
        'has_eviction_history' => false,
        'criminal_background_clear' => true,
        'identity_verified' => true,
    ],

    // CASE 3: Fair applicant - Marginal credit
    [
        'name' => 'Sarah Martinez',
        'monthly_income' => 6500,
        'employment_verified' => true,
        'credit_score' => 640,
        'has_recent_bankruptcy' => false,
        'years_rental_history' => 2,
        'previous_landlord_rating' => 7,
        'has_eviction_history' => false,
        'criminal_background_clear' => true,
        'identity_verified' => true,
    ],

    // CASE 4: Review required - Low income
    [
        'name' => 'David Chen',
        'monthly_income' => 5200,
        'employment_verified' => true,
        'credit_score' => 670,
        'has_recent_bankruptcy' => false,
        'years_rental_history' => 4,
        'previous_landlord_rating' => 8,
        'has_eviction_history' => false,
        'criminal_background_clear' => true,
        'identity_verified' => true,
    ],

    // CASE 5: Review required - Multiple issues
    [
        'name' => 'Jennifer Taylor',
        'monthly_income' => 4800,
        'employment_verified' => false,
        'credit_score' => 580,
        'has_recent_bankruptcy' => true,
        'years_rental_history' => 1,
        'previous_landlord_rating' => 5,
        'has_eviction_history' => true,
        'criminal_background_clear' => false,
        'identity_verified' => true,
    ],
];

// ============================================================================
// STEP 3: Evaluate Applications
// ============================================================================

echo "🔍 Screening rental applications...\n";
echo str_repeat('-', 72)."\n\n";

$screeningResults = [];
$eligify = app(\CleaniqueCoders\Eligify\Eligify::class);

foreach ($applicants as $index => $applicant) {
    echo 'APPLICATION '.($index + 1).": {$applicant['name']}\n";
    echo str_repeat('-', 72)."\n";
    echo 'Monthly Income: $'.number_format($applicant['monthly_income'])."\n";
    echo 'Income-to-Rent Ratio: '.round($applicant['monthly_income'] / $monthlyRent, 1)."x\n";
    echo "Credit Score: {$applicant['credit_score']}\n";
    echo 'Employment: '.($applicant['employment_verified'] ? 'Verified' : 'Not Verified')."\n";
    echo "Rental History: {$applicant['years_rental_history']} years\n";
    echo "Landlord Rating: {$applicant['previous_landlord_rating']}/10\n";

    $flags = [];
    if ($applicant['has_recent_bankruptcy']) {
        $flags[] = 'Bankruptcy';
    }
    if ($applicant['has_eviction_history']) {
        $flags[] = 'Eviction History';
    }
    if (! $applicant['criminal_background_clear']) {
        $flags[] = 'Background Check';
    }

    if (! empty($flags)) {
        echo '⚠️  Red Flags: '.implode(', ', $flags)."\n";
    }

    // Evaluate with callbacks
    $result = $eligify->evaluateWithCallbacks($criteria, $applicant);

    // Determine approval tier
    $tier = match (true) {
        $result['score'] >= 85 => 'Excellent',
        $result['score'] >= 70 => 'Good',
        $result['score'] >= 60 => 'Fair',
        default => 'Review Required'
    };

    $depositMultiplier = match ($tier) {
        'Excellent' => 1.0,
        'Good' => 1.5,
        'Fair' => 2.0,
        default => 0
    };

    $deposit = $depositMultiplier > 0 ? $monthlyRent * $depositMultiplier : 0;

    $screeningResults[] = [
        'name' => $applicant['name'],
        'income' => $applicant['monthly_income'],
        'score' => $result['score'],
        'tier' => $tier,
        'approved' => $result['passed'],
        'deposit' => $deposit,
    ];

    echo "\n📊 SCREENING RESULT:\n";
    echo "   Score: {$result['score']}%\n";
    echo "   Tier: {$tier}\n";
    echo '   Status: '.($result['passed'] ? '✅ APPROVED' : '⚠️  MANUAL REVIEW')."\n";

    if ($result['passed']) {
        echo '   Security Deposit: $'.number_format($deposit)."\n";
        echo '   Total Move-in: $'.number_format($monthlyRent + $deposit)."\n";
    }

    echo "\n".str_repeat('=', 72)."\n\n";
}

// ============================================================================
// STEP 4: Screening Summary
// ============================================================================

echo "📊 SCREENING SUMMARY\n";
echo str_repeat('-', 72)."\n\n";

printf("%-20s | %-10s | %-10s | %-15s | %-12s\n",
    'Applicant', 'Income', 'Score', 'Tier', 'Deposit');
echo str_repeat('-', 72)."\n";

$autoApproved = 0;
$manualReview = 0;

foreach ($screeningResults as $result) {
    printf("%-20s | $%8s | %7.1f%% | %-15s | %s\n",
        $result['name'],
        number_format($result['income']),
        $result['score'],
        $result['tier'],
        $result['approved'] ? '$'.number_format($result['deposit']) : 'N/A'
    );

    if ($result['approved']) {
        $autoApproved++;
    } else {
        $manualReview++;
    }
}

echo str_repeat('-', 72)."\n";
echo "Auto-Approved: {$autoApproved}\n";
echo "Manual Review: {$manualReview}\n";
echo 'Auto-Approval Rate: '.round(($autoApproved / count($screeningResults)) * 100, 1)."%\n\n";

// ============================================================================
// STEP 5: Laravel Integration with Workflow
// ============================================================================

echo "💡 LARAVEL INTEGRATION WITH WORKFLOW MANAGER:\n";
echo str_repeat('-', 72)."\n";
echo <<<'LARAVEL'

// App/Models/RentalApplication.php
use CleaniqueCoders\Eligify\Concerns\HasEligibility;

class RentalApplication extends Model
{
    use HasEligibility;

    protected $fillable = [
        'property_id', 'applicant_id', 'monthly_income', 'employment_verified',
        'credit_score', 'has_recent_bankruptcy', 'years_rental_history',
        'previous_landlord_rating', 'has_eviction_history',
        'criminal_background_clear', 'identity_verified',
        'screening_score', 'approval_tier', 'status', 'security_deposit'
    ];

    public function getEligibilityData(): array
    {
        return [
            'monthly_income' => $this->monthly_income,
            'employment_verified' => $this->employment_verified,
            'credit_score' => $this->credit_score,
            'has_recent_bankruptcy' => $this->has_recent_bankruptcy,
            'years_rental_history' => $this->years_rental_history,
            'previous_landlord_rating' => $this->previous_landlord_rating,
            'has_eviction_history' => $this->has_eviction_history,
            'criminal_background_clear' => $this->criminal_background_clear,
            'identity_verified' => $this->identity_verified,
        ];
    }
}

// App/Http/Controllers/RentalApplicationController.php
use CleaniqueCoders\Eligify\Workflow\WorkflowManager;

class RentalApplicationController extends Controller
{
    public function screen(RentalApplication $application)
    {
        // Run eligibility check with workflow
        $result = $application->checkEligibility('rental_application_screening_2025');

        // Determine tier
        $tier = match(true) {
            $result['score'] >= 85 => 'excellent',
            $result['score'] >= 70 => 'good',
            $result['score'] >= 60 => 'fair',
            default => 'review'
        };

        $property = $application->property;
        $depositMultiplier = match($tier) {
            'excellent' => 1.0,
            'good' => 1.5,
            'fair' => 2.0,
            default => 0
        };

        $deposit = $depositMultiplier > 0 ? $property->monthly_rent * $depositMultiplier : 0;

        // Update application
        $application->update([
            'screening_score' => $result['score'],
            'approval_tier' => $tier,
            'status' => $result['passed'] ? 'approved' : 'review',
            'security_deposit' => $deposit,
            'screened_at' => now(),
        ]);

        // Execute workflow based on tier
        $this->executeWorkflow($application, $tier, $result);

        return redirect()
            ->route('applications.show', $application)
            ->with('success', 'Application screened successfully');
    }

    protected function executeWorkflow(RentalApplication $application, string $tier, array $result)
    {
        $workflow = new WorkflowManager();

        match($tier) {
            'excellent' => $workflow
                ->addStep(fn() => $this->generateLease($application))
                ->addStep(fn() => $this->scheduleWalkthrough($application))
                ->addStep(fn() => $this->sendWelcomePackage($application))
                ->execute(),

            'good' => $workflow
                ->addStep(fn() => $this->prepareLease($application))
                ->addStep(fn() => $this->verifyReferences($application))
                ->addStep(fn() => $this->notifyApproval($application))
                ->execute(),

            'fair' => $workflow
                ->addStep(fn() => $this->requestAdditionalDocs($application))
                ->addStep(fn() => $this->flagForReview($application))
                ->addStep(fn() => $this->notifyConditional($application))
                ->execute(),

            default => $workflow
                ->addStep(fn() => $this->escalateToManager($application))
                ->addStep(fn() => $this->scheduleInterview($application))
                ->addStep(fn() => $this->notifyManualReview($application))
                ->execute()
        };
    }

    // Workflow step methods
    protected function generateLease(RentalApplication $application)
    {
        GenerateLeaseDocument::dispatch($application);
    }

    protected function scheduleWalkthrough(RentalApplication $application)
    {
        PropertyWalkthrough::create([
            'application_id' => $application->id,
            'scheduled_at' => now()->addDays(2),
        ]);
    }

    protected function sendWelcomePackage(RentalApplication $application)
    {
        Mail::to($application->applicant)->send(
            new WelcomeNewTenant($application)
        );
    }

    protected function flagForReview(RentalApplication $application)
    {
        $application->update(['requires_manager_review' => true]);

        // Notify property manager
        $application->property->manager->notify(
            new ApplicationNeedsReview($application)
        );
    }
}

// App/Events/ApplicationScreened.php
use CleaniqueCoders\Eligify\Events\EvaluationCompleted;

class ApplicationScreened
{
    public function handle(EvaluationCompleted $event)
    {
        // Log the screening
        Log::info('Rental application screened', [
            'criteria' => $event->criteria->name,
            'score' => $event->result['score'],
            'passed' => $event->result['passed'],
        ]);

        // Track in analytics
        Analytics::track('application_screened', [
            'score' => $event->result['score'],
            'tier' => $this->determineTier($event->result['score']),
        ]);
    }

    protected function determineTier(float $score): string
    {
        return match(true) {
            $score >= 85 => 'excellent',
            $score >= 70 => 'good',
            $score >= 60 => 'fair',
            default => 'review'
        };
    }
}

LARAVEL;

echo "\n".str_repeat('=', 72)."\n";
echo "Example completed! Check rental screening results above.\n";
echo str_repeat('=', 72)."\n";
