<?php

namespace CleaniqueCoders\Eligify\Database\Factories;

use CleaniqueCoders\Eligify\Enums\GroupCombination;
use CleaniqueCoders\Eligify\Models\Criteria;
use CleaniqueCoders\Eligify\Models\RuleGroup;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<RuleGroup>
 */
class RuleGroupFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = RuleGroup::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'uuid' => Str::uuid()->toString(),
            'criteria_id' => Criteria::factory(),
            'name' => $this->faker->word(),
            'description' => $this->faker->optional()->sentence(),
            'logic_type' => GroupCombination::ALL->value,
            'min_required' => null,
            'boolean_expression' => null,
            'weight' => $this->faker->randomFloat(2, 0.5, 3.0),
            'order' => 0,
            'is_active' => true,
            'meta' => [],
        ];
    }

    /**
     * Create a group with ALL logic (all rules must pass).
     */
    public function withAllLogic(): self
    {
        return $this->state(fn (array $attributes) => [
            'logic_type' => GroupCombination::ALL->value,
            'min_required' => null,
            'boolean_expression' => null,
        ]);
    }

    /**
     * Create a group with ANY logic (at least one rule must pass).
     */
    public function withAnyLogic(): self
    {
        return $this->state(fn (array $attributes) => [
            'logic_type' => GroupCombination::ANY->value,
            'min_required' => null,
            'boolean_expression' => null,
        ]);
    }

    /**
     * Create a group with MIN logic.
     */
    public function withMinLogic(int $minRequired): self
    {
        return $this->state(fn (array $attributes) => [
            'logic_type' => GroupCombination::MIN->value,
            'min_required' => $minRequired,
            'boolean_expression' => null,
        ]);
    }

    /**
     * Create a group with MAJORITY logic.
     */
    public function withMajorityLogic(): self
    {
        return $this->state(fn (array $attributes) => [
            'logic_type' => GroupCombination::MAJORITY->value,
            'min_required' => null,
            'boolean_expression' => null,
        ]);
    }

    /**
     * Create a group with BOOLEAN logic.
     */
    public function withBooleanLogic(string $expression): self
    {
        return $this->state(fn (array $attributes) => [
            'logic_type' => GroupCombination::BOOLEAN->value,
            'min_required' => null,
            'boolean_expression' => $expression,
        ]);
    }

    /**
     * Create an identity verification group.
     */
    public function identity(): self
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'identity',
            'description' => 'Identity verification checks',
            'logic_type' => GroupCombination::ALL->value,
            'weight' => 2.0,
        ]);
    }

    /**
     * Create a financial group.
     */
    public function financial(): self
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'financial',
            'description' => 'Financial eligibility checks',
            'logic_type' => GroupCombination::ALL->value,
            'weight' => 2.5,
        ]);
    }

    /**
     * Create a verification group.
     */
    public function verification(): self
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'verification',
            'description' => 'Contact method verification',
            'logic_type' => GroupCombination::MIN->value,
            'min_required' => 2,
            'weight' => 1.5,
        ]);
    }

    /**
     * Create an inactive group.
     */
    public function inactive(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set specific order.
     */
    public function ordered(int $order): self
    {
        return $this->state(fn (array $attributes) => [
            'order' => $order,
        ]);
    }

    /**
     * Set metadata.
     */
    public function withMeta(array $meta): self
    {
        return $this->state(fn (array $attributes) => [
            'meta' => $meta,
        ]);
    }

    /**
     * For a specific criteria.
     */
    public function forCriteria(Criteria $criteria): self
    {
        return $this->state(fn (array $attributes) => [
            'criteria_id' => $criteria->id,
        ]);
    }
}
