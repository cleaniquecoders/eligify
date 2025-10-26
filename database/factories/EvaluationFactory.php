<?php

namespace CleaniqueCoders\Eligify\Database\Factories;

use CleaniqueCoders\Eligify\Models\Criteria;
use CleaniqueCoders\Eligify\Models\Evaluation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class EvaluationFactory extends Factory
{
    protected $model = Evaluation::class;

    public function definition(): array
    {
        $passed = fake()->boolean(70); // 70% chance of passing
        $score = $passed ? fake()->randomFloat(2, 65, 100) : fake()->randomFloat(2, 0, 64);
        $evaluableType = fake()->randomElement(['App\\Models\\User', 'App\\Models\\Application', 'App\\Models\\LoanApplication']);
        $evaluableId = fake()->numberBetween(1, 1000);

        return [
            'criteria_id' => Criteria::factory(),
            'evaluable_type' => $evaluableType,
            'evaluable_id' => $evaluableId,
            'slug' => Str::slug("evaluation_{$evaluableType}_{$evaluableId}_".fake()->word()),
            'passed' => $passed,
            'score' => $score,
            'failed_rules' => $passed ? [] : fake()->randomElements([1, 2, 3, 4, 5], fake()->numberBetween(1, 3)),
            'rule_results' => $this->generateRuleResults(),
            'decision' => $passed ? fake()->randomElement(['Approved', 'Accepted', 'Qualified']) : fake()->randomElement(['Rejected', 'Declined', 'Not Qualified']),
            'context' => [
                'user_data' => [
                    'income' => fake()->numberBetween(20000, 100000),
                    'credit_score' => fake()->numberBetween(300, 850),
                    'age' => fake()->numberBetween(18, 65),
                ],
                'evaluation_type' => 'automatic',
                'session_id' => fake()->uuid(),
            ],
            'meta' => [
                'processing_time_ms' => fake()->numberBetween(10, 500),
                'evaluator_version' => '1.0.0',
                'notes' => fake()->sentence(),
            ],
            'evaluated_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Generate sample rule results
     */
    private function generateRuleResults(): array
    {
        $results = [];
        $ruleCount = fake()->numberBetween(2, 6);

        for ($i = 1; $i <= $ruleCount; $i++) {
            $passed = fake()->boolean(75);
            $results["rule_{$i}"] = [
                'rule_id' => $i,
                'field' => fake()->randomElement(['income', 'credit_score', 'age', 'experience']),
                'operator' => fake()->randomElement(['>=', '<=', '==']),
                'expected' => fake()->numberBetween(100, 1000),
                'actual' => fake()->numberBetween(50, 1200),
                'passed' => $passed,
                'score' => $passed ? fake()->numberBetween(8, 10) : fake()->numberBetween(0, 7),
            ];
        }

        return $results;
    }

    /**
     * Create a passed evaluation
     */
    public function passed(): static
    {
        return $this->state(fn (array $attributes) => [
            'passed' => true,
            'score' => fake()->randomFloat(2, 65, 100),
            'failed_rules' => [],
            'decision' => fake()->randomElement(['Approved', 'Accepted', 'Qualified']),
        ]);
    }

    /**
     * Create a failed evaluation
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'passed' => false,
            'score' => fake()->randomFloat(2, 0, 64),
            'failed_rules' => fake()->randomElements([1, 2, 3, 4, 5], fake()->numberBetween(1, 3)),
            'decision' => fake()->randomElement(['Rejected', 'Declined', 'Not Qualified']),
        ]);
    }

    /**
     * Set specific score
     */
    public function withScore(float $score): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => $score,
            'passed' => $score >= 65,
        ]);
    }

    /**
     * Set specific evaluable entity
     */
    public function forEvaluable(string $type, int $id): static
    {
        return $this->state(fn (array $attributes) => [
            'evaluable_type' => $type,
            'evaluable_id' => $id,
            'slug' => Str::slug("evaluation_{$type}_{$id}_".fake()->word()),
        ]);
    }

    /**
     * Set evaluation date
     */
    public function evaluatedAt($date): static
    {
        return $this->state(fn (array $attributes) => [
            'evaluated_at' => $date,
        ]);
    }

    /**
     * Create recent evaluation (within last 30 days)
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'evaluated_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    /**
     * Add specific failed rules
     */
    public function withFailedRules(array $ruleIds): static
    {
        return $this->state(fn (array $attributes) => [
            'failed_rules' => $ruleIds,
            'passed' => empty($ruleIds),
        ]);
    }
}
