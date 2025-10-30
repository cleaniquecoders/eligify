<?php

/**
 * Example 14: Snapshot Usage
 *
 * Use Case: Demonstrates the power of the Snapshot class for
 * flexible data extraction, transformation, and evaluation.
 *
 * Features Demonstrated:
 * - Creating Snapshot instances
 * - Property and array access patterns
 * - Data filtering and transformation
 * - Metadata tracking
 * - Integration with rule evaluation
 * - Immutability benefits
 * - Method chaining
 *
 * This example shows how Snapshot provides type safety, rich APIs,
 * and better developer experience compared to plain arrays.
 */

require_once __DIR__.'/bootstrap.php';

use CleaniqueCoders\Eligify\Data\Snapshot;
use CleaniqueCoders\Eligify\Facades\Eligify;

echo '='.str_repeat('=', 70)."\n";
echo "  SNAPSHOT USAGE EXAMPLE\n";
echo '='.str_repeat('=', 70)."\n\n";

// ============================================================================
// STEP 1: Basic Usage - Creating and Accessing Data
// ============================================================================

echo "📦 STEP 1: Basic Usage\n";
echo str_repeat('-', 70)."\n\n";

// Create an Snapshot instance
$rawData = [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'annual_income' => 75000.50,
    'credit_score' => 720.3,
    'age' => 35,
    'employment_status' => 'employed',
    'active_loans' => 2,
    'debt_to_income_ratio' => 28.5,
    'is_verified' => true,
    'ssn' => '123-45-6789',
    'account_number' => 'ACC123456',
];

$extracted = new Snapshot($rawData, [
    'model_class' => 'App\Models\User',
    'model_key' => 123,
    'extracted_at' => now()->toIso8601String(),
]);

echo '✓ Created Snapshot with '.count($extracted)." fields\n\n";

// Access data using different methods
echo "Access Patterns:\n";
echo "  Property syntax: \$extracted->name = '{$extracted->name}'\n";
echo "  Array syntax: \$extracted['email'] = '{$extracted['email']}'\n";
echo "  Get with default: \$extracted->get('phone', 'N/A') = '{$extracted->get('phone', 'N/A')}'\n";
echo "  Has check: \$extracted->has('income') = ".(var_export($extracted->has('annual_income'), true))."\n\n";

// Metadata access
echo "Metadata:\n";
echo "  Model Class: {$extracted->metadata('model_class')}\n";
echo "  Model Key: {$extracted->metadata('model_key')}\n";
echo "  Field Count: {$extracted->metadata('field_count')}\n";
echo "  Extracted At: {$extracted->metadata('extracted_at')}\n\n";

// ============================================================================
// STEP 2: Data Filtering and Selection
// ============================================================================

echo "📋 STEP 2: Data Filtering and Selection\n";
echo str_repeat('-', 70)."\n\n";

// Select only specific fields
$financialData = $extracted->only([
    'annual_income',
    'credit_score',
    'debt_to_income_ratio',
    'active_loans',
]);

echo "Financial fields only:\n";
foreach ($financialData->toArray() as $key => $value) {
    echo "  • {$key}: {$value}\n";
}
echo "\n";

// Exclude sensitive fields
$safeData = $extracted->except(['ssn', 'account_number']);
echo 'Sensitive fields excluded: '.(count($safeData))." fields remaining\n\n";

// Filter by type
$numericFields = $extracted->numericFields();
echo 'Numeric fields only ('.count($numericFields)." fields):\n";
foreach ($numericFields->toArray() as $key => $value) {
    echo "  • {$key}: {$value}\n";
}
echo "\n";

$stringFields = $extracted->stringFields();
echo 'String fields only ('.count($stringFields)." fields):\n";
foreach ($stringFields->toArray() as $key => $value) {
    echo "  • {$key}: {$value}\n";
}
echo "\n";

$booleanFields = $extracted->booleanFields();
echo 'Boolean fields only ('.count($booleanFields)." fields):\n";
foreach ($booleanFields->toArray() as $key => $value) {
    echo "  • {$key}: ".(var_export($value, true))."\n";
}
echo "\n";

