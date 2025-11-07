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
        'current_version',
        'type',
        'group',
        'category',
        'tags',
        'meta',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'current_version' => 'integer',
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
     * Versions of this criteria
     */
    public function versions(): HasMany
    {
        return $this->hasMany(CriteriaVersion::class);
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

    /**
     * Create a new version snapshot of current criteria and rules
     *
     * @param  string  $description  Optional description of this version
     * @return CriteriaVersion The created version
     */
    public function createVersion(string $description = ''): CriteriaVersion
    {
        $nextVersion = ($this->versions()->max('version') ?? 0) + 1;

        $rulesSnapshot = $this->rules()
            ->get()
            ->map(fn (Rule $rule) => [
                'id' => $rule->id,
                'field' => $rule->field,
                'operator' => $rule->operator,
                'value' => $rule->value,
                'weight' => $rule->weight,
                'order' => $rule->order,
                'is_active' => $rule->is_active,
                'meta' => $rule->meta,
            ])
            ->toArray();

        $version = $this->versions()->create([
            'uuid' => (string) str()->uuid(),
            'version' => $nextVersion,
            'description' => $description,
            'rules_snapshot' => $rulesSnapshot,
            'meta' => [
                'created_from_rules_count' => count($rulesSnapshot),
            ],
        ]);

        // Update current version
        $this->update(['current_version' => $nextVersion]);

        // Refresh the model to ensure current_version is set in memory
        $this->refresh();

        return $version;
    }

    /**
     * Get a specific version by version number
     *
     * @param  int  $version  The version number to retrieve
     * @return CriteriaVersion|null The version or null if not found
     */
    public function version(int $version): ?CriteriaVersion
    {
        return $this->versions()
            ->where('version', $version)
            ->first();
    }

    /**
     * Get the latest version
     *
     * @return CriteriaVersion|null The latest version or null if none exist
     */
    public function latestVersion(): ?CriteriaVersion
    {
        return $this->versions()
            ->orderByDesc('version')
            ->first();
    }

    /**
     * Check if a specific version exists
     *
     * @param  int  $version  The version number to check
     * @return bool True if version exists
     */
    public function hasVersion(int $version): bool
    {
        return $this->versions()
            ->where('version', $version)
            ->exists();
    }

    /**
     * Get all version numbers for this criteria
     *
     * @return array Array of version numbers
     */
    public function getVersionNumbers(): array
    {
        return $this->versions()
            ->orderBy('version')
            ->pluck('version')
            ->toArray();
    }

    /**
     * Compare two versions and return differences
     *
     * @param  int  $version1  First version number
     * @param  int  $version2  Second version number
     * @return array Differences between versions
     */
    public function compareVersions(int $version1, int $version2): array
    {
        $v1 = $this->version($version1);
        $v2 = $this->version($version2);

        if (! $v1 || ! $v2) {
            throw new \InvalidArgumentException('One or both versions not found');
        }

        $rules1 = collect($v1->getRulesSnapshot());
        $rules2 = collect($v2->getRulesSnapshot());

        return [
            'added' => $rules2->whereNotIn('id', $rules1->pluck('id'))->values()->toArray(),
            'removed' => $rules1->whereNotIn('id', $rules2->pluck('id'))->values()->toArray(),
            'modified' => $this->findModifiedRules($rules1, $rules2),
        ];
    }

    /**
     * Find rules that were modified between versions
     *
     * @param  Collection  $rules1  Rules from first version
     * @param  Collection  $rules2  Rules from second version
     * @return array Array of modified rules
     */
    private function findModifiedRules($rules1, $rules2): array
    {
        $modified = [];

        foreach ($rules1 as $rule1) {
            $rule2 = $rules2->firstWhere('id', $rule1['id']);

            if ($rule2 && $rule1 !== $rule2) {
                $modified[] = [
                    'rule_id' => $rule1['id'],
                    'before' => $rule1,
                    'after' => $rule2,
                ];
            }
        }

        return $modified;
    }
}
