<?php

declare(strict_types=1);

use CleaniqueCoders\Eligify\Data\Extractor;
use CleaniqueCoders\Eligify\Data\Snapshot;
use CleaniqueCoders\Eligify\Eligify;
use CleaniqueCoders\Eligify\Models\Criteria;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

if (! function_exists('eligify')) {
    function eligify(): Eligify
    {
        return new Eligify;
    }
}

if (! function_exists('eligify_extractor')) {
    function eligify_extractor(): Extractor
    {
        return new Extractor;
    }
}

if (! function_exists('eligify_snapshot')) {
    function eligify_snapshot(string $model, Model $data): Snapshot
    {
        if (! class_exists($model)) {
            throw new InvalidArgumentException("Model class not found: {$model}");
        }

        return eligify_extractor()
            ->forModel($model)
            ->extract($data);
    }
}

if (! function_exists('eligify_evaluate')) {
    /**
     * Evaluate a criteria (by string or instance) against a Snapshot.
     * Flags let callers control persistence and caching.
     *
     * @return mixed
     */
    function eligify_evaluate(string|Criteria $criteria, Snapshot $snapshot, bool $saveEvaluation = false, bool $useCache = false)
    {
        if (is_string($criteria)) {
            // Prefer slug, then fallback to name; do not auto-create
            $criteria = eligify_find_criteria($criteria, createIfMissing: false);
        }

        return eligify()->evaluate(
            criteria: $criteria,
            data: $snapshot,
            saveEvaluation: $saveEvaluation,
            useCache: $useCache
        );
    }
}

/**
 * Find criteria by slug (preferred) then by exact name; optionally create an unsaved instance.
 */
if (! function_exists('eligify_find_criteria')) {
    function eligify_find_criteria(string $keyword, bool $createIfMissing = false): Criteria
    {
        $slug = Str::slug(Str::lower($keyword));

        $criteria = Criteria::query()
            ->where('slug', $slug)
            ->orWhere('name', $keyword)
            ->first();

        if ($criteria) {
            return $criteria;
        }

        if ($createIfMissing) {
            return new Criteria([
                'name' => $keyword,
                'slug' => $slug,
                'description' => "Auto-generated criteria for {$keyword}",
                'is_active' => true,
            ]);
        }

        // Maintain soft behavior (no throw): return an unsaved Criteria
        return new Criteria(['name' => $keyword, 'slug' => $slug]);
    }
}

/**
 * Convenience: query a model's attached criteria with optional filters.
 * Requires the model to use the HasCriteria trait (criteria() relation).
 * Supported filters: type, group, category, tags (array|string)
 */
if (! function_exists('eligify_criteria_of')) {
    function eligify_criteria_of(Model $model, array $filters = [])
    {
        if (! method_exists($model, 'criteria')) {
            throw new InvalidArgumentException(sprintf(
                'Model %s must use the HasCriteria trait to call criteria()',
                $model::class
            ));
        }

        $query = $model->criteria();

        if (! empty($filters['type'])) {
            $query->whereIn('type', (array) $filters['type']);
        }
        if (! empty($filters['group'])) {
            $query->whereIn('group', (array) $filters['group']);
        }
        if (! empty($filters['category'])) {
            $query->whereIn('category', (array) $filters['category']);
        }
        if (! empty($filters['tags'])) {
            foreach ((array) $filters['tags'] as $tag) {
                $query->whereJsonContains('tags', Str::lower((string) $tag));
            }
        }

        return $query;
    }
}

/**
 * Convenience: attach criteria IDs/slugs/instances to a model without detaching existing.
 */
if (! function_exists('eligify_attach_criteria')) {
    function eligify_attach_criteria(Model $model, array|string|int|Criteria $criteria): void
    {
        if (! method_exists($model, 'criteria')) {
            throw new InvalidArgumentException(sprintf(
                'Model %s must use the HasCriteria trait to call criteria()',
                $model::class
            ));
        }

        $ids = collect(is_array($criteria) ? $criteria : [$criteria])
            ->map(function ($item) {
                if ($item instanceof Criteria) {
                    return $item->getKey();
                }
                if (is_int($item)) {
                    return $item;
                }
                if (is_string($item)) {
                    $found = eligify_find_criteria($item, createIfMissing: false);

                    return $found->exists ? $found->getKey() : null;
                }

                return null;
            })
            ->filter()
            ->values()
            ->all();

        if ($ids === []) {
            return;
        }

        $model->criteria()->syncWithoutDetaching($ids);
    }
}
