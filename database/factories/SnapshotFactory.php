<?php

namespace CleaniqueCoders\Eligify\Database\Factories;

use CleaniqueCoders\Eligify\Models\Snapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

class SnapshotFactory extends Factory
{
    protected $model = Snapshot::class;

    public function definition(): array
    {
        $snapshotableType = fake()->randomElement(['App\\Models\\User', 'App\\Models\\Application', 'App\\Models\\LoanApplication']);
        $snapshotableId = fake()->numberBetween(1, 1000);

        $data = $this->generateSampleData();

        return [
            'uuid' => fake()->uuid(),
            'snapshotable_type' => $snapshotableType,
            'snapshotable_id' => $snapshotableId,
            'data' => $data,
            'checksum' => hash('sha256', json_encode($data, JSON_SORT_KEYS)),
            'meta' => [
                'source' => fake()->randomElement(['manual', 'automatic', 'api']),
                'extractor_version' => '1.0.0',
                'field_count' => count($data),
            ],
            'captured_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Generate sample snapshot data
     */
    private function generateSampleData(): array
    {
        return [
            'income' => fake()->numberBetween(20000, 150000),
            'credit_score' => fake()->numberBetween(300, 850),
            'age' => fake()->numberBetween(18, 75),
            'employment_status' => fake()->randomElement(['employed', 'self-employed', 'unemployed', 'retired']),
            'years_employed' => fake()->numberBetween(0, 40),
            'debt_to_income_ratio' => fake()->randomFloat(2, 0, 1),
            'has_collateral' => fake()->boolean(),
            'loan_amount_requested' => fake()->numberBetween(1000, 500000),
            'existing_loans_count' => fake()->numberBetween(0, 5),
            'is_first_time_applicant' => fake()->boolean(30),
        ];
    }

    /**
     * Create a snapshot for a specific entity
     */
    public function forSnapshotable(string $type, int $id): static
    {
        return $this->state(fn (array $attributes) => [
            'snapshotable_type' => $type,
            'snapshotable_id' => $id,
        ]);
    }

    /**
     * Create a snapshot with specific data
     */
    public function withData(array $data): static
    {
        return $this->state(fn (array $attributes) => [
            'data' => $data,
            'checksum' => hash('sha256', json_encode($data, JSON_SORT_KEYS)),
            'meta' => array_merge($attributes['meta'] ?? [], [
                'field_count' => count($data),
            ]),
        ]);
    }

    /**
     * Create a snapshot captured at a specific time
     */
    public function capturedAt($date): static
    {
        return $this->state(fn (array $attributes) => [
            'captured_at' => $date,
        ]);
    }

    /**
     * Create a recent snapshot (within last 30 days)
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'captured_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    /**
     * Create a snapshot with loan application data
     */
    public function loanApplication(): static
    {
        return $this->state(fn (array $attributes) => [
            'snapshotable_type' => 'App\\Models\\LoanApplication',
            'data' => [
                'income' => fake()->numberBetween(30000, 200000),
                'credit_score' => fake()->numberBetween(500, 850),
                'loan_amount' => fake()->numberBetween(5000, 500000),
                'loan_term_months' => fake()->randomElement([12, 24, 36, 48, 60, 72, 84]),
                'loan_purpose' => fake()->randomElement(['home', 'auto', 'education', 'business', 'personal']),
                'debt_to_income_ratio' => fake()->randomFloat(2, 0.1, 0.6),
                'employment_length_years' => fake()->numberBetween(0, 30),
                'has_bankruptcies' => fake()->boolean(10),
                'has_collateral' => fake()->boolean(40),
            ],
        ]);
    }

    /**
     * Create a snapshot with scholarship applicant data
     */
    public function scholarshipApplicant(): static
    {
        return $this->state(fn (array $attributes) => [
            'snapshotable_type' => 'App\\Models\\ScholarshipApplication',
            'data' => [
                'gpa' => fake()->randomFloat(2, 2.0, 4.0),
                'sat_score' => fake()->numberBetween(800, 1600),
                'act_score' => fake()->numberBetween(15, 36),
                'family_income' => fake()->numberBetween(20000, 200000),
                'extracurricular_count' => fake()->numberBetween(0, 10),
                'community_service_hours' => fake()->numberBetween(0, 500),
                'has_recommendation_letters' => fake()->boolean(80),
                'essay_submitted' => fake()->boolean(90),
                'is_first_generation' => fake()->boolean(30),
            ],
        ]);
    }

    /**
     * Create a snapshot with user profile data
     */
    public function userProfile(): static
    {
        return $this->state(fn (array $attributes) => [
            'snapshotable_type' => 'App\\Models\\User',
            'data' => [
                'age' => fake()->numberBetween(18, 80),
                'account_age_days' => fake()->numberBetween(1, 3650),
                'is_verified' => fake()->boolean(70),
                'has_2fa_enabled' => fake()->boolean(40),
                'subscription_tier' => fake()->randomElement(['free', 'basic', 'premium', 'enterprise']),
                'total_purchases' => fake()->numberBetween(0, 100),
                'lifetime_value' => fake()->randomFloat(2, 0, 10000),
                'support_tickets_count' => fake()->numberBetween(0, 20),
                'last_login_days_ago' => fake()->numberBetween(0, 365),
            ],
        ]);
    }
}
