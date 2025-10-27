<?php

/**
 * Example 01: Loan Approval System
 *
 * Use Case: Financial institution needs to automatically evaluate loan applications
 * based on multiple financial criteria including credit score, income, employment
 * status, and debt-to-income ratio.
 *
 * Features Demonstrated:
 * - Basic rule creation with weighted scoring
 * - Callback functions for approval/rejection workflows
 * - Audit logging for compliance
 * - Multiple evaluation scenarios
 *
 * Business Logic:
 * - Credit score must be >= 650 (heavily weighted)
 * - Annual income must be >= $30,000
 * - Must be employed (full-time or self-employed)
 * - Debt-to-income ratio must be <= 43%
 * - Active loans must be <= 3
 */

require_once __DIR__.'/bootstrap.php';

use CleaniqueCoders\Eligify\Facades\Eligify;

echo '='.str_repeat('=', 70)."\n";
echo "  LOAN APPROVAL SYSTEM EXAMPLE\n";
echo '='.str_repeat('=', 70)."\n\n";

// ============================================================================
// STEP 1: Define Loan Approval Criteria
// ============================================================================

echo "📋 Setting up loan approval criteria...\n\n";

$criteria = Eligify::criteria('personal_loan_approval')
    ->description('Standard personal loan approval criteria for amounts up to $50,000')

    // Credit Score (40% weight - most important factor)
    ->addRule('credit_score', '>=', 650, 40)

    // Annual Income (30% weight)
    ->addRule('annual_income', '>=', 30000, 30)

    // Employment Status (15% weight)
    ->addRule('employment_status', 'in', ['employed', 'self-employed'], 15)

    // Debt-to-Income Ratio (10% weight)
    ->addRule('debt_to_income_ratio', '<=', 43, 10)

    // Active Loans (5% weight)
    ->addRule('active_loans', '<=', 3, 5)

    // Set pass threshold to 80%
    ->passThreshold(80)

    // Define callbacks for automated workflows
    ->onPass(function ($applicant, $result) {
        echo "\n✅ LOAN APPROVED!\n";
        echo "   Applicant: {$applicant['name']}\n";
        echo "   Score: {$result['score']}%\n";
        echo "   → Sending approval email...\n";
        echo "   → Creating loan account...\n";
        echo "   → Scheduling disbursement...\n";
    })

    ->onFail(function ($applicant, $result) {
        echo "\n❌ LOAN DENIED\n";
        echo "   Applicant: {$applicant['name']}\n";
        echo "   Score: {$result['score']}%\n";
        echo "   → Sending rejection email with reasons...\n";

        if (! empty($result['failed_rules'])) {
            echo "   → Failed Requirements:\n";
            foreach ($result['failed_rules'] as $rule) {
                $ruleObj = $rule['rule'] ?? null;
                if ($ruleObj) {
                    $field = $ruleObj->getAttribute('field');
                    $operator = $ruleObj->getAttribute('operator');
                    $expected = $ruleObj->getAttribute('value');
                    $actual = $rule['field_value'] ?? 'N/A';

                    // Format the expected value nicely
                    $expectedStr = is_array($expected) ? implode(', ', $expected) : $expected;
                    $actualStr = is_array($actual) ? implode(', ', $actual) : $actual;

                    echo "      • {$field}: Expected {$operator} {$expectedStr}, got {$actualStr}\n";
                }
            }
        }
    })

    ->onExcellent(function ($applicant, $result) {
        echo "   ⭐ PREMIUM RATE ELIGIBLE (Score: {$result['score']}%)\n";
        echo "   → Offering 0.5% interest rate discount\n";
    })

    ->onGood(function ($applicant, $result) {
        echo "   ⭐ STANDARD RATE APPLICABLE (Score: {$result['score']}%)\n";
    })

    ->save();

echo "✓ Criteria configured successfully!\n";
echo "  - 5 rules defined\n";
echo "  - Pass threshold: 80%\n";
echo "  - Callbacks: onPass, onFail, onExcellent, onGood\n\n";

// ============================================================================
// STEP 2: Prepare Test Applicants
// ============================================================================

echo "👥 Preparing test applicants...\n\n";

$applicants = [
    // CASE 1: Excellent applicant - should pass with high score
    [
        'name' => 'John Smith',
        'credit_score' => 780,
        'annual_income' => 85000,
        'employment_status' => 'employed',
        'debt_to_income_ratio' => 25,
        'active_loans' => 1,
    ],

    // CASE 2: Good applicant - should pass
    [
        'name' => 'Sarah Johnson',
        'credit_score' => 680,
        'annual_income' => 45000,
        'employment_status' => 'self-employed',
        'debt_to_income_ratio' => 38,
        'active_loans' => 2,
    ],

    // CASE 3: Borderline applicant - might fail
    [
        'name' => 'Mike Davis',
        'credit_score' => 620,
        'annual_income' => 28000,
        'employment_status' => 'employed',
        'debt_to_income_ratio' => 45,
        'active_loans' => 4,
    ],

    // CASE 4: Poor applicant - should fail
    [
        'name' => 'Lisa Anderson',
        'credit_score' => 580,
        'annual_income' => 22000,
        'employment_status' => 'unemployed',
        'debt_to_income_ratio' => 55,
        'active_loans' => 5,
    ],
];

