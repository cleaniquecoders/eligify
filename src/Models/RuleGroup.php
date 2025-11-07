<?php

declare(strict_types=1);

namespace CleaniqueCoders\Eligify\Models;

use CleaniqueCoders\Eligify\Enums\GroupCombination;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * RuleGroup Model
 *
 * Represents a logical grouping of rules within a criteria.
 * Groups can have different combination logic (AND, OR, MIN, MAJORITY, BOOLEAN).
 *
 * @property int $id
 * @property string $uuid
 * @property int $criteria_id
 * @property string $name
 * @property string|null $description
 * @property string $logic_type
 * @property int|null $min_required
 * @property string|null $boolean_expression
 * @property float $weight
 * @property int $order
 * @property bool $is_active
 * @property array|null $meta
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \CleaniqueCoders\Eligify\Models\Criteria $criteria
 * @property-read \Illuminate\Database\Eloquent\Collection<Rule> $rules
 */
class RuleGroup extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'eligify_rule_groups';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'uuid',
        'criteria_id',
        'name',
        'description',
        'logic_type',
        'min_required',
        'boolean_expression',
        'weight',
        'order',
        'is_active',
        'meta',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'weight' => 'float',
        'min_required' => 'integer',
        'meta' => 'array',
        'logic_type' => GroupCombination::class,
    ];

    /**
     * Get the criteria that owns this group.
     */
    public function criteria(): BelongsTo
    {
        return $this->belongsTo(Criteria::class, 'criteria_id');
    }

    /**
     * Get the rules in this group.
     */
    public function rules(): HasMany
    {
        return $this->hasMany(Rule::class, 'group_id')->orderBy('order');
    }

    /**
     * Get the logic type combination.
     */
    public function getLogicType(): GroupCombination
    {
        return $this->logic_type instanceof GroupCombination
            ? $this->logic_type
            : GroupCombination::from($this->logic_type);
    }

    /**
     * Check if this group uses AND logic (all rules must pass).
     */
    public function usesAllLogic(): bool
    {
        return $this->getLogicType() === GroupCombination::ALL;
    }

    /**
     * Check if this group uses OR logic (any rule must pass).
     */
    public function usesAnyLogic(): bool
    {
        return $this->getLogicType() === GroupCombination::ANY;
    }

    /**
     * Check if this group uses MIN logic.
     */
    public function usesMinLogic(): bool
    {
        return $this->getLogicType() === GroupCombination::MIN;
    }

    /**
     * Check if this group uses MAJORITY logic.
     */
    public function usesMajorityLogic(): bool
    {
        return $this->getLogicType() === GroupCombination::MAJORITY;
    }

    /**
     * Check if this group uses BOOLEAN logic.
     */
    public function usesBooleanLogic(): bool
    {
        return $this->getLogicType() === GroupCombination::BOOLEAN;
    }

    /**
     * Get the minimum rules required for this group (for MIN logic).
     */
    public function getMinRequired(): ?int
    {
        return $this->min_required;
    }

    /**
     * Get the boolean expression (for BOOLEAN logic).
     */
    public function getBooleanExpression(): ?string
    {
        return $this->boolean_expression;
    }

    /**
     * Get metadata value by key.
     */
    public function getMeta(string $key, mixed $default = null): mixed
    {
        return $this->meta[$key] ?? $default;
    }

    /**
     * Set metadata value.
     */
    public function setMeta(string $key, mixed $value): self
    {
        $this->meta ??= [];
        $this->meta[$key] = $value;

        return $this;
    }

    /**
     * Scope to get active groups.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get groups for a specific criteria.
     */
    public function scopeForCriteria($query, Criteria $criteria)
    {
        return $query->where('criteria_id', $criteria->id);
    }

    /**
     * Scope to filter by logic type.
     */
    public function scopeByLogicType($query, GroupCombination|string $logicType)
    {
        $type = is_string($logicType) ? GroupCombination::from($logicType) : $logicType;

        return $query->where('logic_type', $type->value);
    }
}
