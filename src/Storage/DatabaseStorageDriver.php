<?php

declare(strict_types=1);

namespace CleaniqueCoders\Eligify\Storage;

use CleaniqueCoders\Eligify\Models\Criteria;
use CleaniqueCoders\Eligify\Models\Rule;
use CleaniqueCoders\Eligify\Storage\Contracts\StorageDriver;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DatabaseStorageDriver implements StorageDriver
{
    public function findCriteriaBySlug(string $slug): ?Criteria
    {
        return Criteria::with(['rules' => function ($query) {
            $query->where('is_active', true)
                ->orderBy('order', 'asc');
        }])
            ->where('slug', str($slug)->slug())
            ->where('is_active', true)
            ->first();
    }

    public function findCriteria(string $identifier): ?Criteria
    {
        return Criteria::where('name', $identifier)
            ->orWhere('slug', $identifier)
            ->first();
    }

    public function getAllActiveCriteria(): Collection
    {
        return Criteria::where('is_active', true)->get();
    }

    public function storeCriteria(array $data): Criteria
    {
        $slug = $data['slug'] ?? str($data['name'])->slug()->toString();

        return Criteria::firstOrCreate(
            ['slug' => $slug],
            array_merge([
                'uuid' => (string) Str::uuid(),
                'name' => $data['name'],
                'slug' => $slug,
                'description' => $data['description'] ?? "Auto-generated criteria for {$data['name']}",
                'is_active' => $data['is_active'] ?? true,
            ], array_filter([
                'type' => $data['type'] ?? null,
                'group' => $data['group'] ?? null,
                'category' => $data['category'] ?? null,
                'tags' => $data['tags'] ?? null,
                'meta' => $data['meta'] ?? null,
            ], fn ($v) => $v !== null))
        );
    }

    public function storeRule(Criteria $criteria, array $ruleData): void
    {
        Rule::create(array_merge($ruleData, [
            'uuid' => (string) Str::uuid(),
            'criteria_id' => $criteria->id,
        ]));
    }

    public function deleteCriteria(string $identifier): bool
    {
        $criteria = $this->findCriteria($identifier);

        if (! $criteria) {
            return false;
        }

        $criteria->rules()->delete();
        $criteria->evaluations()->delete();

        return $criteria->delete();
    }

    public function storeGroup(Criteria $criteria, array $groupData, array $rules = []): mixed
    {
        $group = $criteria->groups()->firstOrCreate(
            ['name' => $groupData['name']],
            array_merge([
                'uuid' => (string) Str::uuid(),
                'logic_type' => $groupData['logic_type'] ?? 'all',
                'weight' => $groupData['weight'] ?? 1.0,
                'order' => $criteria->groups()->count(),
                'is_active' => $groupData['is_active'] ?? true,
                'meta' => $groupData['meta'] ?? [],
            ], array_filter([
                'description' => $groupData['description'] ?? null,
                'min_required' => $groupData['min_required'] ?? null,
                'boolean_expression' => $groupData['boolean_expression'] ?? null,
            ], fn ($v) => $v !== null))
        );

        foreach ($rules as $index => $ruleData) {
            $group->rules()->create(array_merge($ruleData, [
                'uuid' => (string) Str::uuid(),
                'criteria_id' => $criteria->id,
                'order' => $index,
            ]));
        }

        return $group;
    }

    public function exportCriteria(string $slug): ?array
    {
        $criteria = Criteria::with(['rules', 'groups.rules'])
            ->where('slug', $slug)
            ->first();

        if (! $criteria) {
            return null;
        }

        return [
            'uuid' => $criteria->uuid,
            'name' => $criteria->name,
            'slug' => $criteria->slug,
            'description' => $criteria->description,
            'is_active' => $criteria->is_active,
            'type' => $criteria->type,
            'group' => $criteria->group,
            'category' => $criteria->category,
            'tags' => $criteria->tags,
            'meta' => $criteria->meta,
            'rules' => $criteria->rules->map(fn (Rule $rule) => [
                'uuid' => $rule->uuid,
                'field' => $rule->field,
                'operator' => $rule->operator,
                'value' => $rule->value,
                'weight' => $rule->weight,
                'order' => $rule->order,
                'is_active' => $rule->is_active,
                'meta' => $rule->meta,
            ])->toArray(),
            'groups' => $criteria->groups->map(fn ($group) => [
                'uuid' => $group->uuid,
                'name' => $group->name,
                'description' => $group->description,
                'logic_type' => $group->logic_type,
                'min_required' => $group->min_required,
                'boolean_expression' => $group->boolean_expression,
                'weight' => $group->weight,
                'order' => $group->order,
                'is_active' => $group->is_active,
                'meta' => $group->meta,
                'rules' => $group->rules->map(fn (Rule $rule) => [
                    'uuid' => $rule->uuid,
                    'field' => $rule->field,
                    'operator' => $rule->operator,
                    'value' => $rule->value,
                    'weight' => $rule->weight,
                    'order' => $rule->order,
                    'is_active' => $rule->is_active,
                    'meta' => $rule->meta,
                ])->toArray(),
            ])->toArray(),
            'created_at' => $criteria->created_at?->toISOString(),
            'updated_at' => $criteria->updated_at?->toISOString(),
        ];
    }

    public function importCriteria(array $data): Criteria
    {
        $criteria = $this->storeCriteria($data);

        foreach ($data['rules'] ?? [] as $ruleData) {
            $this->storeRule($criteria, $ruleData);
        }

        foreach ($data['groups'] ?? [] as $groupData) {
            $rules = $groupData['rules'] ?? [];
            unset($groupData['rules']);
            $this->storeGroup($criteria, $groupData, $rules);
        }

        return $criteria->fresh(['rules', 'groups']);
    }
}
