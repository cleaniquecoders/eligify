<?php

namespace CleaniqueCoders\Eligify\Models;

use CleaniqueCoders\Traitify\Concerns\InteractsWithEnum;
use CleaniqueCoders\Traitify\Concerns\InteractsWithMeta;
use CleaniqueCoders\Traitify\Concerns\InteractsWithSlug;
use CleaniqueCoders\Traitify\Concerns\InteractsWithUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Evaluation extends Model
{
    use HasFactory;
    use InteractsWithEnum;
    use InteractsWithMeta;
    use InteractsWithSlug;
    use InteractsWithUuid;

    protected $table = 'eligify_evaluations';

    protected $fillable = [
        'criteria_id',
        'evaluable_type',
        'evaluable_id',
        'slug',
        'passed',
        'score',
        'failed_rules',
        'rule_results',
        'decision',
        'context',
        'meta',
        'evaluated_at',
    ];

    protected $casts = [
        'passed' => 'boolean',
        'score' => 'decimal:2',
        'failed_rules' => 'array',
        'rule_results' => 'array',
        'context' => 'array',
        'meta' => 'array',
        'evaluated_at' => 'datetime',
    ];

    /**
     * Configure the slug source field
     */
    public function getSlugSourceAttribute(): string
    {
        $criteriaName = $this->criteria ? $this->criteria->getAttribute('name') : 'unknown';

        return $criteriaName.'_'.$this->getAttribute('evaluable_type').'_'.$this->getAttribute('evaluable_id');
    }

    /**
     * The criteria this evaluation was performed against
     */
    public function criteria(): BelongsTo
    {
        return $this->belongsTo(Criteria::class);
    }

    /**
     * The evaluable entity (polymorphic relationship)
     */
    public function evaluable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope for passed evaluations
     */
    public function scopePassed($query)
    {
        return $query->where('passed', true);
    }

    /**
     * Scope for failed evaluations
     */
    public function scopeFailed($query)
    {
        return $query->where('passed', false);
    }

    /**
     * Scope to filter by score range
     */
    public function scopeByScoreRange($query, float $min, float $max)
    {
        return $query->whereBetween('score', [$min, $max]);
    }

    /**
     * Scope to filter by evaluation date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('evaluated_at', [$startDate, $endDate]);
    }

    /**
     * Get failed rule IDs as collection
     */
    public function getFailedRuleIds()
    {
        return collect($this->getAttribute('failed_rules') ?? []);
    }

    /**
     * Get the evaluation result summary
     */
    public function getSummary(): array
    {
        return [
            'passed' => $this->getAttribute('passed'),
            'score' => $this->getAttribute('score'),
            'decision' => $this->getAttribute('decision'),
            'failed_rules_count' => count($this->getAttribute('failed_rules') ?? []),
            'evaluated_at' => $this->getAttribute('evaluated_at'),
        ];
    }

    /**
     * Check if a specific rule failed
     */
    public function ruleFailedById(int $ruleId): bool
    {
        return in_array($ruleId, $this->getAttribute('failed_rules') ?? []);
    }
}
