<?php

declare(strict_types=1);

namespace CleaniqueCoders\Eligify\Models;

use CleaniqueCoders\Traitify\Concerns\InteractsWithEnum;
use CleaniqueCoders\Traitify\Concerns\InteractsWithMeta;
use CleaniqueCoders\Traitify\Concerns\InteractsWithUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rule extends Model
{
    use HasFactory;
    use InteractsWithEnum;
    use InteractsWithMeta;
    use InteractsWithUuid;

    protected $table = 'eligify_rules';

    protected $fillable = [
        'uuid',
        'criteria_id',
        'field',
        'operator',
        'value',
        'weight',
        'order',
        'is_active',
        'meta',
    ];

    protected $casts = [
        'value' => 'json',
        'weight' => 'integer',
        'order' => 'integer',
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    /**
     * The criteria this rule belongs to
     */
    public function criteria(): BelongsTo
    {
        return $this->belongsTo(Criteria::class);
    }

    /**
     * Scope for active rules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order rules by execution order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * Scope to filter by field
     */
    public function scopeByField($query, string $field)
    {
        return $query->where('field', $field);
    }

    /**
     * Scope to filter by operator
     */
    public function scopeByOperator($query, string $operator)
    {
        return $query->where('operator', $operator);
    }

    /**
     * Get the actual value for evaluation
     */
    public function getEvaluationValue()
    {
        $value = $this->getAttribute('value');

        return is_array($value) && count($value) === 1
            ? $value[0]
            : $value;
    }

    /**
     * Check if this rule supports multiple values
     */
    public function supportsMultipleValues(): bool
    {
        return in_array($this->getAttribute('operator'), ['in', 'not_in', 'between']);
    }
}
