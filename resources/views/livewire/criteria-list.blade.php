<div class="space-y-3">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
        <div class="flex items-center gap-2">
            <input type="text" placeholder="Search criteria..." class="px-3 py-2 border rounded w-64" wire:model.debounce.300ms="search">
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
            <div class="p-6 text-sm text-gray-600">No criteria found.</div>
        @else
            @if ($view === 'list')
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-gray-700">
                            <tr>
                                <th class="text-left px-4 py-2">Name</th>
                                <th class="text-left px-4 py-2">Active</th>
                                <th class="text-left px-4 py-2">Rules</th>
                                <th class="text-left px-4 py-2">Evaluations</th>
                                <th class="text-left px-4 py-2">Updated</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $criteria)
                                <tr class="border-t">
                                    <td class="px-4 py-2">
                                        <a class="text-blue-600 hover:underline" href="{{ route('eligify.criteria.show', $criteria->id) }}">{{ $criteria->name }}</a>
                                        <div class="text-xs text-gray-500">{{ $criteria->description }}</div>
                                    </td>
                                    <td class="px-4 py-2">
                                        <span class="inline-flex items-center px-2 py-0.5 text-xs rounded {{ $criteria->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                                            {{ $criteria->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2">{{ $criteria->rules_count }}</td>
                                    <td class="px-4 py-2">{{ $criteria->evaluations_count }}</td>
                                    <td class="px-4 py-2 text-gray-500">{{ optional($criteria->updated_at)->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 p-3">
                    @foreach ($items as $criteria)
                        <div class="border rounded p-4">
                            <div class="flex items-start justify-between">
                                <div>
                                    <a class="font-semibold hover:underline" href="{{ route('eligify.criteria.show', $criteria->id) }}">{{ $criteria->name }}</a>
                                    <div class="text-xs text-gray-500 mt-1">{{ \Illuminate\Support\Str::limit($criteria->description, 120) }}</div>
                                </div>
                                <span class="inline-flex items-center px-2 py-0.5 text-xs rounded {{ $criteria->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                                    {{ $criteria->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            <div class="mt-3 flex gap-4 text-xs text-gray-600">
                                <div>Rules: <span class="font-semibold">{{ $criteria->rules_count }}</span></div>
                                <div>Evals: <span class="font-semibold">{{ $criteria->evaluations_count }}</span></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="px-3 py-2 border-t">{{ $items->links() }}</div>
        @endif
    </div>
</div>
