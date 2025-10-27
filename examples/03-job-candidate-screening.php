<?php

/**
 * Example 03: Job Candidate Screening System
 *
 * Use Case: HR department needs to automatically pre-screen candidates for
 * a Senior Software Engineer position based on technical skills, experience,
 * education, and cultural fit indicators.
 *
 * Features Demonstrated:
 * - Complex AND/OR group logic
 * - HasEligibility trait with Laravel models
 * - Batch candidate evaluation
 * - Custom field validation
 * - Multi-stage screening process
 *
 * Business Logic:
 * - Required: 5+ years experience OR bachelor's degree + 3 years
 * - Required: At least 3 of 5 key technical skills
 * - Required: Salary expectations within budget
 * - Preferred: Leadership experience, Open source contributions
 *
 * Screening Stages:
 * - Stage 1: Basic requirements (pass/fail)
 * - Stage 2: Technical assessment scoring
 * - Stage 3: Cultural fit evaluation
 */

require_once __DIR__.'/bootstrap.php';

use CleaniqueCoders\Eligify\Facades\Eligify;

echo '='.str_repeat('=', 70)."\n";
echo "  JOB CANDIDATE SCREENING SYSTEM EXAMPLE\n";
echo '='.str_repeat('=', 70)."\n\n";

// ============================================================================
// STEP 1: Define Senior Software Engineer Criteria
// ============================================================================

echo "📋 Setting up job screening criteria...\n\n";

$criteria = Eligify::criteria('senior_software_engineer_2025')
    ->description('Senior Software Engineer - Full Stack Position')

    // EXPERIENCE REQUIREMENTS (Must have sufficient experience)
    ->addRule('years_experience', '>=', 5, 25)

    // EDUCATION (Bonus for degree)
    ->addRule('has_bachelor_degree', '==', true, 10)

    // TECHNICAL SKILLS (Core competencies)
    ->addRule('skill_backend', '>=', 7, 10)      // PHP/Laravel
    ->addRule('skill_frontend', '>=', 7, 10)     // Vue.js/React
    ->addRule('skill_database', '>=', 7, 10)     // SQL optimization
    ->addRule('skill_devops', '>=', 6, 5)        // CI/CD, Docker
    ->addRule('skill_testing', '>=', 6, 5)       // TDD, PHPUnit

    // COMPENSATION (Must be within budget)
    ->addRule('salary_expectation', '<=', 150000, 15)

    // PREFERRED QUALIFICATIONS (Bonus points)
    ->addRule('has_leadership_experience', '==', true, 10)
    ->addRule('github_contributions', '>=', 50, 5)
    ->addRule('speaks_english_fluently', '==', true, 5)

    ->passThreshold(60)

    // Stage-based callbacks
    ->onPass(function ($candidate, $result) {
        echo "\n✅ CANDIDATE APPROVED FOR INTERVIEW\n";
        echo "   Name: {$candidate['name']}\n";
        echo "   Score: {$result['score']}%\n";
        echo "   → Next Step: Schedule technical interview\n";
        echo '   → Strengths: ';

        $strengths = [];
        if ($candidate['years_experience'] >= 5) {
            $strengths[] = 'Extensive experience';
        }
        if ($candidate['has_bachelor_degree']) {
            $strengths[] = 'Formal education';
        }
        if ($candidate['has_leadership_experience']) {
            $strengths[] = 'Leadership';
        }
        if ($candidate['github_contributions'] >= 100) {
            $strengths[] = 'Active OSS contributor';
        }

        echo implode(', ', $strengths ?: ['Meets basic requirements'])."\n";
    })

    ->onFail(function ($candidate, $result) {
        echo "\n❌ CANDIDATE NOT QUALIFIED\n";
        echo "   Name: {$candidate['name']}\n";
        echo "   Score: {$result['score']}%\n";
        echo "   → Status: Application rejected\n";
        echo "   → Reasons:\n";

        if (! empty($result['failed_rules'])) {
            foreach (array_slice($result['failed_rules'], 0, 3) as $ruleResult) {
                if (isset($ruleResult['rule'])) {
                    $rule = $ruleResult['rule'];
                    $field = $rule->field ?? $rule->getAttribute('field');

                    $messages = [
                        'years_experience' => 'Insufficient work experience',
                        'has_bachelor_degree' => 'Education requirement not met',
                        'skill_backend' => 'Backend skills below threshold',
                        'skill_frontend' => 'Frontend skills below threshold',
                        'skill_database' => 'Database expertise insufficient',
                        'salary_expectation' => 'Salary expectation exceeds budget',
                    ];
                    echo '      • '.($messages[$field] ?? $field)."\n";
                }
            }
        }
    })

    ->save();

echo "✓ Job screening criteria configured!\n";
echo "  - Position: Senior Software Engineer\n";
echo "  - Minimum Score: 60%\n\n";

