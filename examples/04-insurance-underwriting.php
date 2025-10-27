<?php

/**
 * Example 04: Insurance Underwriting System
 *
 * Use Case: Insurance company needs to automate health insurance application
 * underwriting based on age, health conditions, lifestyle factors, and
 * calculate appropriate premium adjustments.
 *
 * Features Demonstrated:
 * - Complex nested conditional logic
 * - Group combinations with different logic types (AND/OR)
 * - Risk assessment scoring
 * - Dynamic premium calculation
 * - Medical history evaluation
 *
 * Business Logic:
 * - Age-based baseline premiums
 * - Pre-existing conditions risk assessment
 * - Lifestyle factors (smoking, BMI, exercise)
 * - Family medical history considerations
 * - Coverage tier determination
 *
 * Risk Categories:
 * - Low Risk (80-100%): Standard premium
 * - Moderate Risk (60-79%): +25% premium
 * - High Risk (40-59%): +50% premium or Special underwriting
 * - Very High Risk (<40%): Declined or Alternative plan
 */

require_once __DIR__.'/bootstrap.php';

use CleaniqueCoders\Eligify\Facades\Eligify;

echo '='.str_repeat('=', 70)."\n";
echo "  HEALTH INSURANCE UNDERWRITING SYSTEM\n";
echo '='.str_repeat('=', 70)."\n\n";

// ============================================================================
// STEP 1: Define Underwriting Criteria
// ============================================================================

echo "📋 Setting up insurance underwriting criteria...\n\n";

$criteria = Eligify::criteria('health_insurance_underwriting_2025')
    ->description('Standard Health Insurance Policy - Individual Coverage')

    // AGE REQUIREMENTS (Ages 18-70 preferred)
    ->addRule('age', '>=', 18, 15)
    ->addRule('age', '<=', 70, 10)

    // HEALTH STATUS - Major conditions
    ->addRule('has_heart_disease', '==', false, 20)
    ->addRule('has_cancer_history', '==', false, 20)
    ->addRule('has_diabetes', '==', false, 15)

    // LIFESTYLE FACTORS
    ->addRule('is_smoker', '==', false, 15)  // Non-smoker preferred
    ->addRule('bmi', '>=', 18.5, 5)          // Not underweight
    ->addRule('bmi', '<=', 30, 10)           // Not obese
    ->addRule('exercise_per_week', '>=', 2, 5)  // Active lifestyle

    // OCCUPATION RISK
    ->addRule('has_hazardous_occupation', '==', false, 10)

    // FINANCIAL STABILITY
    ->addRule('monthly_income', '>=', 3000, 5)

    ->passThreshold(50)  // Minimum 50% to be approved

    // Risk-based callbacks
    ->onScoreRange(80, 100, function ($applicant, $result) {
        $premium = calculatePremium($applicant['age'], 1.0);
        echo "\n✅ APPROVED - LOW RISK\n";
        echo "   Name: {$applicant['name']}\n";
        echo "   Risk Category: LOW\n";
        echo "   Premium: \${$premium}/month (Standard Rate)\n";
        echo "   Coverage: $500,000\n";
        echo "   → Welcome! You qualify for our best rates.\n";
    })

    ->onScoreRange(60, 79, function ($applicant, $result) {
        $premium = calculatePremium($applicant['age'], 1.25);
        echo "\n⚠️  APPROVED - MODERATE RISK\n";
        echo "   Name: {$applicant['name']}\n";
        echo "   Risk Category: MODERATE\n";
        echo "   Premium: \${$premium}/month (+25% adjustment)\n";
        echo "   Coverage: $500,000\n";
        echo "   → Approved with adjusted premium.\n";
    })

    ->onScoreRange(50, 59, function ($applicant, $result) {
        $premium = calculatePremium($applicant['age'], 1.50);
        echo "\n🔍 APPROVED - HIGH RISK (SPECIAL UNDERWRITING)\n";
        echo "   Name: {$applicant['name']}\n";
        echo "   Risk Category: HIGH\n";
        echo "   Premium: \${$premium}/month (+50% adjustment)\n";
        echo "   Coverage: $250,000 (Reduced)\n";
        echo "   → Requires medical examination and additional documentation.\n";
    })

    ->onFail(function ($applicant, $result) {
        echo "\n❌ APPLICATION DECLINED\n";
        echo "   Name: {$applicant['name']}\n";
        echo "   Risk Score: {$result['score']}%\n";
        echo "   → Unfortunately, we cannot offer standard coverage at this time.\n";
        echo "   → Consider: High-risk pool or alternative plans.\n";
        echo "   → Primary concerns:\n";

        if (! empty($result['failed_rules'])) {
            foreach (array_slice($result['failed_rules'], 0, 3) as $ruleResult) {
                if (isset($ruleResult['rule'])) {
                    $rule = $ruleResult['rule'];
                    $field = $rule->field ?? $rule->getAttribute('field');

                    $concerns = [
                        'age' => 'Age outside acceptable range',
                        'has_heart_disease' => 'Pre-existing heart condition',
                        'has_cancer_history' => 'Cancer history',
                        'has_diabetes' => 'Diabetes diagnosis',
                        'is_smoker' => 'Tobacco use',
                        'bmi' => 'BMI outside healthy range',
                    ];
                    echo '      • '.($concerns[$field] ?? $field)."\n";
                }
            }
        }
    })

    ->save();

