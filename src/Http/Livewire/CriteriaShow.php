<?php

namespace CleaniqueCoders\Eligify\Http\Livewire;

use CleaniqueCoders\Eligify\Models\Criteria;
use Livewire\Component;

class CriteriaShow extends Component
{
    public int $criteriaId;

    public Criteria $criteria;

    public function mount(int $criteriaId): void
    {
        $this->criteriaId = $criteriaId;
        $this->criteria = Criteria::query()->withCount(['rules', 'evaluations'])->findOrFail($criteriaId);
    }

    public function delete()
    {
        $this->criteria->delete();
        session()->flash('status', 'Criteria deleted successfully.');

        return redirect()->route('eligify.criteria.index');
    }

    public function render()
    {
        return view('eligify::livewire.criteria-show');
    }
}
