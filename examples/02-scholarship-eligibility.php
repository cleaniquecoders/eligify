<?php

/**
 * Example 02: Scholarship Eligibility System
 *
 * Use Case: Educational institution needs to evaluate students for various
 * scholarship programs based on academic merit, financial need, and
 * extracurricular involvement.
 *
 * Features Demonstrated:
 * - Threshold-based decision tiers (Full, Partial, Merit)
 * - Score range callbacks for different award amounts
 * - Batch evaluation of multiple students
 * - Custom scoring with academic and financial factors
 *
 * Business Logic:
 * - GPA >= 3.5 (Academic Excellence)
 * - Financial need assessment (family income)
 * - Extracurricular activities score
 * - Community service hours
 * - Essay/application completeness
 *
 * Award Tiers:
 * - 90-100%: Full Scholarship ($20,000/year)
 * - 75-89%: Partial Scholarship ($10,000/year)
 * - 60-74%: Merit Award ($5,000/year)
 * - Below 60%: Not Eligible
 */

require_once __DIR__.'/bootstrap.php';

use CleaniqueCoders\Eligify\Facades\Eligify;

echo '='.str_repeat('=', 70)."\n";
echo "  SCHOLARSHIP ELIGIBILITY SYSTEM EXAMPLE\n";
echo '='.str_repeat('=', 70)."\n\n";

// ============================================================================
// STEP 1: Define Scholarship Criteria
// ============================================================================

echo "📋 Setting up scholarship eligibility criteria...\n\n";

$criteria = Eligify::criteria('academic_scholarship_2025')
    ->description('Academic Excellence Scholarship Program 2025')

    // Academic Performance (40% weight)
    ->addRule('gpa', '>=', 3.5, 40)

    // Financial Need (25% weight) - Lower family income = higher need
    ->addRule('family_income', '<=', 80000, 25)

    // Extracurricular Activities (20% weight)
    ->addRule('extracurricular_score', '>=', 70, 20)

    // Community Service (10% weight)
    ->addRule('community_service_hours', '>=', 40, 10)

    // Application Completeness (5% weight)
    ->addRule('application_complete', '==', true, 5)

    // Set minimum passing threshold
    ->passThreshold(60)

    // Define award tiers using score range callbacks
    ->onScoreRange(90, 100, function ($student, $result) {
        echo "\n🏆 FULL SCHOLARSHIP AWARDED!\n";
        echo "   Student: {$student['name']}\n";
        echo "   Award: \$20,000/year (4 years)\n";
        echo "   Total Value: \$80,000\n";
        echo "   → Congratulations! You've qualified for our top scholarship!\n";
    })

    ->onScoreRange(75, 89, function ($student, $result) {
        echo "\n🥈 PARTIAL SCHOLARSHIP AWARDED!\n";
        echo "   Student: {$student['name']}\n";
        echo "   Award: \$10,000/year (4 years)\n";
        echo "   Total Value: \$40,000\n";
        echo "   → Great achievement! Partial funding approved.\n";
    })

    ->onScoreRange(60, 74, function ($student, $result) {
        echo "\n🥉 MERIT AWARD GRANTED!\n";
        echo "   Student: {$student['name']}\n";
        echo "   Award: \$5,000/year (4 years)\n";
        echo "   Total Value: \$20,000\n";
        echo "   → Well done! You qualify for our merit-based award.\n";
    })

    ->onFail(function ($student, $result) {
        echo "\n📧 NOT ELIGIBLE\n";
        echo "   Student: {$student['name']}\n";
        echo "   Score: {$result['score']}%\n";
        echo "   → Unfortunately, minimum requirements not met.\n";
        echo "   → Consider applying next year after improving:\n";

        if (! empty($result['failed_rules'])) {
            foreach ($result['failed_rules'] as $rule) {
                echo "      • {$rule['field']}\n";
            }
        }
    })

    ->save();

echo "✓ Scholarship criteria configured!\n";
echo "  - Award Tiers: Full, Partial, Merit\n";
echo "  - Minimum Score: 60%\n\n";

// ============================================================================
// STEP 2: Prepare Test Students
// ============================================================================

echo "🎓 Preparing student applications...\n\n";

$students = [
    // CASE 1: Excellent student - Full scholarship
    [
        'name' => 'Emily Chen',
        'gpa' => 4.0,
        'family_income' => 45000,
        'extracurricular_score' => 95,
        'community_service_hours' => 120,
        'application_complete' => true,
    ],

    // CASE 2: Strong student - Partial scholarship
    [
        'name' => 'Marcus Thompson',
        'gpa' => 3.8,
        'family_income' => 65000,
        'extracurricular_score' => 85,
        'community_service_hours' => 60,
        'application_complete' => true,
    ],

    // CASE 3: Good student - Merit award
    [
        'name' => 'Aisha Patel',
        'gpa' => 3.6,
        'family_income' => 75000,
        'extracurricular_score' => 72,
        'community_service_hours' => 45,
        'application_complete' => true,
    ],

    // CASE 4: Borderline student
    [
        'name' => 'James Rodriguez',
        'gpa' => 3.4,
        'family_income' => 90000,
        'extracurricular_score' => 65,
        'community_service_hours' => 30,
        'application_complete' => true,
    ],

    // CASE 5: Not eligible
    [
        'name' => 'Sophie Miller',
        'gpa' => 3.2,
        'family_income' => 100000,
        'extracurricular_score' => 50,
        'community_service_hours' => 15,
        'application_complete' => false,
    ],
];

