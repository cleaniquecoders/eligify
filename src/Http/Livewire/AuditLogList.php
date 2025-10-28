<?php

namespace CleaniqueCoders\Eligify\Http\Livewire;

use CleaniqueCoders\Eligify\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class AuditLogList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $view = 'list'; // 'grid' | 'list'

    public int $perPage = 10;

    protected $queryString = [
        'search' => ['except' => ''],
        'view' => ['except' => 'list'],
        'perPage' => ['except' => 10],
        'page' => ['except' => 1],
    ];

    public function updating($name, $value)
    {
        if (in_array($name, ['search', 'perPage'])) {
            $this->resetPage();
        }
    }

    public function setView(string $view): void
    {
        $this->view = in_array($view, ['grid', 'list']) ? $view : 'list';
    }

    public function getItemsProperty(): LengthAwarePaginator
    {
        $query = AuditLog::query();

        if ($this->search !== '') {
            $s = "%{$this->search}%";
            $query->where(function ($q) use ($s) {
                $q->where('event', 'like', $s)
                    ->orWhere('auditable_type', 'like', $s)
                    ->orWhere('auditable_id', 'like', $s)
                    ->orWhere('slug', 'like', $s)
                    ->orWhere('ip_address', 'like', $s)
                    ->orWhere('user_agent', 'like', $s);
            });
        }

        return $query->orderByDesc('created_at')
            ->paginate($this->perPage)
            ->withQueryString();
    }

    public function render()
    {
        return view('eligify::livewire.audit-log-list', [
            'items' => $this->items,
        ]);
    }
}
