<?php

namespace CleaniqueCoders\Eligify\Http\Livewire;

use CleaniqueCoders\Eligify\Models\Criteria;
use Livewire\Component;

class CriteriaEditor extends Component
{
    public string $mode = 'create'; // create|edit

    public ?int $criteriaId = null;

    public string $name = '';

    public ?string $description = null;

    public bool $is_active = true;

    public ?Criteria $criteria = null;

    // New classification fields
    public ?string $type = null;

    public ?string $group = null;

    public ?string $category = null;

    // Comma-separated tags input, will be normalized to array on save
    public ?string $tags = null;

    public function mount(string $mode = 'create', ?int $criteriaId = null): void
    {
        $this->mode = in_array($mode, ['create', 'edit']) ? $mode : 'create';
        $this->criteriaId = $criteriaId;

        if ($this->mode === 'edit' && $criteriaId) {
            $this->criteria = Criteria::query()->findOrFail($criteriaId);
            $this->name = (string) $this->criteria->name;
            $this->description = $this->criteria->description;
            $this->is_active = (bool) $this->criteria->is_active;
            $this->type = $this->criteria->type;
            $this->group = $this->criteria->group;
            $this->category = $this->criteria->category;
            $this->tags = is_array($this->criteria->tags)
                ? implode(', ', $this->criteria->tags)
                : null;
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'type' => ['nullable', 'string', 'max:255'],
            'group' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'string'], // comma-separated
        ];
    }

    public function save()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
            'type' => $this->type ?: null,
            'group' => $this->group ?: null,
            'category' => $this->category ?: null,
            'tags' => $this->parseTagsString($this->tags),
        ];

        if ($this->mode === 'edit' && $this->criteria) {
            $this->criteria->update($data);
            session()->flash('status', 'Criteria updated successfully.');

            return $this->redirect(route('eligify.criteria.show', $this->criteria->id));
        }

        $criteria = Criteria::create($data);
        session()->flash('status', 'Criteria created successfully.');

        return $this->redirect(route('eligify.criteria.show', $criteria->id));
    }

    /**
     * Convert comma-separated tags string to a normalized array, or null if empty
     */
    protected function parseTagsString(?string $tags): ?array
    {
        if ($tags === null) {
            return null;
        }

        $parts = array_map('trim', explode(',', $tags));
        $parts = array_filter($parts, fn ($t) => $t !== '');
        $parts = array_map(fn ($t) => mb_strtolower($t), $parts);
        $parts = array_values(array_unique($parts));

        return count($parts) ? $parts : null;
    }

    public function render()
    {
        return view('eligify::livewire.criteria-editor');
    }
}
