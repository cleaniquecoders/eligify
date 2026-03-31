<?php

declare(strict_types=1);

namespace CleaniqueCoders\Eligify\Storage;

use CleaniqueCoders\Eligify\Models\Criteria;
use CleaniqueCoders\Eligify\Models\Rule;
use CleaniqueCoders\Eligify\Models\RuleGroup;
use CleaniqueCoders\Eligify\Storage\Contracts\StorageDriver;
use Illuminate\Database\Eloquent\Model;
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
            'criteria_id' => $criteria->getAttribute('id'),
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

        return (bool) $criteria->delete();
    }

    public function storeGroup(Criteria $criteria, array $groupData, array $rules = []): mixed
    {
        /** @var RuleGroup $group */
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
                'criteria_id' => $criteria->getAttribute('id'),
                'order' => $index,
            ]));
        }

        return $group;
    }

    public function exportCriteria(string $slug): ?array
    {
        /** @var Criteria|null $criteria */
        $criteria = Criteria::with(['rules', 'groups.rules'])
            ->where('slug', $slug)
            ->first();

        if (! $criteria) {
            return null;
        }

        $mapRule = fn (Model $rule): array => [
            'uuid' => $rule->getAttribute('uuid'),
            'field' => $rule->getAttribute('field'),
            'operator' => $rule->getAttribute('operator'),
            'value' => $rule->getAttribute('value'),
            'weight' => $rule->getAttribute('weight'),
            'order' => $rule->getAttribute('order'),
            'is_active' => $rule->getAttribute('is_active'),
            'meta' => $rule->getAttribute('meta'),
        ];

        return [
            'uuid' => $criteria->getAttribute('uuid'),
            'name' => $criteria->getAttribute('name'),
            'slug' => $criteria->getAttribute('slug'),
            'description' => $criteria->getAttribute('description'),
            'is_active' => $criteria->getAttribute('is_active'),
            'type' => $criteria->getAttribute('type'),
            'group' => $criteria->getAttribute('group'),
            'category' => $criteria->getAttribute('category'),
            'tags' => $criteria->getAttribute('tags'),
            'meta' => $criteria->getAttribute('meta'),
            'rules' => $criteria->rules->map($mapRule)->toArray(),
            'groups' => $criteria->groups->map(function ($group) use ($mapRule): array {
                /** @var RuleGroup $group */
                return [
                    'uuid' => $group->getAttribute('uuid'),
                    'name' => $group->getAttribute('name'),
                    'description' => $group->getAttribute('description'),
                    'logic_type' => $group->getAttribute('logic_type'),
                    'min_required' => $group->getAttribute('min_required'),
                    'boolean_expression' => $group->getAttribute('boolean_expression'),
                    'weight' => $group->getAttribute('weight'),
                    'order' => $group->getAttribute('order'),
                    'is_active' => $group->getAttribute('is_active'),
                    'meta' => $group->getAttribute('meta'),
                    'rules' => $group->rules->map(fn ($rule) => $mapRule($rule))->toArray(),
                ];
            })->toArray(),
            'created_at' => $criteria->getAttribute('created_at')?->toISOString(),
            'updated_at' => $criteria->getAttribute('updated_at')?->toISOString(),
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
