<?php

declare(strict_types=1);

namespace CleaniqueCoders\Eligify\Models;

use CleaniqueCoders\Traitify\Concerns\InteractsWithEnum;
use CleaniqueCoders\Traitify\Concerns\InteractsWithMeta;
use CleaniqueCoders\Traitify\Concerns\InteractsWithSlug;
use CleaniqueCoders\Traitify\Concerns\InteractsWithUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Criteria extends Model
{
    use HasFactory;
    use InteractsWithEnum;
    use InteractsWithMeta;
    use InteractsWithSlug;
    use InteractsWithUuid;

    protected $table = 'eligify_criteria';

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'description',
        'is_active',
        'type',
        'group',
        'category',
        'tags',
        'meta',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'tags' => 'array',
        'meta' => 'array',
    ];

    /**
     * Configure the slug source field
     */
    public function getSlugSourceAttribute(): string
    {
        return $this->getAttribute('name');
    }

    /**
     * Rules associated with this criteria
     */
    public function rules(): HasMany
    {
        return $this->hasMany(Rule::class);
    }

    /**
     * Active rules only
     */
    public function activeRules(): HasMany
    {
        return $this->rules()->where('is_active', true)->orderBy('order');
    }

    /**
     * Evaluations performed on this criteria
     */
    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    /**
     * Scope for active criteria
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to find by name
     */
    public function scopeByName($query, string $name)
    {
        return $query->where('name', $name);
    }

    /**
     * Scope: filter by type(s)
     */
    public function scopeType($query, string|array $type)
    {
        return $query->whereIn('type', (array) $type);
    }

    /**
     * Scope: filter by group(s)
     */
    public function scopeGroup($query, string|array $group)
    {
        return $query->whereIn('group', (array) $group);
    }

    /**
     * Scope: filter by category(ies)
     */
    public function scopeCategory($query, string|array $category)
    {
        return $query->whereIn('category', (array) $category);
    }

    /**
     * Scope: filter by tags (AND semantics across provided tags)
     */
    public function scopeTagged($query, string|array $tags)
    {
        foreach ((array) $tags as $tag) {
            $query->whereJsonContains('tags', $tag);
        }

        return $query;
    }
}