// ============================================================================
// STEP 3: Evaluate Each Student (With Callbacks)
// ============================================================================

echo "🔍 Evaluating scholarship applications...\n";
echo str_repeat('-', 72)."\n\n";

$awards = [];

foreach ($students as $index => $student) {
    echo 'APPLICATION '.($index + 1).": {$student['name']}\n";
    echo str_repeat('-', 72)."\n";
    echo "GPA: {$student['gpa']}\n";
    echo "Family Income: \${$student['family_income']}\n";
    echo "Extracurricular Score: {$student['extracurricular_score']}/100\n";
    echo "Community Service: {$student['community_service_hours']} hours\n";
    echo 'Application Status: '.($student['application_complete'] ? 'Complete' : 'Incomplete')."\n";

    // Evaluate with callbacks
    $eligify = app(\CleaniqueCoders\Eligify\Eligify::class);
    $result = $eligify->evaluateWithCallbacks($criteria, $student);

    // Determine award tier
    $award = 'None';
    if ($result['score'] >= 90) {
        $award = 'Full Scholarship ($80,000)';
    } elseif ($result['score'] >= 75) {
        $award = 'Partial Scholarship ($40,000)';
    } elseif ($result['score'] >= 60) {
        $award = 'Merit Award ($20,000)';
    }

    $awards[] = [
        'name' => $student['name'],
        'score' => $result['score'],
        'award' => $award,
        'passed' => $result['passed'],
    ];

    echo "\n📊 EVALUATION SUMMARY:\n";
    echo "   Eligibility Score: {$result['score']}%\n";
    echo "   Award: {$award}\n";
    echo '   Status: '.($result['passed'] ? '✅ ELIGIBLE' : '❌ NOT ELIGIBLE')."\n";

    echo "\n".str_repeat('=', 72)."\n\n";
}

// ============================================================================
// STEP 4: Batch Evaluation Summary
// ============================================================================

echo "📊 SCHOLARSHIP AWARDS SUMMARY\n";
echo str_repeat('-', 72)."\n\n";

printf("%-20s | %-10s | %-10s | %-30s\n", 'Student', 'Score', 'Status', 'Award');
echo str_repeat('-', 72)."\n";

$totalAwarded = 0;
$awardValues = [
    'Full Scholarship ($80,000)' => 80000,
    'Partial Scholarship ($40,000)' => 40000,
    'Merit Award ($20,000)' => 20000,
    'None' => 0,
];

foreach ($awards as $award) {
    printf(
        "%-20s | %7.1f%% | %-10s | %-30s\n",
        $award['name'],
        $award['score'],
        $award['passed'] ? '✅ YES' : '❌ NO',
        $award['award']
    );

    $totalAwarded += $awardValues[$award['award']];
}

echo str_repeat('-', 72)."\n";
echo 'Total Scholarship Budget Allocated: $'.number_format($totalAwarded)."\n\n";

// ============================================================================
// STEP 5: Statistics
// ============================================================================

$eligible = count(array_filter($awards, fn ($a) => $a['passed']));
$notEligible = count($awards) - $eligible;

echo "📈 PROGRAM STATISTICS:\n";
echo str_repeat('-', 72)."\n";
echo 'Total Applications: '.count($awards)."\n";
echo "Eligible Students: {$eligible}\n";
echo "Not Eligible: {$notEligible}\n";
echo 'Acceptance Rate: '.round(($eligible / count($awards)) * 100, 1)."%\n\n";

// ============================================================================
// USAGE IN LARAVEL APPLICATION
// ============================================================================

echo "💡 LARAVEL INTEGRATION EXAMPLE:\n";
echo str_repeat('-', 72)."\n";
echo <<<'LARAVEL'

// In your Laravel Controller:
class ScholarshipApplicationController extends Controller
{
    public function evaluate(Student $student)
    {
        $applicationData = [
            'gpa' => $student->gpa,
            'family_income' => $student->family_income,
            'extracurricular_score' => $student->calculateExtracurricularScore(),
            'community_service_hours' => $student->community_service_hours,
            'application_complete' => $student->hasCompletedApplication(),
        ];

        $eligify = app(\CleaniqueCoders\Eligify\Eligify::class);
        $result = $eligify->evaluate('academic_scholarship_2025', $applicationData);

        // Determine award tier
        $awardTier = match(true) {
            $result['score'] >= 90 => 'full',
            $result['score'] >= 75 => 'partial',
            $result['score'] >= 60 => 'merit',
            default => null
        };

        // Update student record
        $student->update([
            'scholarship_eligible' => $result['passed'],
            'eligibility_score' => $result['score'],
            'award_tier' => $awardTier,
        ]);

        // Send notification
        if ($result['passed']) {
            Mail::to($student)->send(new ScholarshipApproved($student, $awardTier));
        }

        return view('scholarship.result', compact('student', 'result', 'awardTier'));
    }

    // Batch evaluate all pending applications
    public function batchEvaluate()
    {
        $pendingStudents = Student::where('scholarship_status', 'pending')->get();

        foreach ($pendingStudents as $student) {
            $this->evaluate($student);
        }

        return redirect()
            ->route('scholarship.dashboard')
            ->with('success', count($pendingStudents) . ' applications evaluated');
    }
}

LARAVEL;

echo "\n".str_repeat('=', 72)."\n";
echo "Example completed! Check scholarship awards above.\n";
echo str_repeat('=', 72)."\n";