// Pattern matching
$loanFields = $extracted->whereKeyMatches('/loan/');
echo "Fields matching '/loan/' pattern:\n";
foreach ($loanFields->toArray() as $key => $value) {
    echo "  • {$key}: {$value}\n";
}
echo "\n";

// ============================================================================
// STEP 3: Data Transformation
// ============================================================================

echo "🔄 STEP 3: Data Transformation\n";
echo str_repeat('-', 70)."\n\n";

// Round numeric values
$rounded = $extracted->transform(function ($value, $key) {
    if (is_numeric($value) && ! is_int($value)) {
        return round($value);
    }

    return $value;
});

echo "Rounded numeric values:\n";
echo "  Original income: {$extracted->annual_income}\n";
echo "  Rounded income: {$rounded->annual_income}\n";
echo "  Original credit_score: {$extracted->credit_score}\n";
echo "  Rounded credit_score: {$rounded->credit_score}\n\n";

// Normalize strings
$normalized = $extracted->transform(function ($value, $key) {
    if (is_string($value)) {
        return strtoupper($value);
    }

    return $value;
});

echo "Uppercase strings:\n";
echo "  Original name: {$extracted->name}\n";
echo "  Normalized name: {$normalized->name}\n";
echo "  Original employment: {$extracted->employment_status}\n";
echo "  Normalized employment: {$normalized->employment_status}\n\n";

// ============================================================================
// STEP 4: Method Chaining
// ============================================================================

echo "⛓️  STEP 4: Method Chaining\n";
echo str_repeat('-', 70)."\n\n";

// Chain multiple operations
$processedData = $extracted
    ->except(['ssn', 'account_number'])  // Remove sensitive fields
    ->numericFields()                      // Keep only numbers
    ->transform(fn ($v) => round($v))     // Round all values
    ->filter(fn ($v) => $v > 0);          // Keep positive values only

echo 'Chained operations result ('.count($processedData)." fields):\n";
foreach ($processedData->toArray() as $key => $value) {
    echo "  • {$key}: {$value}\n";
}
echo "\n";

// ============================================================================
// STEP 5: Integration with Eligify Evaluation
// ============================================================================

echo "⚖️  STEP 5: Integration with Eligify Evaluation\n";
echo str_repeat('-', 70)."\n\n";

// Create criteria
$criteria = Eligify::criteria('extracted_data_evaluation')
    ->description('Demonstrate Snapshot integration')
    ->addRule('annual_income', '>=', 50000, 30)
    ->addRule('credit_score', '>=', 680, 40)
    ->addRule('active_loans', '<=', 3, 20)
    ->addRule('is_verified', '=', true, 10)
    ->passThreshold(70)
    ->save();

echo "✓ Criteria created with 4 rules\n\n";

// Evaluate using Snapshot directly
echo "Evaluating with Snapshot object:\n";
$result1 = app('eligify')->evaluate($criteria->getCriteria(), $extracted);
echo '  Result: '.($result1['passed'] ? '✅ PASSED' : '❌ FAILED')."\n";
echo "  Score: {$result1['score']}%\n\n";

// Evaluate using filtered data
echo "Evaluating with filtered data (financial fields only):\n";
$result2 = app('eligify')->evaluate($criteria->getCriteria(), $financialData);
echo '  Result: '.($result2['passed'] ? '✅ PASSED' : '❌ FAILED')."\n";
echo "  Score: {$result2['score']}%\n\n";

// Evaluate using transformed data
echo "Evaluating with transformed data (rounded values):\n";
$result3 = app('eligify')->evaluate($criteria->getCriteria(), $rounded);
echo '  Result: '.($result3['passed'] ? '✅ PASSED' : '❌ FAILED')."\n";
echo "  Score: {$result3['score']}%\n\n";

// Also works with plain arrays (backward compatibility)
echo "Evaluating with plain array (backward compatibility):\n";
$result4 = app('eligify')->evaluate($criteria->getCriteria(), $rawData);
echo '  Result: '.($result4['passed'] ? '✅ PASSED' : '❌ FAILED')."\n";
echo "  Score: {$result4['score']}%\n\n";

// ============================================================================
// STEP 6: Immutability Demonstration
// ============================================================================

echo "🔒 STEP 6: Immutability\n";
echo str_repeat('-', 70)."\n\n";