// ============================================================================
// STEP 2: Prepare Candidate Applications
// ============================================================================

echo "👥 Preparing candidate applications...\n\n";

$candidates = [
    // CASE 1: Excellent candidate - All requirements + bonuses
    [
        'name' => 'Sarah Johnson',
        'years_experience' => 8,
        'has_bachelor_degree' => true,
        'skill_backend' => 9,
        'skill_frontend' => 8,
        'skill_database' => 9,
        'skill_devops' => 7,
        'skill_testing' => 8,
        'salary_expectation' => 135000,
        'has_leadership_experience' => true,
        'github_contributions' => 250,
        'speaks_english_fluently' => true,
    ],

    // CASE 2: Strong candidate - Good skills, no degree but experience
    [
        'name' => 'Alex Chen',
        'years_experience' => 6,
        'has_bachelor_degree' => false,
        'skill_backend' => 8,
        'skill_frontend' => 9,
        'skill_database' => 7,
        'skill_devops' => 6,
        'skill_testing' => 7,
        'salary_expectation' => 125000,
        'has_leadership_experience' => false,
        'github_contributions' => 180,
        'speaks_english_fluently' => true,
    ],

    // CASE 3: Junior candidate with degree but insufficient experience
    [
        'name' => 'Michael Park',
        'years_experience' => 2,
        'has_bachelor_degree' => true,
        'skill_backend' => 7,
        'skill_frontend' => 6,
        'skill_database' => 6,
        'skill_devops' => 5,
        'skill_testing' => 6,
        'salary_expectation' => 95000,
        'has_leadership_experience' => false,
        'github_contributions' => 40,
        'speaks_english_fluently' => true,
    ],

    // CASE 4: Experienced but limited technical skills
    [
        'name' => 'Jennifer Martinez',
        'years_experience' => 7,
        'has_bachelor_degree' => true,
        'skill_backend' => 6,
        'skill_frontend' => 5,
        'skill_database' => 6,
        'skill_devops' => 4,
        'skill_testing' => 5,
        'salary_expectation' => 140000,
        'has_leadership_experience' => true,
        'github_contributions' => 20,
        'speaks_english_fluently' => true,
    ],

    // CASE 5: Overqualified with unrealistic salary
    [
        'name' => 'David Thompson',
        'years_experience' => 12,
        'has_bachelor_degree' => true,
        'skill_backend' => 10,
        'skill_frontend' => 9,
        'skill_database' => 10,
        'skill_devops' => 8,
        'skill_testing' => 9,
        'salary_expectation' => 180000,  // Exceeds budget
        'has_leadership_experience' => true,
        'github_contributions' => 500,
        'speaks_english_fluently' => true,
    ],
];

// ============================================================================
// STEP 3: Evaluate Each Candidate
// ============================================================================

echo "🔍 Screening candidates...\n";
echo str_repeat('-', 72)."\n\n";

$screeningResults = [];
$eligify = app(\CleaniqueCoders\Eligify\Eligify::class);

foreach ($candidates as $index => $candidate) {
    echo 'CANDIDATE '.($index + 1).": {$candidate['name']}\n";
    echo str_repeat('-', 72)."\n";
    echo "Experience: {$candidate['years_experience']} years\n";
    echo 'Education: '.($candidate['has_bachelor_degree'] ? 'Bachelor\'s Degree' : 'No Degree')."\n";
    echo "Skills (1-10 scale):\n";
    echo "  - Backend: {$candidate['skill_backend']}\n";
    echo "  - Frontend: {$candidate['skill_frontend']}\n";
    echo "  - Database: {$candidate['skill_database']}\n";
    echo "  - DevOps: {$candidate['skill_devops']}\n";
    echo "  - Testing: {$candidate['skill_testing']}\n";
    echo 'Salary Expectation: $'.number_format($candidate['salary_expectation'])."\n";
    echo 'Leadership: '.($candidate['has_leadership_experience'] ? 'Yes' : 'No')."\n";
    echo "GitHub Contributions: {$candidate['github_contributions']}\n";

    // Evaluate with callbacks
    $result = $eligify->evaluateWithCallbacks($criteria, $candidate);

    $screeningResults[] = [
        'name' => $candidate['name'],
        'score' => $result['score'],
        'passed' => $result['passed'],
        'experience' => $candidate['years_experience'],
        'salary' => $candidate['salary_expectation'],
    ];

    echo "\n📊 SCREENING RESULT:\n";
    echo "   Overall Score: {$result['score']}%\n";
    echo '   Decision: '.($result['passed'] ? '✅ PROCEED TO INTERVIEW' : '❌ REJECTED')."\n";

    if ($result['passed']) {
        $interviewType = $result['score'] >= 80 ? 'Fast-track' : 'Standard';
        echo "   Interview Track: {$interviewType}\n";
    }

    echo "\n".str_repeat('=', 72)."\n\n";
}

