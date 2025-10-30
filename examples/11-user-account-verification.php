<?php

/**
 * Example 11: User Account Verification Eligibility
 *
 * Demonstrates the use of UserModelMapping for automatic data extraction
 * from User models, combined with eligibility criteria for account verification.
 *
 * Use Case: Determine if a user account is eligible for full platform access
 * based on account age, verification status, and basic activity.
 *
 * Features Demonstrated:
 * - UserModelMapping automatic field extraction
 * - Model-based evaluation with HasEligibility trait
 * - Simple verification criteria
 * - Automatic computed fields (account_age_days, is_verified)
 */

require_once __DIR__.'/bootstrap.php';

use CleaniqueCoders\Eligify\Facades\Eligify;
use CleaniqueCoders\Eligify\Support\Extractor;
use Illuminate\Database\Eloquent\Model;

// ============================================================================
// STEP 1: Define a simple User model for demonstration
// ============================================================================

class User extends Model
{
    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // In real Laravel app, add this trait to enable model evaluation
    // use \CleaniqueCoders\Eligify\Concerns\HasEligibility;
}

// ============================================================================
// STEP 2: Create eligibility criteria for verified user access
// ============================================================================

echo "🎯 Creating User Account Verification Criteria...\n";
echo str_repeat('=', 80)."\n\n";

$criteria = Eligify::criteria('verified_user_access')
    ->description('Criteria for granting full platform access to verified users')
    ->addRule('is_verified', '==', true, 40)              // Must be verified (40% weight)
    ->addRule('account_age_days', '>=', 7, 30)            // Account at least 7 days old (30%)
    ->addRule('created_at', '<=', now()->toDateTimeString(), 30) // Valid registration (30%)
    ->passThreshold(70)  // Must score at least 70%
    ->onPass(function ($data, $result) {
        echo "✅ GRANTED: User '{$data['name']}' has full platform access\n";
        echo "   📊 Verification Score: {$result['score']}%\n";
        echo "   📧 Email: {$data['email']}\n";
        echo "   📅 Account Age: {$data['account_age_days']} days\n";
    })
    ->onFail(function ($data, $result) {
        echo "❌ DENIED: User '{$data['name']}' doesn't meet verification requirements\n";
        echo "   📊 Score: {$result['score']}%\n";
        $failedCount = count($result['failed_rules']);
        echo "   ⚠️  Failed {$failedCount} rule(s)\n";

        // Show what failed
        if (isset($result['execution_log'])) {
            foreach ($result['execution_log'] as $logEntry) {
                if (! $logEntry['passed']) {
                    $actual = is_bool($logEntry['actual']) ? ($logEntry['actual'] ? 'true' : 'false') : $logEntry['actual'];
                    $expected = is_bool($logEntry['expected']) ? ($logEntry['expected'] ? 'true' : 'false') : $logEntry['expected'];
                    echo "   ❗ {$logEntry['field']}: expected {$logEntry['operator']} {$expected}, got {$actual}\n";
                }
            }
        }
    })
    ->save();

echo "✓ Criteria created successfully!\n\n";

// ============================================================================
// STEP 3: Create test users with different verification states
// ============================================================================

echo "👥 Creating Test Users...\n";
echo str_repeat('=', 80)."\n\n";

// User 1: Fully verified, old account
$verifiedUser = new User([
    'name' => 'Alice Johnson',
    'email' => 'alice@example.com',
    'email_verified_at' => now()->subDays(30),
    'created_at' => now()->subDays(30),
    'updated_at' => now()->subDays(1),
]);

// User 2: Verified but new account
$newUser = new User([
    'name' => 'Bob Smith',
    'email' => 'bob@example.com',
    'email_verified_at' => now()->subDays(2),
    'created_at' => now()->subDays(2),
    'updated_at' => now()->subHours(12),
]);

// User 3: Unverified account
$unverifiedUser = new User([
    'name' => 'Charlie Brown',
    'email' => 'charlie@example.com',
    'email_verified_at' => null,
    'created_at' => now()->subDays(15),
    'updated_at' => now()->subDays(1),
]);

// User 4: Verified and meets all criteria
$eligibleUser = new User([
    'name' => 'Diana Prince',
    'email' => 'diana@example.com',
    'email_verified_at' => now()->subDays(10),
    'created_at' => now()->subDays(14),
    'updated_at' => now(),
]);

echo "✓ Created 4 test users\n\n";

// ============================================================================
// STEP 4: Demonstrate UserModelMapping automatic extraction
// ============================================================================

echo "🔍 Demonstrating UserModelMapping Data Extraction...\n";
echo str_repeat('=', 80)."\n\n";

// Extract data using UserModelMapping
$extractor = Extractor::forModel('User');
$extractedData = $extractor->extract($verifiedUser);

echo "📦 Extracted Data from User Model:\n";
echo "   Original Fields:\n";
echo "   - name: {$extractedData['name']}\n";
echo "   - email: {$extractedData['email']}\n";
echo "\n";
echo "   Mapped Fields (via UserModelMapping):\n";
if (isset($extractedData['email_verified_timestamp'])) {
    echo "   - email_verified_at → email_verified_timestamp: {$extractedData['email_verified_timestamp']}\n";
} else {
    echo "   - email_verified_at → email_verified_timestamp: (not mapped - using original field)\n";
}
if (isset($extractedData['registration_date'])) {
    echo "   - created_at → registration_date: {$extractedData['registration_date']}\n";
} else {
    echo "   - created_at → registration_date: (not mapped - using original field)\n";
}
echo "\n";
echo "   Computed Fields (automatic):\n";
$isVerified = $extractedData['is_verified'] ?? false;
echo '   - is_verified: '.($isVerified ? 'true' : 'false')."\n";
echo "   - account_age_days: {$extractedData['account_age_days']}\n";
echo "   - created_days_ago: {$extractedData['created_days_ago']}\n";
echo "   - created_months_ago: {$extractedData['created_months_ago']}\n";
echo "\n";

