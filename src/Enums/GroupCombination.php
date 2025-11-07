<?php

declare(strict_types=1);

namespace CleaniqueCoders\Eligify\Enums;

/**
 * Group combination logic types for rule groups.
 *
 * Defines how rules within a group (or groups themselves) combine:
 * - ALL: All items must pass (AND logic)
 * - ANY: At least one item must pass (OR logic)
 * - MIN: At least N items must pass
 * - MAJORITY: More than half the items must pass
 * - BOOLEAN: Custom boolean expression like "(a AND b) OR c"
 */
enum GroupCombination: string
{
    case ALL = 'all';
    case ANY = 'any';
    case MIN = 'min';
    case MAJORITY = 'majority';
    case BOOLEAN = 'boolean';

    /**
     * Get user-friendly label for the combination type.
     */
    public function label(): string
    {
        return match ($this) {
            self::ALL => 'All rules must pass',
            self::ANY => 'At least one rule must pass',
            self::MIN => 'Minimum rules must pass',
            self::MAJORITY => 'Majority of rules must pass',
            self::BOOLEAN => 'Custom boolean logic',
        };
    }

    /**
     * Get description for the combination type.
     */
    public function description(): string
    {
        return match ($this) {
            self::ALL => 'AND logic - all rules must evaluate to true',
            self::ANY => 'OR logic - at least one rule must evaluate to true',
            self::MIN => 'At least N rules must evaluate to true',
            self::MAJORITY => 'More than 50% of rules must evaluate to true',
            self::BOOLEAN => 'Custom boolean expression with AND, OR, NOT operators',
        };
    }
}