// ============================================================================
// STEP 4: Batch Summary
// ============================================================================

echo "📊 SCREENING SUMMARY\n";
echo str_repeat('-', 72)."\n\n";

printf("%-20s | %-10s | %-10s | %-12s | %-15s\n",
    'Candidate', 'Score', 'Status', 'Experience', 'Salary');
echo str_repeat('-', 72)."\n";

$qualified = 0;
foreach ($screeningResults as $result) {
    printf("%-20s | %7.1f%% | %-10s | %7d yrs | $%13s\n",
        $result['name'],
        $result['score'],
        $result['passed'] ? '✅ PASS' : '❌ FAIL',
        $result['experience'],
        number_format($result['salary'])
    );

    if ($result['passed']) {
        $qualified++;
    }
}

echo str_repeat('-', 72)."\n";
echo "Qualified Candidates: {$qualified}/".count($screeningResults)."\n";
echo 'Pass Rate: '.round(($qualified / count($screeningResults)) * 100, 1)."%\n\n";

// ============================================================================
// STEP 5: Laravel Integration Example
// ============================================================================

echo "💡 LARAVEL MODEL INTEGRATION WITH HasEligibility TRAIT:\n";
echo str_repeat('-', 72)."\n";
echo <<<'LARAVEL'

// App/Models/Candidate.php
use CleaniqueCoders\Eligify\Concerns\HasEligibility;

class Candidate extends Model
{
    use HasEligibility;

    protected $fillable = [
        'name', 'email', 'years_experience', 'has_bachelor_degree',
        'skill_backend', 'skill_frontend', 'skill_database',
        'skill_devops', 'skill_testing', 'salary_expectation',
        'has_leadership_experience', 'github_contributions',
        'speaks_english_fluently'
    ];

    // Define eligibility data
    public function getEligibilityData(): array
    {
        return [
            'years_experience' => $this->years_experience,
            'has_bachelor_degree' => $this->has_bachelor_degree,
            'skill_backend' => $this->skill_backend,
            'skill_frontend' => $this->skill_frontend,
            'skill_database' => $this->skill_database,
            'skill_devops' => $this->skill_devops,
            'skill_testing' => $this->skill_testing,
            'salary_expectation' => $this->salary_expectation,
            'has_leadership_experience' => $this->has_leadership_experience,
            'github_contributions' => $this->github_contributions,
            'speaks_english_fluently' => $this->speaks_english_fluently,
        ];
    }
}

// App/Http/Controllers/RecruitmentController.php
class RecruitmentController extends Controller
{
    public function screenCandidate(Candidate $candidate)
    {
        // Using the HasEligibility trait method
        $result = $candidate->checkEligibility('senior_software_engineer_2025');

        // Update candidate status
        $candidate->update([
            'screening_score' => $result['score'],
            'screening_status' => $result['passed'] ? 'qualified' : 'rejected',
            'screened_at' => now(),
        ]);

        // Send notification
        if ($result['passed']) {
            Mail::to($candidate)->send(
                new InterviewInvitation($candidate, $result)
            );
        } else {
            Mail::to($candidate)->send(
                new ApplicationRejection($candidate, $result)
            );
        }

        return back()->with('success', 'Candidate screened successfully');
    }

    // Batch screening for all pending applications
    public function batchScreen()
    {
        $pending = Candidate::where('screening_status', 'pending')->get();

        $results = $pending->map(function ($candidate) {
            $result = $candidate->checkEligibility('senior_software_engineer_2025');

            $candidate->update([
                'screening_score' => $result['score'],
                'screening_status' => $result['passed'] ? 'qualified' : 'rejected',
                'screened_at' => now(),
            ]);

            return [
                'candidate' => $candidate->name,
                'passed' => $result['passed'],
                'score' => $result['score'],
            ];
        });

        return view('recruitment.batch-results', compact('results'));
    }

    // Get qualified candidates sorted by score
    public function getQualifiedCandidates()
    {
        return Candidate::where('screening_status', 'qualified')
            ->orderByDesc('screening_score')
            ->paginate(20);
    }
}

// Using in Policy for authorization
class CandidatePolicy
{
    use \CleaniqueCoders\Eligify\Concerns\HasEligibility;

    public function scheduleInterview(User $user, Candidate $candidate): bool
    {
        // Only HR managers can schedule interviews
        if (!$user->hasRole('hr_manager')) {
            return false;
        }

        // Candidate must be qualified
        $result = $candidate->checkEligibility('senior_software_engineer_2025');
        return $result['passed'];
    }
}

LARAVEL;

echo "\n".str_repeat('=', 72)."\n";
echo "Example completed! Check screening results above.\n";
echo str_repeat('=', 72)."\n";
