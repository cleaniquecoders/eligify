<?php

namespace CleaniqueCoders\Eligify\Http\Livewire;

use CleaniqueCoders\Eligify\Models\Criteria;
use CleaniqueCoders\Eligify\Models\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class CriteriaShow extends Component
{
    use WithPagination;

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

    public function deleteRule($ruleId)
    {
        $rule = Rule::query()->where('criteria_id', $this->criteriaId)->findOrFail($ruleId);
        $rule->delete();

        session()->flash('rule_status', 'Rule deleted successfully.');
        $this->criteria = Criteria::query()->withCount(['rules', 'evaluations'])->findOrFail($this->criteriaId);
    }

    public function toggleRuleStatus($ruleId)
    {
        $rule = Rule::query()->where('criteria_id', $this->criteriaId)->findOrFail($ruleId);
        $rule->update(['is_active' => ! $rule->is_active]);

        session()->flash('rule_status', 'Rule status updated successfully.');
        $this->criteria = Criteria::query()->withCount(['rules', 'evaluations'])->findOrFail($this->criteriaId);
    }

    public function render()
    {
        $rules = Rule::query()
            ->where('criteria_id', $this->criteriaId)
            ->ordered()
            ->paginate(10);

        return view('eligify::livewire.criteria-show', [
            'rules' => $rules,
        ]);
    }
}
