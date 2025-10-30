<?php

/**
 * Example 16: Model Mapping Generation
 *
 * Demonstrates the three new features:
 * 1. Automatic prefix generation for each mapper
 * 2. Bulk generation from app/Models directory
 * 3. Relationship detection with existing mapper usage
 */

require_once __DIR__.'/bootstrap.php';

use CleaniqueCoders\Eligify\Data\Mappings\UserModelMapping;

echo "=== Model Mapping Generation Examples ===\n\n";

// Feature 1: Prefix Generation
echo "1. Automatic Prefix Generation\n";
echo str_repeat('-', 50)."\n";

$userMapping = new UserModelMapping;
echo "UserMapping prefix: '{$userMapping->getPrefix()}'\n";
echo "This allows field references like: {$userMapping->getPrefix()}.name, {$userMapping->getPrefix()}.email\n\n";

// Demonstrate multi-word model names
$mockMapping = new class extends \CleaniqueCoders\Eligify\Data\Mappings\AbstractModelMapping
{
    public function getModelClass(): string
    {
        return 'App\Models\LoanApplication';
    }

    public function getName(): string
    {
        return 'Loan Application';
    }

    public function getDescription(): string
    {
        return 'Test mapping for loan applications';
    }
};

echo "LoanApplicationMapping prefix: '{$mockMapping->getPrefix()}'\n";
echo "Field references: {$mockMapping->getPrefix()}.amount, {$mockMapping->getPrefix()}.status\n\n";

// Feature 2: Bulk Generation Command
echo "\n2. Bulk Mapping Generation Command\n";
echo str_repeat('-', 50)."\n";
echo "To generate mappings for all models:\n";
echo "  php artisan eligify:make-all-mappings\n\n";
echo "Options:\n";
echo "  --path=app/Models              Specify directory to scan\n";
echo "  --namespace=App\\Models         Specify model namespace\n";
echo "  --force                         Overwrite existing mappings\n";
echo "  --dry-run                       Preview without creating files\n\n";

echo "Examples:\n";
echo "  # Generate from default app/Models\n";
echo "  php artisan eligify:make-all-mappings\n\n";
echo "  # Preview what would be generated\n";
echo "  php artisan eligify:make-all-mappings --dry-run\n\n";
echo "  # Custom path for module-based structure\n";
echo "  php artisan eligify:make-all-mappings --path=modules/User/Models --namespace=Modules\\\\User\\\\Models\n\n";

// Feature 3: Relationship Detection
echo "\n3. Relationship Mapper Detection\n";
echo str_repeat('-', 50)."\n";
echo "When generating a mapping, related models are checked for existing mappings.\n\n";

echo "Example scenario:\n";
echo "  Model: Application (has 'user' relationship)\n";
echo "  Related: User (has UserMapping with prefix 'user')\n\n";

echo "Generated mapping will include:\n";
echo "  protected array \$relationshipMappings = [\n";
echo "      'user.count' => 'user_count',\n";
echo "      // user uses User which has UserMapping\n";
echo "      // You can reference fields like: user.user.field_name\n";
echo "  ];\n\n";

echo "Usage in rules:\n";
echo "  Eligify::criteria('application_review')\n";
echo "      ->addRule('application.amount', '<=', 50000)\n";
echo "      ->addRule('application.user.is_verified', '=', true)  // Via UserMapping\n";
echo "      ->addRule('application.user.email', 'contains', '@company.com')\n";
echo "      ->evaluate(\$application);\n\n";

// Practical Example
echo "\n4. Practical Example: Loan Application System\n";
echo str_repeat('-', 50)."\n";
echo "Directory structure:\n";
echo "  app/Models/\n";
echo "    ├── User.php\n";
echo "    ├── Applicant.php\n";
echo "    ├── LoanApplication.php\n";
echo "    └── CreditReport.php\n\n";

echo "Step 1: Generate all mappings\n";
echo "  php artisan eligify:make-all-mappings\n\n";

echo "Step 2: Generated mappings with prefixes\n";
echo "  app/Eligify/Mappings/\n";
echo "    ├── UserMapping.php          (prefix: 'user')\n";
echo "    ├── ApplicantMapping.php     (prefix: 'applicant')\n";
echo "    ├── LoanApplicationMapping.php (prefix: 'loan.application')\n";
echo "    └── CreditReportMapping.php  (prefix: 'credit.report')\n\n";

echo "Step 3: Use in eligibility rules\n";
echo "  Eligify::criteria('loan_approval')\n";
echo "      // Applicant fields\n";
echo "      ->addRule('applicant.income', '>=', 3000)\n";
echo "      ->addRule('applicant.employment_years', '>=', 2)\n";
echo "      \n";
echo "      // User relationship fields (via UserMapping)\n";
echo "      ->addRule('applicant.user.is_verified', '=', true)\n";
echo "      ->addRule('applicant.user.email_verified_timestamp', '!=', null)\n";
echo "      \n";
echo "      // Credit report relationship fields\n";
echo "      ->addRule('applicant.credit_report.score', '>=', 650)\n";
echo "      ->addRule('applicant.credit_report.delinquencies_count', '=', 0)\n";
echo "      \n";
echo "      ->evaluate(\$applicant);\n\n";

echo "=== Benefits ===\n";
echo "✓ Type-safe field references with prefixes\n";
echo "✓ Automatic detection of model relationships\n";
echo "✓ Reuse existing mappings for related models\n";
echo "✓ Clear namespace separation (applicant.field vs user.field)\n";
echo "✓ Bulk generation saves time for large codebases\n";