// ============================================================================
// STEP 3: Evaluate Each Applicant
// ============================================================================

echo "🔍 Evaluating applicants...\n";
echo str_repeat('-', 72)."\n\n";

foreach ($applicants as $index => $applicant) {
    echo 'APPLICANT '.($index + 1).": {$applicant['name']}\n";
    echo str_repeat('-', 72)."\n";
    echo "Credit Score: {$applicant['credit_score']}\n";
    echo "Annual Income: \${$applicant['annual_income']}\n";
    echo "Employment: {$applicant['employment_status']}\n";
    echo "Debt-to-Income: {$applicant['debt_to_income_ratio']}%\n";
    echo "Active Loans: {$applicant['active_loans']}\n";

    // Evaluate with callbacks
    $result = Eligify::evaluateWithCallbacks(
        $criteria,
        $applicant
    );

    echo "\n📊 EVALUATION RESULT:\n";
    echo "   Overall Score: {$result['score']}%\n";
    echo "   Decision: {$result['decision']}\n";
    echo '   Status: '.($result['passed'] ? '✅ APPROVED' : '❌ DENIED')."\n";

    echo "\n".str_repeat('=', 72)."\n\n";
}

// ============================================================================
// STEP 4: Demonstrate Without Callbacks (Direct Evaluation)
// ============================================================================

echo "📈 SUMMARY EVALUATION (Without Callbacks)\n";
echo str_repeat('-', 72)."\n\n";

$summaryData = [];

foreach ($applicants as $applicant) {
    $result = Eligify::evaluate('personal_loan_approval', $applicant);

    $summaryData[] = [
        'name' => $applicant['name'],
        'score' => $result['score'],
        'passed' => $result['passed'],
        'decision' => $result['decision'],
    ];
}

// Display summary table
printf("%-20s | %-10s | %-10s | %-20s\n", 'Name', 'Score', 'Status', 'Decision');
echo str_repeat('-', 72)."\n";

foreach ($summaryData as $data) {
    printf(
        "%-20s | %7.1f%% | %-10s | %-20s\n",
        $data['name'],
        $data['score'],
        $data['passed'] ? '✅ PASS' : '❌ FAIL',
        $data['decision']
    );
}

echo "\n";

// ============================================================================
// STEP 5: Show Audit Trail (if enabled)
// ============================================================================

echo "📝 AUDIT INFORMATION:\n";
echo str_repeat('-', 72)."\n";
echo "All loan evaluations are automatically logged for compliance.\n";
echo "Audit logs include:\n";
echo "  • Timestamp of evaluation\n";
echo "  • Applicant data submitted\n";
echo "  • Evaluation results and scores\n";
echo "  • Rules that passed/failed\n";
echo "  • Decision outcome\n\n";

// ============================================================================
// USAGE IN LARAVEL APPLICATION
// ============================================================================

echo "💡 INTEGRATION EXAMPLE:\n";
echo str_repeat('-', 72)."\n";
echo <<<'LARAVEL'

// In your Laravel Controller:
class LoanApplicationController extends Controller
{
    public function submit(LoanApplicationRequest $request)
    {
        $applicantData = [
            'credit_score' => $request->credit_score,
            'annual_income' => $request->annual_income,
            'employment_status' => $request->employment_status,
            'debt_to_income_ratio' => $request->debt_to_income_ratio,
            'active_loans' => $request->active_loans,
        ];

        $result = Eligify::evaluateWithCallbacks(
            Eligify::criteria('personal_loan_approval'),
            $applicantData
        );

        if ($result['passed']) {
            // Create loan record
            $loan = Loan::create([
                'user_id' => $request->user()->id,
                'amount' => $request->amount,
                'status' => 'approved',
                'eligibility_score' => $result['score'],
            ]);

            return redirect()
                ->route('loan.success', $loan)
                ->with('success', 'Your loan has been approved!');
        }

        return back()
            ->with('error', 'Loan application denied')
            ->with('reasons', $result['failed_rules']);
    }
}

LARAVEL;

echo "\n".str_repeat('=', 72)."\n";
echo "Example completed! Check the results above.\n";
echo str_repeat('=', 72)."\n";
