<?php

/**
 * Example: Testing the Playground Sample Data Generator
 *
 * This shows how the playground generates nested JSON from rules with dot notation
 */

require __DIR__.'/bootstrap.php';

use CleaniqueCoders\Eligify\Facades\Eligify;

// Example 1: Create criteria with dot notation fields
echo "📋 Creating Loan Approval Criteria with nested fields...\n\n";

$loanCriteria = Eligify::criteria('loan-approval-playground-test')
    ->description('Loan approval with nested applicant data')

    // Nested applicant fields
    ->addRule('applicant.income', '>=', 3000, weight: 30)
    ->addRule('applicant.age', '>=', 18, weight: 20)
    ->addRule('applicant.credit_score', '>=', 650, weight: 25)
    ->addRule('applicant.not_bankrupt', '==', true, weight: 25)

    // Nested loan fields
    ->addRule('loan.amount', '<=', 50000, weight: 15)
    ->addRule('loan.purpose', 'in', ['home', 'business', 'education'], weight: 10)

    ->save();

echo "✅ Criteria created with {$loanCriteria->getCriteria()->rules->count()} rules\n\n";

// Example 2: Show what the playground would generate
echo "🎯 Sample data that playground would generate:\n\n";

$rules = $loanCriteria->getCriteria()->rules;
$sampleData = [];

foreach ($rules as $rule) {
    $field = $rule->field;
    $operator = $rule->operator;
    $value = $rule->value;

    // Generate appropriate sample value
    $sampleValue = match($operator) {
        '>=', '>' => is_numeric($value) ? $value + 10 : $value,
        '<=', '<' => is_numeric($value) ? $value - 10 : $value,
        '==' => $value,
        'in' => is_array($value) ? $value[0] : $value,
        default => $value,
    };

    // Handle dot notation
    if (str_contains($field, '.')) {
        $keys = explode('.', $field);
        $nested = &$sampleData;

        while (count($keys) > 1) {
            $key = array_shift($keys);
            if (!isset($nested[$key])) {
                $nested[$key] = [];
            }
            $nested = &$nested[$key];
        }

        $nested[array_shift($keys)] = $sampleValue;
    } else {
        $sampleData[$field] = $sampleValue;
    }
}

echo json_encode($sampleData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
echo "\n\n";

// Example 3: Test with the generated data
echo "🧪 Testing with generated data...\n\n";

$result = Eligify::evaluate('loan-approval-playground-test', $sampleData);

echo "Result: " . ($result['passed'] ? '✅ PASSED' : '❌ FAILED') . "\n";
echo "Score: {$result['score']}/100\n";
echo "Decision: {$result['decision']}\n\n";

// Show execution log
if (!empty($result['execution_log'])) {
    echo "📊 Rules Breakdown:\n";
    foreach ($result['execution_log'] as $log) {
        $status = $log['passed'] ? '✓' : '✗';
        echo "  {$status} {$log['field']} {$log['operator']} ";
        echo json_encode($log['expected']) . " (actual: " . json_encode($log['actual']) . ")\n";
    }
}

echo "\n✨ This is what you'll see in the Playground UI!\n";
