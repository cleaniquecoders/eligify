<?php

namespace CleaniqueCoders\Eligify\Database\Factories;

use CleaniqueCoders\Eligify\Models\Criteria;
use CleaniqueCoders\Eligify\Models\Rule;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class RuleFactory extends Factory
{
    protected $model = Rule::class;

    public function definition(): array
    {
        $field = fake()->randomElement(['income', 'credit_score', 'age', 'experience_years', 'gpa']);
        $operator = fake()->randomElement(['>=', '<=', '==', '!=', '>', '<']);

        return [
            'criteria_id' => Criteria::factory(),
            'field' => $field,
            'operator' => $operator,
            'value' => [$this->generateValueForField($field, $operator)],
            'slug' => Str::slug($field.'_'.$operator.'_'.fake()->word()),
            'weight' => fake()->numberBetween(1, 10),
            'order' => fake()->numberBetween(0, 100),
            'is_active' => fake()->boolean(85), // 85% chance of being active
            'meta' => [
                'description' => fake()->sentence(),
                'category' => fake()->randomElement(['basic', 'advanced', 'premium']),
                'created_by' => fake()->name(),
            ],
        ];
    }

    /**
     * Generate appropriate value based on field and operator
     */
    private function generateValueForField(string $field, string $operator): mixed
    {
        return match ($field) {
            'income' => fake()->numberBetween(20000, 100000),
            'credit_score' => fake()->numberBetween(300, 850),
            'age' => fake()->numberBetween(18, 65),
            'experience_years' => fake()->numberBetween(0, 30),
            'gpa' => fake()->randomFloat(2, 2.0, 4.0),
            default => fake()->numberBetween(1, 100),
        };
    }

    /**
     * Mark the rule as active
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Mark the rule as inactive
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create income rule
     */
    public function income(string $operator = '>=', int $amount = 50000): static
    {
        return $this->state(fn (array $attributes) => [
            'field' => 'income',
            'operator' => $operator,
            'value' => [$amount],
            'slug' => Str::slug("income_{$operator}_{$amount}"),
        ]);
    }

    /**
     * Create credit score rule
     */
    public function creditScore(string $operator = '>=', int $score = 650): static
    {
        return $this->state(fn (array $attributes) => [
            'field' => 'credit_score',
            'operator' => $operator,
            'value' => [$score],
            'slug' => Str::slug("credit_score_{$operator}_{$score}"),
        ]);
    }

    /**
     * Create age rule
     */
    public function age(string $operator = '>=', int $years = 21): static
    {
        return $this->state(fn (array $attributes) => [
            'field' => 'age',
            'operator' => $operator,
            'value' => [$years],
            'slug' => Str::slug("age_{$operator}_{$years}"),
        ]);
    }

    /**
     * Create rule with multiple values (for 'in' operator)
     */
    public function withMultipleValues(?array $values = null): static
    {
        $values = $values ?? [fake()->word(), fake()->word(), fake()->word()];

        return $this->state(fn (array $attributes) => [
            'operator' => 'in',
            'value' => $values,
            'slug' => Str::slug($attributes['field'].'_in_'.implode('_', $values)),
        ]);
    }

    /**
     * Set rule order
     */
    public function order(int $order): static
    {
        return $this->state(fn (array $attributes) => [
            'order' => $order,
        ]);
    }

    /**
     * Set rule weight
     */
    public function weight(int $weight): static
    {
        return $this->state(fn (array $attributes) => [
            'weight' => $weight,
        ]);
    }
}
