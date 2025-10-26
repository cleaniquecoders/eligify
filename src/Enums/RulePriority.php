<?php

namespace CleaniqueCoders\Eligify\Enums;

use CleaniqueCoders\Traitify\Concerns\InteractsWithEnum;
use CleaniqueCoders\Traitify\Contracts\Enum;

enum RulePriority: string implements Enum
{
    use InteractsWithEnum;

    case CRITICAL = 'critical';
    case HIGH = 'high';
    case MEDIUM = 'medium';
    case LOW = 'low';
    case INFO = 'info';

    /**
     * Get human-readable label (required by Enum interface)
     */
    public function label(): string
    {
        return match ($this) {
            self::CRITICAL => 'Critical',
            self::HIGH => 'High',
            self::MEDIUM => 'Medium',
            self::LOW => 'Low',
            self::INFO => 'Informational',
        };
    }

    /**
     * Get description (required by Enum interface)
     */
    public function description(): string
    {
        return match ($this) {
            self::CRITICAL => 'Must pass for eligibility',
            self::HIGH => 'Very important for eligibility',
            self::MEDIUM => 'Standard importance',
            self::LOW => 'Nice to have',
            self::INFO => 'Informational only',
        };
    }

    /**
     * Get numeric weight for this priority
     */
    public function getWeight(): int
    {
        return match ($this) {
            self::CRITICAL => 10,
            self::HIGH => 7,
            self::MEDIUM => 5,
            self::LOW => 3,
            self::INFO => 1,
        };
    }

    /**
     * Get color for UI representation
     */
    public function getColor(): string
    {
        return match ($this) {
            self::CRITICAL => '#dc2626', // red-600
            self::HIGH => '#ea580c',     // orange-600
            self::MEDIUM => '#ca8a04',   // yellow-600
            self::LOW => '#16a34a',      // green-600
            self::INFO => '#6b7280',     // gray-500
        };
    }

    /**
     * Check if this priority is blocking (must pass)
     */
    public function isBlocking(): bool
    {
        return $this === self::CRITICAL;
    }

    /**
     * Get all priorities with their weights
     */
    public static function withWeights(): array
    {
        $priorities = [];
        foreach (self::cases() as $priority) {
            $priorities[$priority->value] = $priority->getWeight();
        }

        return $priorities;
    }
}