// ============================================================================
// STEP 5: Evaluate each user against the criteria
// ============================================================================

echo "🎯 Evaluating Users Against Verification Criteria...\n";
echo str_repeat('=', 80)."\n\n";

$users = [
    'Fully Verified (30 days old)' => $verifiedUser,
    'New User (2 days old)' => $newUser,
    'Unverified (15 days old)' => $unverifiedUser,
    'Eligible User (14 days old)' => $eligibleUser,
];

foreach ($users as $description => $user) {
    echo "📋 Evaluating: {$description}\n";
    echo str_repeat('-', 80)."\n";

    // Extract data using UserModelMapping
    $extractor = Extractor::forModel('User');
    $data = $extractor->extract($user);

    // Evaluate using the extracted data
    $result = Eligify::evaluate('verified_user_access', $data);

    echo "\n";
}

// ============================================================================
// STEP 6: Show detailed evaluation breakdown
// ============================================================================

echo "\n📊 Detailed Evaluation Breakdown\n";
echo str_repeat('=', 80)."\n\n";

// Evaluate the eligible user and show details
$extractor = Extractor::forModel('User');
$data = $extractor->extract($eligibleUser);
$result = Eligify::evaluate('verified_user_access', $data);

echo "User: {$data['name']}\n";
echo 'Status: '.($result['passed'] ? '✅ PASSED' : '❌ FAILED')."\n";
echo "Score: {$result['score']}%\n";
echo "Decision: {$result['decision']}\n\n";

echo "Rule-by-Rule Breakdown:\n";
if (isset($result['execution_log'])) {
    foreach ($result['execution_log'] as $index => $logEntry) {
        $status = $logEntry['passed'] ? '✅' : '❌';

        echo sprintf(
            "  %s Rule %d: %s %s %s (Weight: %d)\n",
            $status,
            $index + 1,
            $logEntry['field'],
            $logEntry['operator'],
            is_bool($logEntry['expected']) ? ($logEntry['expected'] ? 'true' : 'false') : $logEntry['expected'],
            $logEntry['weight']
        );

        echo sprintf(
            "     Actual: %s | Score: %.1f | Passed: %s\n",
            is_bool($logEntry['actual']) ? ($logEntry['actual'] ? 'true' : 'false') : $logEntry['actual'],
            $logEntry['score'],
            $logEntry['passed'] ? 'Yes' : 'No'
        );
    }
} else {
    echo "  No execution log available\n";
}

// ============================================================================
// STEP 7: Demonstrate batch evaluation
// ============================================================================

echo "\n\n🔄 Batch Evaluation of All Users\n";
echo str_repeat('=', 80)."\n\n";

$batchData = [];
$userNames = [];

foreach ($users as $description => $user) {
    $extractor = Extractor::forModel('User');
    $batchData[] = $extractor->extract($user);
    $userNames[] = $user->name;
}

echo "Evaluating {count($batchData)} users individually...\n\n";

echo "Batch Evaluation Summary:\n";
echo sprintf("%-25s | %-10s | %-10s | %s\n", 'User', 'Status', 'Score', 'Verified');
echo str_repeat('-', 80)."\n";

foreach ($batchData as $index => $data) {
    $result = Eligify::evaluate('verified_user_access', $data, false); // Don't save each evaluation
    $isVerified = $data['is_verified'] ?? false;
    echo sprintf(
        "%-25s | %-10s | %6.1f%%   | %s\n",
        $userNames[$index],
        $result['passed'] ? '✅ Passed' : '❌ Failed',
        $result['score'],
        $isVerified ? 'Yes' : 'No'
    );
}

// ============================================================================
// Summary and Key Takeaways
// ============================================================================

echo "\n\n📚 Key Takeaways\n";
echo str_repeat('=', 80)."\n\n";

echo "1. UserModelMapping Benefits:\n";
echo "   ✓ Automatic field mapping (email_verified_at → email_verified_timestamp)\n";
echo "   ✓ Computed fields without manual calculation (is_verified, account_age_days)\n";
echo "   ✓ Consistent data structure across evaluations\n";
echo "\n";

echo "2. Simple Criteria Creation:\n";
echo "   ✓ Use extracted fields directly in rules\n";
echo "   ✓ Leverage computed fields for complex conditions\n";
echo "   ✓ Set appropriate weights and thresholds\n";
echo "\n";

echo "3. Flexible Evaluation:\n";
echo "   ✓ Single user evaluation with Extractor::forModel()\n";
echo "   ✓ Batch evaluation for multiple users\n";
echo "   ✓ Detailed rule-by-rule results\n";
echo "\n";

echo "4. Real-World Applications:\n";
echo "   • User account verification\n";
echo "   • Feature access control\n";
echo "   • Tiered membership eligibility\n";
echo "   • Onboarding progress tracking\n";
echo "\n";

echo "✨ Example completed successfully!\n";
