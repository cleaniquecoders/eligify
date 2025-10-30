<?php

/**
 * Example: Using Model Mappings with Eligify
 *
 * This example demonstrates how to generate and use model mappings
 * for automatic data extraction in eligibility evaluations.
 */

require_once __DIR__.'/bootstrap.php';

use CleaniqueCoders\Eligify\Eligify;
use CleaniqueCoders\Eligify\Support\Extractor;

echo "=== Model Mapping Example ===\n\n";

// Step 1: Generate a model mapping (run this command first)
echo "Step 1: Generate Model Mapping\n";
echo "-------------------------------\n";
echo "Run this command to generate a mapping for your User model:\n\n";
echo "  php artisan eligify:make-mapping \"App\\Models\\User\"\n\n";
echo "This creates: app/Eligify/Mappings/UserMapping.php\n\n";

// Step 2: Example of what gets generated
echo "Step 2: Generated Mapping Class\n";
echo "--------------------------------\n";
echo "The command generates a class like this:\n\n";
echo <<<'PHP'
class UserMapping extends AbstractModelMapping
{
    protected array $fieldMappings = [
        'created_at' => 'registration_date',
        'email_verified_at' => 'email_verified_timestamp',
    ];

    protected array $relationshipMappings = [
        'orders.count' => 'orders_count',
        'orders.sum:total' => 'lifetime_value',
    ];

    protected array $computedFields = [
        'is_verified' => null,
        'is_premium' => null,
    ];

    public function __construct()
    {
        $this->computedFields = [
            'is_verified' => fn ($model) => !is_null($model->email_verified_at),
            'is_premium' => fn ($model) => $model->subscription_tier === 'premium',
        ];
    }
}

PHP;
echo "\n";

// Step 3: Register the mapping in config
echo "Step 3: Register Mapping in Config\n";
echo "-----------------------------------\n";
echo "Add to config/eligify.php:\n\n";
echo <<<'PHP'
'model_mappings' => [
    'App\Models\User' => App\Eligify\Mappings\UserMapping::class,
],

PHP;
echo "\n";

// Step 4: Use in eligibility evaluation
echo "Step 4: Use in Eligibility Evaluation\n";
echo "--------------------------------------\n\n";

// Create a mock user for demonstration
$mockUser = (object) [
    'id' => 1,
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'email_verified_at' => now(),
    'created_at' => now()->subMonths(6),
    'subscription_tier' => 'premium',
];

echo "Mock User Data:\n";
echo json_encode($mockUser, JSON_PRETTY_PRINT)."\n\n";

// Define criteria using mapped fields
$criteria = Eligify::criteria('premium-feature-access')
    ->description('Access to premium features')
    ->addRule('is_verified', '=', true)
    ->addRule('is_premium', '=', true)
    ->addRule('orders_count', '>=', 1)
    ->build();

echo "Criteria Rules (using mapped field names):\n";
foreach ($criteria->rules as $rule) {
    echo sprintf("  - %s %s %s\n", $rule->field, $rule->operator, $rule->value);
}
echo "\n";

// Step 5: Automatic data extraction
echo "Step 5: Automatic Data Extraction\n";
echo "----------------------------------\n\n";

// In a real scenario, the extractor would use the registered mapping
echo "The Extractor automatically:\n";
echo "  1. Applies field mappings (created_at → registration_date)\n";
echo "  2. Calculates computed fields (is_verified, is_premium)\n";
echo "  3. Aggregates relationships (orders.count → orders_count)\n\n";

// Manual extraction for demonstration
$extractor = new Extractor($mockUser);

// Apply field mapping
$extractor->mapField('created_at', 'registration_date');
$extractor->mapField('email_verified_at', 'email_verified_timestamp');

// Add computed fields
$extractor->addComputedField('is_verified', function ($model) {
    return ! is_null($model->email_verified_at ?? null);
});

$extractor->addComputedField('is_premium', function ($model) {
    return ($model->subscription_tier ?? null) === 'premium';
});

// Mock relationship data
$extractor->addComputedField('orders_count', fn () => 5);
$extractor->addComputedField('lifetime_value', fn () => 1250.00);

$extracted = $extractor->extract();

echo "Extracted Data:\n";
echo json_encode($extracted, JSON_PRETTY_PRINT)."\n\n";

// Step 6: Evaluate eligibility
echo "Step 6: Evaluate Eligibility\n";
echo "-----------------------------\n\n";

// Mock evaluation (in real usage, Eligify handles this)
$result = [
    'passed' => $extracted['is_verified'] &&
                $extracted['is_premium'] &&
                $extracted['orders_count'] >= 1,
    'score' => 100,
    'failed_rules' => [],
    'message' => 'User is eligible for premium features',
];

echo "Evaluation Result:\n";
echo json_encode($result, JSON_PRETTY_PRINT)."\n\n";

// Step 7: Benefits of Model Mappings
echo "Step 7: Benefits of Model Mappings\n";
echo "-----------------------------------\n\n";
echo "✓ Decouple eligibility rules from database schema\n";
echo "✓ Use business-friendly field names in rules\n";
echo "✓ Centralize data transformation logic\n";
echo "✓ Easily update mappings without changing rules\n";
echo "✓ Reuse mappings across multiple criteria\n\n";

// Step 8: Advanced patterns
echo "Step 8: Advanced Mapping Patterns\n";
echo "----------------------------------\n\n";

echo "Custom aggregations:\n";
echo <<<'PHP'
'orders.where:status,completed.sum:total' => 'completed_order_value',
'reviews.avg:rating' => 'average_rating',
'posts.where:published,true.count' => 'published_posts_count',

PHP;
echo "\n";

echo "Nested relationships:\n";
echo <<<'PHP'
'company.employees.count' => 'company_size',
'team.members.where:active,true.count' => 'active_team_members',

PHP;
echo "\n";

echo "Complex computed fields:\n";
echo <<<'PHP'
'credit_tier' => fn ($model) => match(true) {
    $model->credit_score >= 750 => 'excellent',
    $model->credit_score >= 650 => 'good',
    $model->credit_score >= 550 => 'fair',
    default => 'poor',
},

PHP;
echo "\n";

echo "=== Example Complete ===\n";
