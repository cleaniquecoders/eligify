<?php

declare(strict_types=1);

namespace CleaniqueCoders\Eligify\Database\Factories;

use CleaniqueCoders\Eligify\Models\Criteria;
use CleaniqueCoders\Eligify\Models\CriteriaVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\CleaniqueCoders\Eligify\Models\CriteriaVersion>
 */
class CriteriaVersionFactory extends Factory
{
    protected $model = CriteriaVersion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) $this->faker->uuid(),
            'criteria_id' => Criteria::factory(),
            'version' => $this->faker->numberBetween(1, 5),
            'description' => $this->faker->sentence(),
            'rules_snapshot' => [
                [
                    'id' => $this->faker->numberBetween(1, 100),
                    'field' => $this->faker->word(),
                    'operator' => '>=',
                    'value' => $this->faker->numberBetween(1, 100),
                    'weight' => 1,
                    'order' => 1,
                    'is_active' => true,
                    'meta' => [],
                ],
            ],
            'meta' => [
                'created_by' => 1,
            ],
        ];
    }

    /**
     * Indicate that the version should be the first
     */
    public function firstVersion(): static
    {
        return $this->state(fn (array $attributes) => [
            'version' => 1,
        ]);
    }

    /**
     * Indicate that the version should have multiple rules
     */
    public function withMultipleRules(int $count = 3): static
    {
        return $this->state(function (array $attributes) use ($count) {
            $rules = collect(range(1, $count))->map(function ($i) {
                return [
                    'id' => $i,
                    'field' => "field_{$i}",
                    'operator' => '>=',
                    'value' => $i * 100,
                    'weight' => 1,
                    'order' => $i,
                    'is_active' => true,
                    'meta' => [],
                ];
            })->toArray();

            return [
                'rules_snapshot' => $rules,
            ];
        });
    }

    /**
     * Indicate a specific version number
     */
    public function version(int $version): static
    {
        return $this->state(fn (array $attributes) => [
            'version' => $version,
        ]);
    }

    /**
     * Indicate a specific description
     */
    public function description(string $description): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => $description,
        ]);
    }
}
