<?php

namespace CleaniqueCoders\Eligify\Http\Livewire;

use CleaniqueCoders\Eligify\Models\Criteria;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class CriteriaList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $view = 'grid'; // 'grid' | 'list'

    public int $perPage = 10;

    public bool $onlyActive = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'view' => ['except' => 'grid'],
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
        $this->view = in_array($view, ['grid', 'list']) ? $view : 'grid';
    }

    public function getItemsProperty(): LengthAwarePaginator
    {
        $query = Criteria::query()->withCount(['rules', 'evaluations']);

        if ($this->onlyActive) {
            $query->where('is_active', true);
        }

        if ($this->search !== '') {
            $s = "%{$this->search}%";
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', $s)
                    ->orWhere('description', 'like', $s)
                    ->orWhere('slug', 'like', $s);
            });
        }

        return $query->orderByDesc('updated_at')
            ->paginate($this->perPage)
            ->withQueryString();
    }

    public function render()
    {
        return view('eligify::livewire.criteria-list', [
            'items' => $this->items,
        ]);
    }
}