echo "Snapshot is immutable - transformations create new instances:\n";
echo '  Original count: '.count($extracted)."\n";
echo '  Filtered count: '.count($safeData)."\n";
echo '  Original still has '.count($extracted)." fields\n\n";

echo "Attempting to modify data will throw an exception:\n";
try {
    $extracted->income = 100000;
    echo "  ❌ Modification succeeded (this shouldn't happen!)\n";
} catch (\BadMethodCallException $e) {
    echo '  ✅ Caught exception: '.$e->getMessage()."\n";
}
echo "\n";

// ============================================================================
// STEP 7: JSON Export and Serialization
// ============================================================================

echo "📄 STEP 7: JSON Export and Serialization\n";
echo str_repeat('-', 70)."\n\n";

// Export as JSON
$json = $extracted->toJson(JSON_PRETTY_PRINT);
echo "Exported as JSON:\n";
echo substr($json, 0, 300)."...\n\n";

// Export as array for legacy code
$array = $extracted->toArray();
echo 'Exported as array: '.count($array)." fields\n";
echo '  Keys: '.implode(', ', array_keys($array))."\n\n";

// ============================================================================
// STEP 8: Real-World Use Case
// ============================================================================

echo "💼 STEP 8: Real-World Use Case - Multi-Stage Evaluation\n";
echo str_repeat('-', 70)."\n\n";

// Stage 1: Pre-screening (quick checks)
echo "Stage 1: Pre-screening...\n";
$preScreening = $extracted->only(['credit_score', 'is_verified']);
echo '  Checking '.count($preScreening)." critical fields\n";

$quickCriteria = Eligify::criteria('quick_prescreen')
    ->description('Fast pre-screening checks')
    ->addRule('credit_score', '>=', 600, 50)
    ->addRule('is_verified', '=', true, 50)
    ->passThreshold(100)
    ->save();

$preScreenResult = app('eligify')->evaluate($quickCriteria->getCriteria(), $preScreening);
echo '  Pre-screen: '.($preScreenResult['passed'] ? '✅ PASSED' : '❌ FAILED')." (Score: {$preScreenResult['score']}%)\n\n";

// Stage 2: Detailed evaluation (if pre-screen passed)
if ($preScreenResult['passed']) {
    echo "Stage 2: Detailed evaluation...\n";
    $detailedData = $extracted
        ->except(['ssn', 'account_number', 'name', 'email'])
        ->numericFields();

    echo '  Evaluating '.count($detailedData)." financial metrics\n";

    $detailedResult = app('eligify')->evaluate($criteria->getCriteria(), $detailedData);
    echo '  Detailed eval: '.($detailedResult['passed'] ? '✅ PASSED' : '❌ FAILED')." (Score: {$detailedResult['score']}%)\n\n";
}

// ============================================================================
// Summary
// ============================================================================

echo str_repeat('=', 70)."\n";
echo "  SUMMARY: Snapshot Benefits\n";
echo str_repeat('=', 70)."\n\n";

echo "✅ Benefits Demonstrated:\n";
echo "   • Type-safe data container with IDE support\n";
echo "   • Multiple access patterns (property, array, get method)\n";
echo "   • Rich filtering and transformation API\n";
echo "   • Metadata tracking for debugging and audit\n";
echo "   • Method chaining for clean code\n";
echo "   • Immutability prevents accidental modifications\n";
echo "   • Seamless integration with rule evaluation\n";
echo "   • Backward compatible with plain arrays\n";
echo "   • Specialized filters (numeric, string, boolean, pattern)\n";
echo "   • JSON serialization with metadata\n\n";

echo "💡 Use Snapshot when:\n";
echo "   • Extracting data from Eloquent models\n";
echo "   • Building complex evaluation pipelines\n";
echo "   • Need data transformation/filtering\n";
echo "   • Type safety is important\n";
echo "   • Building APIs that return evaluation data\n\n";

echo "💡 Use plain arrays when:\n";
echo "   • Quick one-off evaluations\n";
echo "   • Simple test cases\n";
echo "   • Legacy code integration\n";
echo "   • Performance is absolutely critical\n\n";

echo "✨ Example completed successfully!\n\n";
