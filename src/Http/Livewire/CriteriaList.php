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

    public string $type = '';

    public string $group = '';

    public string $category = '';

    public string $tag = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'view' => ['except' => 'grid'],
        'perPage' => ['except' => 10],
        'onlyActive' => ['except' => false],
        'type' => ['except' => ''],
        'group' => ['except' => ''],
        'category' => ['except' => ''],
        'tag' => ['except' => ''],
        'page' => ['except' => 1],
    ];

    public function updating($name, $value)
    {
        if (in_array($name, ['search', 'perPage', 'onlyActive', 'type', 'group', 'category', 'tag'])) {
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

        if ($this->type !== '') {
            $query->where('type', $this->type);
        }

        if ($this->group !== '') {
            $query->where('group', $this->group);
        }

        if ($this->category !== '') {
            $query->where('category', $this->category);
        }

        if ($this->tag !== '') {
            // Tags are normalized to lowercase in editor/builder; mirror that here
            $query->whereJsonContains('tags', strtolower($this->tag));
        }

        return $query->orderByDesc('updated_at')
            ->paginate($this->perPage)
            ->withQueryString();
    }

    public function getTypeOptionsProperty()
    {
        return Criteria::query()
            ->select('type')
            ->whereNotNull('type')
            ->where('type', '!=', '')
            ->distinct()
            ->orderBy('type')
            ->pluck('type');
    }

    public function getGroupOptionsProperty()
    {
        return Criteria::query()
            ->select('group')
            ->whereNotNull('group')
            ->where('group', '!=', '')
            ->distinct()
            ->orderBy('group')
            ->pluck('group');
    }

    public function getCategoryOptionsProperty()
    {
        return Criteria::query()
            ->select('category')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');
    }

    public function getTagOptionsProperty()
    {
        // Collect all tags from rows and return unique, sorted list
        return Criteria::query()
            ->select('tags')
            ->whereNotNull('tags')
            ->get()
            ->flatMap(function ($row) {
                $tags = is_array($row->tags) ? $row->tags : [];

                return collect($tags);
            })
            ->filter()
            ->map(fn ($t) => strtolower((string) $t))
            ->unique()
            ->sort()
            ->values();
    }

    public function clearFilters(): void
    {
        $this->type = '';
        $this->group = '';
        $this->category = '';
        $this->tag = '';
        $this->resetPage();
    }

    public function render()
    {
        return view('eligify::livewire.criteria-list', [
            'items' => $this->items,
            'typeOptions' => $this->typeOptions,
            'groupOptions' => $this->groupOptions,
            'categoryOptions' => $this->categoryOptions,
            'tagOptions' => $this->tagOptions,
        ]);
    }
}