// Helper function for premium calculation
function calculatePremium(int $age, float $riskMultiplier): int
{
    // Base premium by age group
    $basePremium = match (true) {
        $age < 30 => 200,
        $age < 40 => 300,
        $age < 50 => 450,
        $age < 60 => 650,
        default => 900
    };

    return (int) round($basePremium * $riskMultiplier);
}

echo "✓ Underwriting criteria configured!\n";
echo "  - Coverage: Health Insurance\n";
echo "  - Minimum Risk Score: 50%\n\n";

// ============================================================================
// STEP 2: Prepare Insurance Applications
// ============================================================================

echo "📝 Processing insurance applications...\n\n";

$applicants = [
    // CASE 1: Perfect candidate - Low risk
    [
        'name' => 'Emily Chen',
        'age' => 32,
        'has_heart_disease' => false,
        'has_cancer_history' => false,
        'has_diabetes' => false,
        'is_smoker' => false,
        'bmi' => 22.5,
        'exercise_per_week' => 4,
        'has_hazardous_occupation' => false,
        'monthly_income' => 7500,
    ],

    // CASE 2: Good candidate with minor lifestyle concerns - Moderate risk
    [
        'name' => 'Robert Martinez',
        'age' => 45,
        'has_heart_disease' => false,
        'has_cancer_history' => false,
        'has_diabetes' => false,
        'is_smoker' => false,
        'bmi' => 28.5,  // Overweight but not obese
        'exercise_per_week' => 1,
        'has_hazardous_occupation' => false,
        'monthly_income' => 5500,
    ],

    // CASE 3: Manageable diabetes with good lifestyle - Moderate risk
    [
        'name' => 'Sarah Johnson',
        'age' => 52,
        'has_heart_disease' => false,
        'has_cancer_history' => false,
        'has_diabetes' => true,  // Pre-existing condition
        'is_smoker' => false,
        'bmi' => 25.0,
        'exercise_per_week' => 3,
        'has_hazardous_occupation' => false,
        'monthly_income' => 6000,
    ],

    // CASE 4: Smoker with borderline health - High risk
    [
        'name' => 'James Wilson',
        'age' => 58,
        'has_heart_disease' => false,
        'has_cancer_history' => false,
        'has_diabetes' => false,
        'is_smoker' => true,  // Major risk factor
        'bmi' => 31.0,  // Obese
        'exercise_per_week' => 0,
        'has_hazardous_occupation' => true,
        'monthly_income' => 4500,
    ],

    // CASE 5: Multiple serious conditions - Declined
    [
        'name' => 'Patricia Anderson',
        'age' => 67,
        'has_heart_disease' => true,  // Major condition
        'has_cancer_history' => true,  // Major condition
        'has_diabetes' => true,  // Major condition
        'is_smoker' => true,
        'bmi' => 33.0,
        'exercise_per_week' => 0,
        'has_hazardous_occupation' => false,
        'monthly_income' => 3500,
    ],
];

