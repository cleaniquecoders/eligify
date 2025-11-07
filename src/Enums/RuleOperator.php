<?php

declare(strict_types=1);

namespace CleaniqueCoders\Eligify\Enums;

use CleaniqueCoders\Traitify\Concerns\InteractsWithEnum;
use CleaniqueCoders\Traitify\Contracts\Enum;

enum RuleOperator: string implements Enum
{
    use InteractsWithEnum;

    // Numeric Comparisons
    case EQUAL = '==';
    case NOT_EQUAL = '!=';
    case GREATER_THAN = '>';
    case GREATER_THAN_OR_EQUAL = '>=';
    case LESS_THAN = '<';
    case LESS_THAN_OR_EQUAL = '<=';

    // Array/Set Operations
    case IN = 'in';
    case NOT_IN = 'not_in';

    // Range Operations
    case BETWEEN = 'between';
    case NOT_BETWEEN = 'not_between';

    // String Operations
    case CONTAINS = 'contains';
    case STARTS_WITH = 'starts_with';
    case ENDS_WITH = 'ends_with';

    // Existence Operations
    case EXISTS = 'exists';
    case NOT_EXISTS = 'not_exists';

    // Pattern Matching
    case REGEX = 'regex';

    /**
     * Get human-readable label (required by Enum interface)
     */
    public function label(): string
    {
        return match ($this) {
            self::EQUAL => 'Equal To',
            self::NOT_EQUAL => 'Not Equal To',
            self::GREATER_THAN => 'Greater Than',
            self::GREATER_THAN_OR_EQUAL => 'Greater Than or Equal',
            self::LESS_THAN => 'Less Than',
            self::LESS_THAN_OR_EQUAL => 'Less Than or Equal',
            self::IN => 'In Array',
            self::NOT_IN => 'Not In Array',
            self::BETWEEN => 'Between',
            self::NOT_BETWEEN => 'Not Between',
            self::CONTAINS => 'Contains',
            self::STARTS_WITH => 'Starts With',
            self::ENDS_WITH => 'Ends With',
            self::EXISTS => 'Exists',
            self::NOT_EXISTS => 'Does Not Exist',
            self::REGEX => 'Regular Expression',
        };
    }

    /**
     * Get description (required by Enum interface)
     */
    public function description(): string
    {
        return match ($this) {
            self::EQUAL => 'Value must be exactly equal',
            self::NOT_EQUAL => 'Value must not be equal',
            self::GREATER_THAN => 'Value must be greater than',
            self::GREATER_THAN_OR_EQUAL => 'Value must be greater than or equal to',
            self::LESS_THAN => 'Value must be less than',
            self::LESS_THAN_OR_EQUAL => 'Value must be less than or equal to',
            self::IN => 'Value must be in the given array',
            self::NOT_IN => 'Value must not be in the given array',
            self::BETWEEN => 'Value must be between two values (inclusive)',
            self::NOT_BETWEEN => 'Value must not be between two values',
            self::CONTAINS => 'String must contain the given substring',
            self::STARTS_WITH => 'String must start with the given substring',
            self::ENDS_WITH => 'String must end with the given substring',
            self::EXISTS => 'Value must not be null or empty',
            self::NOT_EXISTS => 'Value must be null or empty',
            self::REGEX => 'Value must match the given regex pattern',
        };
    }

    /**
     * Check if operator requires multiple values
     */
    public function requiresMultipleValues(): bool
    {
        return in_array($this, [
            self::IN,
            self::NOT_IN,
            self::BETWEEN,
            self::NOT_BETWEEN,
        ]);
    }

    /**
     * Check if operator is numeric comparison
     */
    public function isNumericComparison(): bool
    {
        return in_array($this, [
            self::EQUAL,
            self::NOT_EQUAL,
            self::GREATER_THAN,
            self::GREATER_THAN_OR_EQUAL,
            self::LESS_THAN,
            self::LESS_THAN_OR_EQUAL,
            self::BETWEEN,
            self::NOT_BETWEEN,
        ]);
    }

    /**
     * Check if operator is string operation
     */
    public function isStringOperation(): bool
    {
        return in_array($this, [
            self::CONTAINS,
            self::STARTS_WITH,
            self::ENDS_WITH,
            self::REGEX,
        ]);
    }

    /**
     * Get operators for specific field type
     */
    public static function forFieldType(FieldType $fieldType): array
    {
        return match ($fieldType) {
            FieldType::NUMERIC, FieldType::INTEGER => [
                self::EQUAL,
                self::NOT_EQUAL,
                self::GREATER_THAN,
                self::GREATER_THAN_OR_EQUAL,
                self::LESS_THAN,
                self::LESS_THAN_OR_EQUAL,
                self::BETWEEN,
                self::NOT_BETWEEN,
                self::IN,
                self::NOT_IN,
            ],
            FieldType::STRING => [
                self::EQUAL,
                self::NOT_EQUAL,
                self::IN,
                self::NOT_IN,
                self::CONTAINS,
                self::STARTS_WITH,
                self::ENDS_WITH,
                self::REGEX,
            ],
            FieldType::BOOLEAN => [
                self::EQUAL,
                self::NOT_EQUAL,
            ],
            FieldType::DATE => [
                self::EQUAL,
                self::NOT_EQUAL,
                self::GREATER_THAN,
                self::GREATER_THAN_OR_EQUAL,
                self::LESS_THAN,
                self::LESS_THAN_OR_EQUAL,
                self::BETWEEN,
                self::NOT_BETWEEN,
            ],
            FieldType::ARRAY => [
                self::IN,
                self::NOT_IN,
                self::CONTAINS,
            ],
        };
    }
}
