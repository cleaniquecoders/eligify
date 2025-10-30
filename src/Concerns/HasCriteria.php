<?php

namespace CleaniqueCoders\Eligify\Concerns;

use CleaniqueCoders\Eligify\Models\Criteria;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasCriteria
{
    /**
     * Polymorphic many-to-many relation to attach criteria to any model.
     */
    public function criteria(): MorphToMany
    {
        return $this->morphToMany(
            Criteria::class,
            'criteriable',
            'eligify_criteriables',
            relatedPivotKey: 'criteria_id'
        )->withTimestamps();
    }

    /**
     * Attach one or more criteria IDs without detaching existing ones.
     */
    public function attachCriteria(int|array|\Illuminate\Support\Collection $criteriaIds): void
    {
        $this->criteria()->syncWithoutDetaching($criteriaIds);
    }

    /**
     * Detach one or more criteria IDs.
     */
    public function detachCriteria(int|array|\Illuminate\Support\Collection $criteriaIds): void
    {
        $this->criteria()->detach($criteriaIds);
    }

    /**
     * Replace currently attached criteria with the provided set.
     */
    public function syncCriteria(array $criteriaIds): void
    {
        $this->criteria()->sync($criteriaIds);
    }

    /**
     * Convenience filter for criteria type(s).
     */
    public function criteriaOfType(string|array $type)
    {
        return $this->criteria()->whereIn('type', (array) $type);
    }

    /**
     * Convenience filter for criteria group(s).
     */
    public function criteriaInGroup(string|array $group)
    {
        return $this->criteria()->whereIn('group', (array) $group);
    }

    /**
     * Convenience filter for criteria category(ies).
     */
    public function criteriaInCategory(string|array $category)
    {
        return $this->criteria()->whereIn('category', (array) $category);
    }

    /**
     * Convenience filter for JSON tags (requires MySQL 5.7+/Postgres).
     */
    public function criteriaTagged(string|array $tags)
    {
        $relation = $this->criteria();
        foreach ((array) $tags as $tag) {
            $relation->whereJsonContains('tags', $tag);
        }

        return $relation;
    }
}