// ============================================================================
// STEP 3: Evaluate Each Application
// ============================================================================

echo "🔍 Underwriting applications...\n";
echo str_repeat('-', 72)."\n\n";

$underwritingResults = [];
$eligify = app(\CleaniqueCoders\Eligify\Eligify::class);

foreach ($applicants as $index => $applicant) {
    echo 'APPLICATION '.($index + 1).": {$applicant['name']}\n";
    echo str_repeat('-', 72)."\n";
    echo "Age: {$applicant['age']} years\n";
    echo "Health Status:\n";
    echo '  - Heart Disease: '.($applicant['has_heart_disease'] ? 'Yes' : 'No')."\n";
    echo '  - Cancer History: '.($applicant['has_cancer_history'] ? 'Yes' : 'No')."\n";
    echo '  - Diabetes: '.($applicant['has_diabetes'] ? 'Yes' : 'No')."\n";
    echo "Lifestyle:\n";
    echo '  - Smoker: '.($applicant['is_smoker'] ? 'Yes' : 'No')."\n";
    echo "  - BMI: {$applicant['bmi']}\n";
    echo "  - Exercise: {$applicant['exercise_per_week']} times/week\n";
    echo 'Income: $'.number_format($applicant['monthly_income'])."/month\n";

    // Evaluate with callbacks
    $result = $eligify->evaluateWithCallbacks($criteria, $applicant);

    // Determine risk category
    $riskCategory = match (true) {
        $result['score'] >= 80 => 'LOW',
        $result['score'] >= 60 => 'MODERATE',
        $result['score'] >= 50 => 'HIGH',
        default => 'DECLINED'
    };

    $premium = $result['passed'] ? calculatePremium(
        $applicant['age'],
        match ($riskCategory) {
            'LOW' => 1.0,
            'MODERATE' => 1.25,
            'HIGH' => 1.50,
            default => 0
        }
    ) : 0;

    $underwritingResults[] = [
        'name' => $applicant['name'],
        'age' => $applicant['age'],
        'score' => $result['score'],
        'risk_category' => $riskCategory,
        'approved' => $result['passed'],
        'premium' => $premium,
    ];

    echo "\n📊 UNDERWRITING DECISION:\n";
    echo "   Risk Score: {$result['score']}%\n";
    echo "   Risk Category: {$riskCategory}\n";
    echo '   Status: '.($result['passed'] ? '✅ APPROVED' : '❌ DECLINED')."\n";

    if ($result['passed']) {
        echo "   Monthly Premium: \${$premium}\n";
        echo '   Annual Cost: $'.number_format($premium * 12)."\n";
    }

    echo "\n".str_repeat('=', 72)."\n\n";
}

// ============================================================================
// STEP 4: Underwriting Summary
// ============================================================================

echo "📊 UNDERWRITING SUMMARY\n";
echo str_repeat('-', 72)."\n\n";

printf("%-20s | %-5s | %-10s | %-12s | %-10s\n",
    'Applicant', 'Age', 'Score', 'Risk', 'Premium');
echo str_repeat('-', 72)."\n";

$approvedCount = 0;
$totalPremiums = 0;

foreach ($underwritingResults as $result) {
    printf("%-20s | %3d | %7.1f%% | %-12s | %s\n",
        $result['name'],
        $result['age'],
        $result['score'],
        $result['risk_category'],
        $result['approved'] ? '$'.$result['premium'].'/mo' : 'DECLINED'
    );

    if ($result['approved']) {
        $approvedCount++;
        $totalPremiums += $result['premium'];
    }
}

echo str_repeat('-', 72)."\n";
echo "Approved: {$approvedCount}/".count($underwritingResults)."\n";
echo 'Approval Rate: '.round(($approvedCount / count($underwritingResults)) * 100, 1)."%\n";
echo 'Average Premium: $'.($approvedCount > 0 ? round($totalPremiums / $approvedCount) : 0)."/month\n\n";

// ============================================================================
// STEP 5: Laravel Integration Example
// ============================================================================

