<?php

namespace CleaniqueCoders\Eligify\Enums;

use CleaniqueCoders\Traitify\Concerns\InteractsWithEnum;
use CleaniqueCoders\Traitify\Contracts\Enum;

enum ScoringMethod: string implements Enum
{
    use InteractsWithEnum;

    case WEIGHTED = 'weighted';
    case AVERAGE = 'average';
    case PERCENTAGE = 'percentage';

    /**
     * Get human-readable label (required by Enum interface)
     */
    public function label(): string
    {
        return match ($this) {
            self::WEIGHTED => 'Weighted',
            self::AVERAGE => 'Average',
            self::PERCENTAGE => 'Percentage',
        };
    }

    /**
     * Get description (required by Enum interface)
     */
    public function description(): string
    {
        return match ($this) {
            self::WEIGHTED => 'Score based on rule weights and importance',
            self::AVERAGE => 'Simple average of all rule scores',
            self::PERCENTAGE => 'Percentage of passed rules',
        };
    }
}
