<?php

declare(strict_types=1);

namespace CleaniqueCoders\Eligify\Storage;

use CleaniqueCoders\Eligify\Models\Criteria;
use CleaniqueCoders\Eligify\Models\Rule;
use CleaniqueCoders\Eligify\Models\RuleGroup;
use CleaniqueCoders\Eligify\Storage\Contracts\StorageDriver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FilesystemStorageDriver implements StorageDriver
{
    protected string $disk;

    protected string $path;

    public function __construct(string $disk = 'local', string $path = 'eligify')
    {
        $this->disk = $disk;
        $this->path = rtrim($path, '/');
    }

    public function findCriteriaBySlug(string $slug): ?Criteria
    {
        $slug = str($slug)->slug()->toString();
        $data = $this->readFile($slug);

        if (! $data || ! ($data['is_active'] ?? true)) {
            return null;
        }

        return $this->hydrateCriteria($data, activeRulesOnly: true);
    }

    public function findCriteria(string $identifier): ?Criteria
    {
        // Try as slug first
        $data = $this->readFile(str($identifier)->slug()->toString());

        if ($data) {
            return $this->hydrateCriteria($data);
        }

        // Search all files by name
        foreach ($this->listFiles() as $file) {
            $fileData = $this->readFileByPath($file);
            if ($fileData && ($fileData['name'] ?? '') === $identifier) {
                return $this->hydrateCriteria($fileData);
            }
        }

        return null;
    }

    public function getAllActiveCriteria(): Collection
    {
        $criteria = collect();

        foreach ($this->listFiles() as $file) {
            $data = $this->readFileByPath($file);
            if ($data && ($data['is_active'] ?? true)) {
                $criteria->push($this->hydrateCriteria($data));
            }
        }

        return $criteria;
    }

    public function storeCriteria(array $data): Criteria
    {
        $slug = $data['slug'] ?? str($data['name'])->slug()->toString();
        $existing = $this->readFile($slug);

        $criteriaData = array_merge($existing ?? [], [
            'uuid' => $data['uuid'] ?? $existing['uuid'] ?? (string) Str::uuid(),
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? $existing['description'] ?? "Auto-generated criteria for {$data['name']}",
            'is_active' => $data['is_active'] ?? $existing['is_active'] ?? true,
            'type' => $data['type'] ?? $existing['type'] ?? null,
            'group' => $data['group'] ?? $existing['group'] ?? null,
            'category' => $data['category'] ?? $existing['category'] ?? null,
            'tags' => $data['tags'] ?? $existing['tags'] ?? [],
            'meta' => $data['meta'] ?? $existing['meta'] ?? [],
            'rules' => $existing['rules'] ?? [],
            'groups' => $existing['groups'] ?? [],
            'created_at' => $existing['created_at'] ?? now()->toISOString(),
            'updated_at' => now()->toISOString(),
        ]);

        $this->writeFile($slug, $criteriaData);

        return $this->hydrateCriteria($criteriaData);
    }

    public function storeRule(Criteria $criteria, array $ruleData): void
    {
        $slug = $criteria->slug;
        $data = $this->readFile($slug) ?? [];

        $rules = $data['rules'] ?? [];
        $rules[] = array_merge($ruleData, [
            'uuid' => $ruleData['uuid'] ?? (string) Str::uuid(),
        ]);

        $data['rules'] = $rules;
        $data['updated_at'] = now()->toISOString();

        $this->writeFile($slug, $data);
    }

    public function deleteCriteria(string $identifier): bool
    {
        $slug = str($identifier)->slug()->toString();

        // Try direct slug match
        if ($this->fileExists($slug)) {
            return $this->deleteFile($slug);
        }

        // Search by name
        foreach ($this->listFiles() as $file) {
            $data = $this->readFileByPath($file);
            if ($data && ($data['name'] ?? '') === $identifier) {
                return Storage::disk($this->disk)->delete($file);
            }
        }

        return false;
    }

    public function storeGroup(Criteria $criteria, array $groupData, array $rules = []): mixed
    {
        $slug = $criteria->slug;
        $data = $this->readFile($slug) ?? [];

        $groups = $data['groups'] ?? [];

        // Check if group already exists
        $existingIndex = null;
        foreach ($groups as $index => $group) {
            if (($group['name'] ?? '') === $groupData['name']) {
                $existingIndex = $index;
                break;
            }
        }

        $group = array_merge($existingIndex !== null ? $groups[$existingIndex] : [], [
            'uuid' => $groupData['uuid'] ?? (string) Str::uuid(),
            'name' => $groupData['name'],
            'description' => $groupData['description'] ?? null,
            'logic_type' => $groupData['logic_type'] ?? 'all',
            'min_required' => $groupData['min_required'] ?? null,
            'boolean_expression' => $groupData['boolean_expression'] ?? null,
            'weight' => $groupData['weight'] ?? 1.0,
            'order' => $groupData['order'] ?? count($groups),
            'is_active' => $groupData['is_active'] ?? true,
            'meta' => $groupData['meta'] ?? [],
            'rules' => $rules,
        ]);

        if ($existingIndex !== null) {
            $groups[$existingIndex] = $group;
        } else {
            $groups[] = $group;
        }

        $data['groups'] = $groups;
        $data['updated_at'] = now()->toISOString();

        $this->writeFile($slug, $data);

        return $this->hydrateGroup($group, $criteria);
    }

    public function exportCriteria(string $slug): ?array
    {
        return $this->readFile($slug);
    }

    public function importCriteria(array $data): Criteria
    {
        $slug = $data['slug'] ?? str($data['name'])->slug()->toString();
        $data['slug'] = $slug;

        $this->writeFile($slug, $data);

        return $this->hydrateCriteria($data);
    }

    /**
     * Hydrate a Criteria Eloquent model from array data
     */
    protected function hydrateCriteria(array $data, bool $activeRulesOnly = false): Criteria
    {
        $criteria = new Criteria;
        $criteria->forceFill([
            'uuid' => $data['uuid'] ?? (string) Str::uuid(),
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'type' => $data['type'] ?? null,
            'group' => $data['group'] ?? null,
            'category' => $data['category'] ?? null,
            'tags' => $data['tags'] ?? [],
            'meta' => $data['meta'] ?? [],
        ]);
        $criteria->exists = false;

        // Hydrate rules
        $rules = collect($data['rules'] ?? [])
            ->when($activeRulesOnly, fn ($c) => $c->where('is_active', true))
            ->sortBy('order')
            ->values()
            ->map(fn (array $r) => $this->hydrateRule($r, $criteria));

        $criteria->setRelation('rules', $rules);

        // Hydrate groups
        if (! empty($data['groups'])) {
            $groups = collect($data['groups'])
                ->map(fn (array $g) => $this->hydrateGroup($g, $criteria));
            $criteria->setRelation('groups', $groups);
        }

        return $criteria;
    }

    /**
     * Hydrate a Rule model from array data
     */
    protected function hydrateRule(array $data, Criteria $criteria): Rule
    {
        $rule = new Rule;
        $rule->forceFill([
            'uuid' => $data['uuid'] ?? (string) Str::uuid(),
            'criteria_id' => $criteria->id,
            'field' => $data['field'],
            'operator' => $data['operator'],
            'value' => $data['value'] ?? null,
            'weight' => $data['weight'] ?? 1,
            'order' => $data['order'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
            'meta' => $data['meta'] ?? [],
        ]);
        $rule->exists = false;

        return $rule;
    }

    /**
     * Hydrate a RuleGroup model from array data
     */
    protected function hydrateGroup(array $data, Criteria $criteria): RuleGroup
    {
        $group = new RuleGroup;
        $group->forceFill([
            'uuid' => $data['uuid'] ?? (string) Str::uuid(),
            'criteria_id' => $criteria->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'logic_type' => $data['logic_type'] ?? 'all',
            'min_required' => $data['min_required'] ?? null,
            'boolean_expression' => $data['boolean_expression'] ?? null,
            'weight' => $data['weight'] ?? 1.0,
            'order' => $data['order'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
            'meta' => $data['meta'] ?? [],
        ]);
        $group->exists = false;

        if (! empty($data['rules'])) {
            $rules = collect($data['rules'])
                ->map(fn (array $r) => $this->hydrateRule($r, $criteria));
            $group->setRelation('rules', $rules);
        }

        return $group;
    }

    protected function filePath(string $slug): string
    {
        return "{$this->path}/{$slug}.json";
    }

    protected function readFile(string $slug): ?array
    {
        $path = $this->filePath($slug);

        if (! Storage::disk($this->disk)->exists($path)) {
            return null;
        }

        $content = Storage::disk($this->disk)->get($path);

        return json_decode($content, true);
    }

    protected function readFileByPath(string $path): ?array
    {
        if (! Storage::disk($this->disk)->exists($path)) {
            return null;
        }

        $content = Storage::disk($this->disk)->get($path);

        return json_decode($content, true);
    }

    protected function writeFile(string $slug, array $data): void
    {
        $path = $this->filePath($slug);

        Storage::disk($this->disk)->put(
            $path,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    protected function fileExists(string $slug): bool
    {
        return Storage::disk($this->disk)->exists($this->filePath($slug));
    }

    protected function deleteFile(string $slug): bool
    {
        return Storage::disk($this->disk)->delete($this->filePath($slug));
    }

    protected function listFiles(): array
    {
        return Storage::disk($this->disk)->files($this->path);
    }
}
