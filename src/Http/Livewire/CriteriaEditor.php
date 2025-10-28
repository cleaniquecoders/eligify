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

    public function mount(string $mode = 'create', ?int $criteriaId = null): void
    {
        $this->mode = in_array($mode, ['create', 'edit']) ? $mode : 'create';
        $this->criteriaId = $criteriaId;

        if ($this->mode === 'edit' && $criteriaId) {
            $this->criteria = Criteria::query()->findOrFail($criteriaId);
            $this->name = (string) $this->criteria->name;
            $this->description = $this->criteria->description;
            $this->is_active = (bool) $this->criteria->is_active;
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }

    public function save()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
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

    public function render()
    {
        return view('eligify::livewire.criteria-editor');
    }
}
