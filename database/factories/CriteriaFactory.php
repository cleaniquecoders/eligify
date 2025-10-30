<?php

namespace CleaniqueCoders\Eligify\Database\Factories;

use CleaniqueCoders\Eligify\Models\Criteria;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CriteriaFactory extends Factory
{
    protected $model = Criteria::class;

    public function definition(): array
    {
        $name = fake()->words(2, true).'_eligibility';

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'is_active' => fake()->boolean(80), // 80% chance of being active
            'type' => fake()->randomElement(['subscription', 'feature', 'policy']),
            'group' => fake()->randomElement(['billing', 'access-control', 'risk']),
            'category' => fake()->randomElement(['basic', 'premium', 'enterprise']),
            'tags' => fake()->randomElements(['beta', 'internal', 'deprecated', 'ga'], fake()->numberBetween(0, 3)),
            'meta' => [
                'difficulty' => fake()->randomElement(['easy', 'medium', 'hard']),
                'source' => 'factory',
            ],
        ];
    }

    /**
     * Mark the criteria as active
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Mark the criteria as inactive
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create loan approval criteria
     */
    public function loanApproval(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'loan_approval',
            'slug' => 'loan-approval',
            'description' => 'Criteria for loan approval eligibility',
            'meta' => [
                'category' => 'finance',
                'min_score' => 65,
                'tags' => ['loan', 'credit', 'finance'],
            ],
        ]);
    }

    /**
     * Create scholarship criteria
     */
    public function scholarship(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'scholarship_eligibility',
            'slug' => 'scholarship-eligibility',
            'description' => 'Criteria for scholarship eligibility',
            'meta' => [
                'category' => 'education',
                'min_score' => 70,
                'tags' => ['scholarship', 'education', 'academic'],
            ],
        ]);
    }
}
