<div class="space-y-3">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
        <div class="flex items-center gap-2">
            <input type="text" placeholder="Search audit logs (event/type/ip/ua)..." class="px-3 py-2 border rounded w-96" wire:model.debounce.300ms="search">
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
            <div class="p-6 text-sm text-gray-600">No audit logs found.</div>
        @else
            @if ($view === 'list')
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-gray-700">
                            <tr>
                                <th class="text-left px-4 py-2">Event</th>
                                <th class="text-left px-4 py-2">Auditable</th>
                                <th class="text-left px-4 py-2">Details</th>
                                <th class="text-left px-4 py-2">When</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $log)
                                <tr class="border-t">
                                    <td class="px-4 py-2">{{ $log->event }}</td>
                                    <td class="px-4 py-2">{{ $log->auditable_type }} #{{ $log->auditable_id }}</td>
                                    <td class="px-4 py-2 text-gray-600">{{ $log->getDescription() }}</td>
                                    <td class="px-4 py-2 text-gray-500">{{ optional($log->created_at)->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 p-3">
                    @foreach ($items as $log)
                        <div class="border rounded p-4">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="text-xs uppercase text-gray-500">{{ $log->event }}</div>
                                    <div class="font-semibold">{{ $log->auditable_type }} #{{ $log->auditable_id }}</div>
                                    <div class="text-xs text-gray-600 mt-1">{{ $log->getDescription() }}</div>
                                </div>
                                <div class="text-xs text-gray-500">{{ optional($log->created_at)->diffForHumans() }}</div>
                            </div>
                            @if ($log->ip_address)
                                <div class="mt-3 text-xs text-gray-600">IP: {{ $log->ip_address }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="px-3 py-2 border-t">{{ $items->links() }}</div>
        @endif
    </div>
</div>