echo "💡 LARAVEL INTEGRATION FOR INSURANCE UNDERWRITING:\n";
echo str_repeat('-', 72)."\n";
echo <<<'LARAVEL'

// App/Models/InsuranceApplication.php
use CleaniqueCoders\Eligify\Concerns\HasEligibility;

class InsuranceApplication extends Model
{
    use HasEligibility;

    protected $fillable = [
        'applicant_id', 'age', 'has_heart_disease', 'has_cancer_history',
        'has_diabetes', 'is_smoker', 'bmi', 'exercise_per_week',
        'has_hazardous_occupation', 'monthly_income', 'risk_score',
        'risk_category', 'status', 'monthly_premium'
    ];

    public function getEligibilityData(): array
    {
        return [
            'age' => $this->age,
            'has_heart_disease' => $this->has_heart_disease,
            'has_cancer_history' => $this->has_cancer_history,
            'has_diabetes' => $this->has_diabetes,
            'is_smoker' => $this->is_smoker,
            'bmi' => $this->bmi,
            'exercise_per_week' => $this->exercise_per_week,
            'has_hazardous_occupation' => $this->has_hazardous_occupation,
            'monthly_income' => $this->monthly_income,
        ];
    }

    // Calculate premium based on risk
    public function calculatePremium(): int
    {
        $riskMultiplier = match($this->risk_category) {
            'LOW' => 1.0,
            'MODERATE' => 1.25,
            'HIGH' => 1.50,
            default => 0
        };

        $basePremium = match(true) {
            $this->age < 30 => 200,
            $this->age < 40 => 300,
            $this->age < 50 => 450,
            $this->age < 60 => 650,
            default => 900
        };

        return (int) round($basePremium * $riskMultiplier);
    }
}

// App/Http/Controllers/UnderwritingController.php
class UnderwritingController extends Controller
{
    public function processApplication(InsuranceApplication $application)
    {
        // Run eligibility check
        $result = $application->checkEligibility('health_insurance_underwriting_2025');

        // Determine risk category
        $riskCategory = match(true) {
            $result['score'] >= 80 => 'LOW',
            $result['score'] >= 60 => 'MODERATE',
            $result['score'] >= 50 => 'HIGH',
            default => 'DECLINED'
        };

        // Update application
        $application->update([
            'risk_score' => $result['score'],
            'risk_category' => $riskCategory,
            'status' => $result['passed'] ? 'approved' : 'declined',
            'monthly_premium' => $result['passed'] ? $application->calculatePremium() : null,
            'underwritten_at' => now(),
        ]);

        // Send notification
        if ($result['passed']) {
            Mail::to($application->applicant)->send(
                new PolicyApproved($application, $result)
            );

            // Generate policy document
            GeneratePolicyDocument::dispatch($application);
        } else {
            Mail::to($application->applicant)->send(
                new ApplicationDeclined($application, $result)
            );
        }

        return redirect()
            ->route('applications.show', $application)
            ->with('success', 'Application processed successfully');
    }

    // Batch underwriting for pending applications
    public function batchUnderwrite()
    {
        $pending = InsuranceApplication::where('status', 'pending')->get();

        $results = $pending->map(function ($application) {
            $result = $application->checkEligibility('health_insurance_underwriting_2025');

            $riskCategory = match(true) {
                $result['score'] >= 80 => 'LOW',
                $result['score'] >= 60 => 'MODERATE',
                $result['score'] >= 50 => 'HIGH',
                default => 'DECLINED'
            };

            $application->update([
                'risk_score' => $result['score'],
                'risk_category' => $riskCategory,
                'status' => $result['passed'] ? 'approved' : 'declined',
                'monthly_premium' => $result['passed'] ? $application->calculatePremium() : null,
                'underwritten_at' => now(),
            ]);

            return [
                'application_id' => $application->id,
                'approved' => $result['passed'],
                'risk_category' => $riskCategory,
            ];
        });

        return view('underwriting.batch-results', compact('results'));
    }
}

LARAVEL;

echo "\n".str_repeat('=', 72)."\n";
echo "Example completed! Check underwriting results above.\n";
echo str_repeat('=', 72)."\n";
