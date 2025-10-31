<?php

namespace Workbench\Database\Seeders;

use Carbon\CarbonImmutable;
use CleaniqueCoders\Eligify\Enums\FieldType;
use CleaniqueCoders\Eligify\Enums\RuleOperator;
use CleaniqueCoders\Eligify\Enums\RulePriority;
use CleaniqueCoders\Eligify\Enums\ScoringMethod;
use CleaniqueCoders\Eligify\Models\AuditLog;
use CleaniqueCoders\Eligify\Models\Criteria;
use CleaniqueCoders\Eligify\Models\Evaluation;
use CleaniqueCoders\Eligify\Models\Rule;
use Illuminate\Database\Seeder;
use Workbench\App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Workbench\Database\Factories\UserFactory;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // UserFactory::new()->times(10)->create();

        if (! User::where('email', 'test@example.com')->exists()) {
            UserFactory::new()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        }

        // Seed sample Eligify criteria based on docs/13-examples
        $examples = [
            [
                'name' => 'Loan Approval',
                'slug' => 'loan_approval',
                'description' => 'Personal loan approval based on income and credit score.',
                'type' => 'finance',
                'group' => 'basic',
                'category' => 'loan',
                'tags' => ['basic', 'finance', 'loan', 'approval'],
            ],
            [
                'name' => 'Scholarship Eligibility',
                'slug' => 'scholarship',
                'description' => 'Student scholarship eligibility based on GPA and financial need.',
                'type' => 'education',
                'group' => 'basic',
                'category' => 'scholarship',
                'tags' => ['basic', 'education', 'scholarship'],
            ],
            [
                'name' => 'Job Screening',
                'slug' => 'job_screening',
                'description' => 'Basic job applicant screening by experience and skills.',
                'type' => 'hr',
                'group' => 'basic',
                'category' => 'recruitment',
                'tags' => ['basic', 'hr', 'job', 'screening'],
            ],
            [
                'name' => 'VIP Discount',
                'slug' => 'vip_discount',
                'description' => 'E-commerce discount eligibility for loyal customers.',
                'type' => 'ecommerce',
                'group' => 'intermediate',
                'category' => 'discount',
                'tags' => ['intermediate', 'ecommerce', 'discount', 'loyalty'],
            ],
            [
                'name' => 'Government Aid',
                'slug' => 'government_aid',
                'description' => 'Eligibility for government aid programs based on income and dependents.',
                'type' => 'government',
                'group' => 'intermediate',
                'category' => 'aid',
                'tags' => ['intermediate', 'government', 'aid'],
            ],
            [
                'name' => 'Insurance Qualification',
                'slug' => 'insurance',
                'description' => 'Eligibility for insurance products using risk and profile data.',
                'type' => 'insurance',
                'group' => 'intermediate',
                'category' => 'policy',
                'tags' => ['intermediate', 'insurance', 'risk'],
            ],
            [
                'name' => 'Credit Card Approval',
                'slug' => 'credit_card_approval',
                'description' => 'Credit card approval using weighted scoring across multiple factors.',
                'type' => 'finance',
                'group' => 'advanced',
                'category' => 'credit-card',
                'tags' => ['advanced', 'finance', 'credit', 'weighted'],
            ],
            [
                'name' => 'Membership Tiers',
                'slug' => 'membership_tiers',
                'description' => 'Tier upgrades based on usage and revenue milestones.',
                'type' => 'saas',
                'group' => 'advanced',
                'category' => 'membership',
                'tags' => ['advanced', 'saas', 'membership', 'tiers'],
            ],
            [
                'name' => 'Rental Screening',
                'slug' => 'rental_screening',
                'description' => 'Tenant screening based on income, credit, and history.',
                'type' => 'real-estate',
                'group' => 'advanced',
                'category' => 'rental',
                'tags' => ['advanced', 'real-estate', 'rental', 'screening'],
            ],
            [
                'name' => 'SaaS Upgrade',
                'slug' => 'saas_upgrade',
                'description' => 'Eligibility to upgrade plans based on usage limits and payment history.',
                'type' => 'saas',
                'group' => 'advanced',
                'category' => 'upgrade',
                'tags' => ['advanced', 'saas', 'upgrade'],
            ],
            [
                'name' => 'Complex Workflows',
                'slug' => 'complex_workflows',
                'description' => 'Multi-step evaluations with dependent criteria in real-world flows.',
                'type' => 'platform',
                'group' => 'real-world',
                'category' => 'workflow',
                'tags' => ['real-world', 'workflow', 'complex'],
            ],
            [
                'name' => 'High Traffic',
                'slug' => 'high_traffic',
                'description' => 'High-throughput eligibility checks with performance tuning.',
                'type' => 'platform',
                'group' => 'real-world',
                'category' => 'performance',
                'tags' => ['real-world', 'performance', 'scalability'],
            ],
            [
                'name' => 'Multi-tenant',
                'slug' => 'multi_tenant',
                'description' => 'Tenant-aware criteria and isolation for SaaS platforms.',
                'type' => 'saas',
                'group' => 'real-world',
                'category' => 'multi-tenant',
                'tags' => ['real-world', 'saas', 'multi-tenant'],
            ],
        ];

        $created = [];
        // Define scoring method per criteria (default percentage)
        $scoringBySlug = [
            'insurance' => ScoringMethod::WEIGHTED,
            'credit_card_approval' => ScoringMethod::WEIGHTED,
        ];

        foreach ($examples as $ex) {
            $scoring = $scoringBySlug[$ex['slug']] ?? ScoringMethod::PERCENTAGE;
            $criteria = Criteria::query()->create([
                'name' => $ex['name'],
                'slug' => $ex['slug'],
                'description' => $ex['description'],
                'is_active' => true,
                'type' => $ex['type'],
                'group' => $ex['group'],
                'category' => $ex['category'],
                'tags' => $ex['tags'],
                'meta' => [
                    'scoring_method' => $scoring->value,
                    // Optional: set pass threshold for weighted examples
                    ...match ($ex['slug']) {
                        'credit_card_approval' => ['pass_threshold' => 75],
                        default => [],
                    },
                ],
            ]);
            $created[$ex['slug']] = $criteria;

            // Seed rules via dedicated method per criteria
            $method = 'seed'.str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $ex['slug'])));
            if (method_exists($this, $method)) {
                $this->{$method}($criteria);
            }
        }
        // Rules are seeded via methods above; continue with a demo evaluation/audit

        // Add a sample evaluation/audit to the Loan Approval criteria for demo
        $loan = $created['loan_approval'] ?? array_values($created)[0];

        Evaluation::query()->create([
            'criteria_id' => $loan->id,
            'evaluable_type' => 'App\\Models\\User',
            'evaluable_id' => 1,
            'slug' => 'demo_eval',
            'passed' => true,
            'score' => 85.5,
            'failed_rules' => [],
            'rule_results' => [],
            'decision' => 'Approved',
            'context' => [],
            'meta' => [],
            'evaluated_at' => CarbonImmutable::now(),
        ]);

        AuditLog::query()->create([
            'event' => 'criteria_created',
            'auditable_type' => Criteria::class,
            'auditable_id' => $loan->id,
            'slug' => 'criteria_created_'.$loan->id,
            'old_values' => null,
            'new_values' => ['name' => $loan->name],
            'context' => [],
            'user_type' => null,
            'user_id' => null,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'workbench',
            'meta' => [],
        ]);
    }

    /**
     * Helper to add a rule with proper value shape based on operator.
     */
    private function addRule(
        Criteria $criteria,
        int $order,
        string $field,
        RuleOperator $operator,
        mixed $value,
        ?float $weight = null,
        ?RulePriority $priority = null,
        ?FieldType $fieldType = null
    ): void {
        // Normalize value: arrays only for IN/NOT_IN/BETWEEN/NOT_BETWEEN
        if ($operator->requiresMultipleValues()) {
            $normalized = is_array($value) ? array_values($value) : [$value];
        } else {
            // Use scalar; if array given, pick first
            $normalized = is_array($value) ? (array_values($value)[0] ?? null) : $value;
        }

        Rule::query()->create([
            'criteria_id' => $criteria->id,
            'field' => $field,
            'operator' => $operator->value,
            'value' => $normalized,
            'weight' => $weight ?? ($priority?->getWeight()),
            'order' => $order,
            'is_active' => true,
            'meta' => array_filter([
                'priority' => $priority?->value,
                'field_type' => $fieldType?->value,
            ]),
        ]);
    }

    private function seedLoanApproval(Criteria $criteria): void
    {
        $i = 1;
        $this->addRule($criteria, $i++, 'income', RuleOperator::GREATER_THAN_OR_EQUAL, 3000, null, RulePriority::HIGH, FieldType::NUMERIC);
        $this->addRule($criteria, $i++, 'credit_score', RuleOperator::GREATER_THAN_OR_EQUAL, 650, null, RulePriority::HIGH, FieldType::INTEGER);
    }

    private function seedScholarship(Criteria $criteria): void
    {
        $i = 1;
        $this->addRule($criteria, $i++, 'gpa', RuleOperator::GREATER_THAN_OR_EQUAL, 3.5, null, RulePriority::HIGH, FieldType::NUMERIC);
        $this->addRule($criteria, $i++, 'family_income', RuleOperator::LESS_THAN_OR_EQUAL, 60000, null, RulePriority::HIGH, FieldType::NUMERIC);
        $this->addRule($criteria, $i++, 'enrollment_status', RuleOperator::EQUAL, 'full-time', null, RulePriority::MEDIUM, FieldType::STRING);
        $this->addRule($criteria, $i++, 'completed_years', RuleOperator::GREATER_THAN_OR_EQUAL, 1, null, RulePriority::MEDIUM, FieldType::INTEGER);
    }

    private function seedJobScreening(Criteria $criteria): void
    {
        $i = 1;
        $this->addRule($criteria, $i++, 'experience_years', RuleOperator::GREATER_THAN_OR_EQUAL, 2, null, RulePriority::MEDIUM, FieldType::INTEGER);
        $this->addRule($criteria, $i++, 'skills', RuleOperator::IN, ['php', 'laravel'], null, RulePriority::LOW, FieldType::ARRAY);
    }

    private function seedVipDiscount(Criteria $criteria): void
    {
        $i = 1;
        $this->addRule($criteria, $i++, 'total_purchases', RuleOperator::GREATER_THAN_OR_EQUAL, 1000, null, RulePriority::HIGH, FieldType::NUMERIC);
        $this->addRule($criteria, $i++, 'loyalty_tier', RuleOperator::IN, ['gold', 'platinum'], null, RulePriority::MEDIUM, FieldType::STRING);
        $this->addRule($criteria, $i++, 'account_age_months', RuleOperator::GREATER_THAN_OR_EQUAL, 6, null, RulePriority::MEDIUM, FieldType::INTEGER);
        $this->addRule($criteria, $i++, 'reviews_count', RuleOperator::GREATER_THAN_OR_EQUAL, 5, null, RulePriority::LOW, FieldType::INTEGER);
    }

    private function seedGovernmentAid(Criteria $criteria): void
    {
        $i = 1;
        $this->addRule($criteria, $i++, 'annual_income', RuleOperator::LESS_THAN_OR_EQUAL, 25000, null, RulePriority::CRITICAL, FieldType::NUMERIC);
        $this->addRule($criteria, $i++, 'family_size', RuleOperator::GREATER_THAN_OR_EQUAL, 3, null, RulePriority::HIGH, FieldType::INTEGER);
        $this->addRule($criteria, $i++, 'citizenship_status', RuleOperator::EQUAL, 'citizen', null, RulePriority::HIGH, FieldType::STRING);
        $this->addRule($criteria, $i++, 'has_dependents', RuleOperator::EQUAL, true, null, RulePriority::MEDIUM, FieldType::BOOLEAN);
        $this->addRule($criteria, $i++, 'employment_status', RuleOperator::IN, ['unemployed', 'part-time'], null, RulePriority::MEDIUM, FieldType::STRING);
    }

    private function seedInsurance(Criteria $criteria): void
    {
        $i = 1;
        $this->addRule($criteria, $i++, 'age', RuleOperator::BETWEEN, [18, 65], 0.2, RulePriority::MEDIUM, FieldType::INTEGER);
        $this->addRule($criteria, $i++, 'smoker', RuleOperator::EQUAL, false, 0.3, RulePriority::HIGH, FieldType::BOOLEAN);
        $this->addRule($criteria, $i++, 'bmi', RuleOperator::LESS_THAN_OR_EQUAL, 30, 0.25, RulePriority::HIGH, FieldType::NUMERIC);
        $this->addRule($criteria, $i++, 'pre_existing_conditions', RuleOperator::EQUAL, 0, 0.25, RulePriority::HIGH, FieldType::INTEGER);
    }

    private function seedCreditCardApproval(Criteria $criteria): void
    {
        $i = 1;
        $this->addRule($criteria, $i++, 'credit_score', RuleOperator::GREATER_THAN_OR_EQUAL, 700, 0.35, RulePriority::HIGH, FieldType::INTEGER);
        $this->addRule($criteria, $i++, 'annual_income', RuleOperator::GREATER_THAN_OR_EQUAL, 40000, 0.25, RulePriority::HIGH, FieldType::NUMERIC);
        $this->addRule($criteria, $i++, 'debt_to_income_ratio', RuleOperator::LESS_THAN_OR_EQUAL, 0.35, 0.20, RulePriority::HIGH, FieldType::NUMERIC);
        $this->addRule($criteria, $i++, 'employment_length_months', RuleOperator::GREATER_THAN_OR_EQUAL, 12, 0.10, RulePriority::MEDIUM, FieldType::INTEGER);
        $this->addRule($criteria, $i++, 'recent_inquiries', RuleOperator::LESS_THAN_OR_EQUAL, 3, 0.10, RulePriority::LOW, FieldType::INTEGER);
    }

    private function seedMembershipTiers(Criteria $criteria): void
    {
        $i = 1;
        $this->addRule($criteria, $i++, 'annual_spend', RuleOperator::GREATER_THAN_OR_EQUAL, 1000, null, RulePriority::MEDIUM, FieldType::NUMERIC);
        $this->addRule($criteria, $i++, 'referrals_count', RuleOperator::GREATER_THAN_OR_EQUAL, 2, null, RulePriority::LOW, FieldType::INTEGER);
        $this->addRule($criteria, $i++, 'account_age_months', RuleOperator::GREATER_THAN_OR_EQUAL, 3, null, RulePriority::LOW, FieldType::INTEGER);
    }

    private function seedRentalScreening(Criteria $criteria): void
    {
        $i = 1;
        $this->addRule($criteria, $i++, 'monthly_income', RuleOperator::GREATER_THAN_OR_EQUAL, 4500, null, RulePriority::HIGH, FieldType::NUMERIC);
        $this->addRule($criteria, $i++, 'credit_score', RuleOperator::GREATER_THAN_OR_EQUAL, 650, null, RulePriority::HIGH, FieldType::INTEGER);
        $this->addRule($criteria, $i++, 'eviction_history_count', RuleOperator::EQUAL, 0, null, RulePriority::CRITICAL, FieldType::INTEGER);
        $this->addRule($criteria, $i++, 'criminal_record', RuleOperator::EQUAL, false, null, RulePriority::CRITICAL, FieldType::BOOLEAN);
        $this->addRule($criteria, $i++, 'employment_verified', RuleOperator::EQUAL, true, null, RulePriority::HIGH, FieldType::BOOLEAN);
        $this->addRule($criteria, $i++, 'previous_landlord_reference', RuleOperator::GREATER_THAN_OR_EQUAL, 4.0, null, RulePriority::MEDIUM, FieldType::NUMERIC);
    }

    private function seedSaasUpgrade(Criteria $criteria): void
    {
        $i = 1;
        $this->addRule($criteria, $i++, 'monthly_active_users', RuleOperator::GREATER_THAN_OR_EQUAL, 1000, null, RulePriority::HIGH, FieldType::INTEGER);
        $this->addRule($criteria, $i++, 'api_calls_per_day', RuleOperator::GREATER_THAN_OR_EQUAL, 10000, null, RulePriority::HIGH, FieldType::INTEGER);
        $this->addRule($criteria, $i++, 'team_size', RuleOperator::GREATER_THAN_OR_EQUAL, 10, null, RulePriority::MEDIUM, FieldType::INTEGER);
        $this->addRule($criteria, $i++, 'feature_usage_rate', RuleOperator::GREATER_THAN_OR_EQUAL, 0.8, null, RulePriority::MEDIUM, FieldType::NUMERIC);
        $this->addRule($criteria, $i++, 'support_tickets_per_month', RuleOperator::GREATER_THAN_OR_EQUAL, 5, null, RulePriority::LOW, FieldType::INTEGER);
    }

    private function seedComplexWorkflows(Criteria $criteria): void
    {
        $i = 1;
        $this->addRule($criteria, $i++, 'age', RuleOperator::BETWEEN, [18, 65], null, RulePriority::MEDIUM, FieldType::INTEGER);
        $this->addRule($criteria, $i++, 'citizenship', RuleOperator::EQUAL, 'US', null, RulePriority::HIGH, FieldType::STRING);
        $this->addRule($criteria, $i++, 'income', RuleOperator::GREATER_THAN_OR_EQUAL, 3000, null, RulePriority::HIGH, FieldType::NUMERIC);
        $this->addRule($criteria, $i++, 'debt_ratio', RuleOperator::LESS_THAN_OR_EQUAL, 0.4, null, RulePriority::HIGH, FieldType::NUMERIC);
        $this->addRule($criteria, $i++, 'employment_months', RuleOperator::GREATER_THAN_OR_EQUAL, 12, null, RulePriority::MEDIUM, FieldType::INTEGER);
        $this->addRule($criteria, $i++, 'credit_score', RuleOperator::GREATER_THAN_OR_EQUAL, 650, null, RulePriority::HIGH, FieldType::INTEGER);
        $this->addRule($criteria, $i++, 'delinquencies', RuleOperator::EQUAL, 0, null, RulePriority::MEDIUM, FieldType::INTEGER);
        $this->addRule($criteria, $i++, 'bankruptcies', RuleOperator::EQUAL, 0, null, RulePriority::MEDIUM, FieldType::INTEGER);
    }

    private function seedHighTraffic(Criteria $criteria): void
    {
        $i = 1;
        $this->addRule($criteria, $i++, 'complex_calculation', RuleOperator::GREATER_THAN_OR_EQUAL, 100, null, RulePriority::INFO, FieldType::NUMERIC);
    }

    private function seedMultiTenant(Criteria $criteria): void
    {
        $i = 1;
        $this->addRule($criteria, $i++, 'income', RuleOperator::GREATER_THAN_OR_EQUAL, 3000, null, RulePriority::MEDIUM, FieldType::NUMERIC);
    }
}
