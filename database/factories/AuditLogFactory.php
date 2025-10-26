<?php

namespace CleaniqueCoders\Eligify\Database\Factories;

use CleaniqueCoders\Eligify\Models\AuditLog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        $event = fake()->randomElement([
            'evaluation_completed',
            'rule_created',
            'rule_updated',
            'rule_deleted',
            'criteria_created',
            'criteria_updated',
            'criteria_activated',
            'criteria_deactivated',
        ]);

        $auditableType = fake()->randomElement([
            'CleaniqueCoders\\Eligify\\Models\\Criteria',
            'CleaniqueCoders\\Eligify\\Models\\Rule',
            'CleaniqueCoders\\Eligify\\Models\\Evaluation',
        ]);

        $auditableId = fake()->numberBetween(1, 1000);

        return [
            'event' => $event,
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'slug' => Str::slug("{$event}_{$auditableType}_{$auditableId}_".fake()->word()),
            'old_values' => $this->generateOldValues($event),
            'new_values' => $this->generateNewValues($event),
            'context' => [
                'source' => fake()->randomElement(['api', 'web', 'cli']),
                'action' => $event,
                'timestamp' => now()->toISOString(),
                'request_id' => fake()->uuid(),
            ],
            'user_type' => fake()->randomElement(['App\\Models\\User', 'App\\Models\\Admin']),
            'user_id' => fake()->numberBetween(1, 100),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'meta' => [
                'session_id' => fake()->uuid(),
                'referrer' => fake()->url(),
                'duration_ms' => fake()->numberBetween(10, 1000),
            ],
        ];
    }

    /**
     * Generate old values based on event type
     */
    private function generateOldValues(string $event): ?array
    {
        if (in_array($event, ['rule_created', 'criteria_created'])) {
            return null; // No old values for creation events
        }

        return match ($event) {
            'rule_updated' => [
                'field' => 'income',
                'operator' => '>=',
                'value' => [40000],
                'is_active' => true,
            ],
            'criteria_updated' => [
                'name' => 'old_loan_approval',
                'description' => 'Old description',
                'is_active' => false,
            ],
            'evaluation_completed' => [
                'status' => 'pending',
                'score' => null,
            ],
            default => [
                'status' => 'old_status',
                'updated_at' => fake()->dateTime()->format('Y-m-d H:i:s'),
            ],
        };
    }

    /**
     * Generate new values based on event type
     */
    private function generateNewValues(string $event): ?array
    {
        if (in_array($event, ['rule_deleted', 'criteria_deleted'])) {
            return null; // No new values for deletion events
        }

        return match ($event) {
            'rule_created', 'rule_updated' => [
                'field' => 'income',
                'operator' => '>=',
                'value' => [50000],
                'is_active' => true,
                'weight' => 5,
            ],
            'criteria_created', 'criteria_updated' => [
                'name' => 'loan_approval',
                'description' => 'Loan approval criteria',
                'is_active' => true,
            ],
            'evaluation_completed' => [
                'status' => 'completed',
                'score' => fake()->randomFloat(2, 0, 100),
                'passed' => fake()->boolean(),
            ],
            default => [
                'status' => 'new_status',
                'updated_at' => now()->format('Y-m-d H:i:s'),
            ],
        };
    }

    /**
     * Create evaluation completed audit log
     */
    public function evaluationCompleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'event' => 'evaluation_completed',
            'auditable_type' => 'CleaniqueCoders\\Eligify\\Models\\Evaluation',
            'old_values' => ['status' => 'pending'],
            'new_values' => [
                'status' => 'completed',
                'score' => fake()->randomFloat(2, 0, 100),
                'passed' => fake()->boolean(),
            ],
        ]);
    }

    /**
     * Create rule created audit log
     */
    public function ruleCreated(): static
    {
        return $this->state(fn (array $attributes) => [
            'event' => 'rule_created',
            'auditable_type' => 'CleaniqueCoders\\Eligify\\Models\\Rule',
            'old_values' => null,
            'new_values' => [
                'field' => fake()->randomElement(['income', 'credit_score', 'age']),
                'operator' => fake()->randomElement(['>=', '<=', '==']),
                'value' => [fake()->numberBetween(100, 1000)],
                'is_active' => true,
            ],
        ]);
    }

    /**
     * Create criteria updated audit log
     */
    public function criteriaUpdated(): static
    {
        return $this->state(fn (array $attributes) => [
            'event' => 'criteria_updated',
            'auditable_type' => 'CleaniqueCoders\\Eligify\\Models\\Criteria',
            'old_values' => [
                'name' => 'old_criteria_name',
                'is_active' => false,
            ],
            'new_values' => [
                'name' => 'updated_criteria_name',
                'is_active' => true,
            ],
        ]);
    }

    /**
     * Set specific user
     */
    public function byUser(string $userType, int $userId): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => $userType,
            'user_id' => $userId,
        ]);
    }

    /**
     * Set specific IP address
     */
    public function fromIp(string $ipAddress): static
    {
        return $this->state(fn (array $attributes) => [
            'ip_address' => $ipAddress,
        ]);
    }

    /**
     * Create recent audit log (within last 7 days)
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => fake()->dateTimeBetween('-7 days', 'now'),
        ]);
    }
}
