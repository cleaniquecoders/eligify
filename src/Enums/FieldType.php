<?php

declare(strict_types=1);

namespace CleaniqueCoders\Eligify\Enums;

use CleaniqueCoders\Traitify\Concerns\InteractsWithEnum;
use CleaniqueCoders\Traitify\Contracts\Enum;

enum FieldType: string implements Enum
{
    use InteractsWithEnum;

    case NUMERIC = 'numeric';
    case INTEGER = 'integer';
    case STRING = 'string';
    case BOOLEAN = 'boolean';
    case DATE = 'date';
    case ARRAY = 'array';

    /**
     * Get human-readable label (required by Enum interface)
     */
    public function label(): string
    {
        return match ($this) {
            self::NUMERIC => 'Numeric',
            self::INTEGER => 'Integer',
            self::STRING => 'String',
            self::BOOLEAN => 'Boolean',
            self::DATE => 'Date',
            self::ARRAY => 'Array',
        };
    }

    /**
     * Get description (required by Enum interface)
     */
    public function description(): string
    {
        return "Field type for {$this->label()} values";
    }

    /**
     * Get validation rule for this field type
     */
    public function getValidationRule(): string
    {
        return match ($this) {
            self::NUMERIC => 'numeric',
            self::INTEGER => 'integer',
            self::STRING => 'string',
            self::BOOLEAN => 'boolean',
            self::DATE => 'date',
            self::ARRAY => 'array',
        };
    }

    /**
     * Get PHP cast type for this field type
     */
    public function getCastType(): string
    {
        return match ($this) {
            self::NUMERIC => 'float',
            self::INTEGER => 'int',
            self::STRING => 'string',
            self::BOOLEAN => 'bool',
            self::DATE => 'datetime',
            self::ARRAY => 'array',
        };
    }

    /**
     * Cast value to this field type
     */
    public function castValue(mixed $value): mixed
    {
        return match ($this) {
            self::NUMERIC => (float) $value,
            self::INTEGER => (int) $value,
            self::STRING => (string) $value,
            self::BOOLEAN => (bool) $value,
            self::DATE => is_string($value) ? new \DateTime($value) : $value,
            self::ARRAY => is_array($value) ? $value : [$value],
        };
    }

    /**
     * Check if field type supports numeric operations
     */
    public function supportsNumericOperations(): bool
    {
        return in_array($this, [self::NUMERIC, self::INTEGER, self::DATE]);
    }

    /**
     * Check if field type supports string operations
     */
    public function supportsStringOperations(): bool
    {
        return $this === self::STRING;
    }

    /**
     * Check if field type supports array operations
     */
    public function supportsArrayOperations(): bool
    {
        return $this === self::ARRAY;
    }
}
