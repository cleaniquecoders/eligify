<div class="space-y-3">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
        <div class="flex items-center gap-2">
            <input type="text" placeholder="Search rules (field/operator/value)..." class="px-3 py-2 border rounded w-80" wire:model.debounce.300ms="search">
            <label class="inline-flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" class="rounded" wire:model.live="onlyActive"> Active only
            </label>
        </div>
        <div class="flex items-center gap-2">
            <select class="px-2 py-2 border rounded text-sm" wire:model.live="perPage">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </select>
            <div class="inline-flex rounded overflow-hidden border">
                <button type="button" wire:click="setView('list')" class="px-3 py-2 text-sm {{ $view==='list' ? 'bg-gray-900 text-white' : 'bg-white' }}">List</button>
                <button type="button" wire:click="setView('grid')" class="px-3 py-2 text-sm {{ $view==='grid' ? 'bg-gray-900 text-white' : 'bg-white' }}">Grid</button>
            </div>
        </div>
    </div>

    <div class="bg-white border rounded">
        @if ($items->count() === 0)
            <div class="p-6 text-sm text-gray-600">No rules found.</div>
        @else
            @if ($view === 'list')
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-gray-700">
                            <tr>
                                <th class="text-left px-4 py-2">Criteria</th>
                                <th class="text-left px-4 py-2">Field</th>
                                <th class="text-left px-4 py-2">Operator</th>
                                <th class="text-left px-4 py-2">Value</th>
                                <th class="text-left px-4 py-2">Active</th>
                                <th class="text-left px-4 py-2">Order</th>
                                <th class="text-left px-4 py-2">Updated</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $rule)
                                <tr class="border-t">
                                    <td class="px-4 py-2">{{ optional($rule->criteria)->name ?? '—' }}</td>
                                    <td class="px-4 py-2">{{ $rule->field }}</td>
                                    <td class="px-4 py-2">{{ $rule->operator }}</td>
                                    <td class="px-4 py-2 text-gray-600">{{ is_array($rule->value) ? json_encode($rule->value) : (string) $rule->value }}</td>
                                    <td class="px-4 py-2">
                                        <span class="inline-flex items-center px-2 py-0.5 text-xs rounded {{ $rule->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                                            {{ $rule->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2">{{ $rule->order }}</td>
                                    <td class="px-4 py-2 text-gray-500">{{ optional($rule->updated_at)->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 p-3">
                    @foreach ($items as $rule)
                        <div class="border rounded p-4">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="text-xs uppercase text-gray-500">{{ optional($rule->criteria)->name ?? 'Unassigned' }}</div>
                                    <div class="font-semibold">{{ $rule->field }} <span class="text-gray-500">{{ $rule->operator }}</span></div>
                                    <div class="text-xs text-gray-600 mt-1">{{ is_array($rule->value) ? json_encode($rule->value) : (string) $rule->value }}</div>
                                </div>
                                <span class="inline-flex items-center px-2 py-0.5 text-xs rounded {{ $rule->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                                    {{ $rule->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            <div class="mt-3 flex gap-4 text-xs text-gray-600">
                                <div>Order: <span class="font-semibold">{{ $rule->order }}</span></div>
                                <div>Updated: <span class="font-semibold">{{ optional($rule->updated_at)->diffForHumans() }}</span></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="px-3 py-2 border-t">{{ $items->links() }}</div>
        @endif
    </div>
</div>
