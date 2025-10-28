<?php

namespace CleaniqueCoders\Eligify\Http\Livewire;

use CleaniqueCoders\Eligify\Models\Rule;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class RuleLibraryList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $view = 'list'; // 'grid' | 'list'

    public int $perPage = 10;

    public bool $onlyActive = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'view' => ['except' => 'list'],
        'perPage' => ['except' => 10],
        'onlyActive' => ['except' => false],
        'page' => ['except' => 1],
    ];

    public function updating($name, $value)
    {
        if (in_array($name, ['search', 'perPage', 'onlyActive'])) {
            $this->resetPage();
        }
    }

    public function setView(string $view): void
    {
        $this->view = in_array($view, ['grid', 'list']) ? $view : 'list';
    }

    public function getItemsProperty(): LengthAwarePaginator
    {
        $query = Rule::query()->with('criteria');

        if ($this->onlyActive) {
            $query->where('is_active', true);
        }

        if ($this->search !== '') {
            $s = "%{$this->search}%";
            $query->where(function ($q) use ($s) {
                $q->where('field', 'like', $s)
                    ->orWhere('operator', 'like', $s)
                    ->orWhere('value', 'like', $s);
            });
        }

        return $query->orderBy('criteria_id')
            ->orderBy('order')
            ->paginate($this->perPage)
            ->withQueryString();
    }

    public function render()
    {
        return view('eligify::livewire.rule-library-list', [
            'items' => $this->items,
        ]);
    }
}
