<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white rounded-xl p-4 shadow-sm border border-gray-100">
        <div class="flex items-center gap-3 flex-1">
            <div class="relative flex-1 max-w-xl">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <x-eligify::ui.input type="text" placeholder="Search rules (field/operator/value)..." class="pl-10" wire:model.live.debounce.300ms="search" />
            </div>
            <label class="inline-flex items-center gap-2 text-sm text-gray-700 bg-gray-50 px-4 py-2.5 border border-gray-200 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer">
                <x-eligify::ui.checkbox wire:model.live="onlyActive" />
                <span class="font-medium">Active only</span>
            </label>
        </div>
        <div class="flex items-center gap-3">
            <x-eligify::ui.select wire:model.live="perPage">
                <option value="10">10 per page</option>
                <option value="25">25 per page</option>
                <option value="50">50 per page</option>
            </x-eligify::ui.select>
            <div class="inline-flex rounded-lg overflow-hidden border border-gray-300 shadow-sm">
                <button type="button" wire:click="setView('list')" class="px-4 py-2.5 text-sm font-medium transition-colors {{ $view==='list' ? 'bg-primary-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                    </svg>
                </button>
                <button type="button" wire:click="setView('grid')" class="px-4 py-2.5 text-sm font-medium border-l border-gray-300 transition-colors {{ $view==='grid' ? 'bg-primary-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div class="bg-white border border-gray-100 rounded-xl shadow-sm overflow-hidden">
        @if ($items->count() === 0)
            <div class="text-center py-16">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                    </svg>
                </div>
                <h3 class="text-base font-semibold text-gray-900 mb-1">No rules found</h3>
                <p class="text-sm text-gray-500">Try adjusting your search or filters</p>
            </div>
        @else
            @if ($view === 'list')
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-linear-to-r from-gray-50 to-slate-50 border-b border-gray-200">
                            <tr>
                                <th class="text-left px-6 py-4 font-semibold text-gray-700">Criteria</th>
                                <th class="text-left px-6 py-4 font-semibold text-gray-700">Field</th>
                                <th class="text-left px-6 py-4 font-semibold text-gray-700">Operator</th>
                                <th class="text-left px-6 py-4 font-semibold text-gray-700">Value</th>
                                <th class="text-left px-6 py-4 font-semibold text-gray-700">Status</th>
                                <th class="text-left px-6 py-4 font-semibold text-gray-700">Order</th>
                                <th class="text-left px-6 py-4 font-semibold text-gray-700">Updated</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($items as $rule)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 font-medium text-gray-900">{{ optional($rule->criteria)->name ?? '—' }}</td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-lg bg-violet-50 text-violet-700">
                                            {{ $rule->field }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <code class="px-2 py-1 bg-gray-100 rounded text-xs font-mono">{{ $rule->operator }}</code>
                                    </td>
                                    <td class="px-6 py-4 text-gray-600 max-w-xs truncate">{{ is_array($rule->value) ? json_encode($rule->value) : (string) $rule->value }}</td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-lg {{ $rule->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                                            <span class="w-1.5 h-1.5 rounded-full mr-1.5 {{ $rule->is_active ? 'bg-green-600' : 'bg-gray-600' }}"></span>
                                            {{ $rule->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center justify-center w-7 h-7 bg-blue-50 text-blue-700 rounded-lg font-semibold text-xs">{{ $rule->order }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-gray-500 text-xs">{{ optional($rule->updated_at)->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6">
                    @foreach ($items as $rule)
                        <div class="border border-gray-100 rounded-xl p-5 hover:shadow-xl hover:border-violet-200 transition-all duration-300 bg-white">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <div class="text-xs font-semibold text-gray-500 mb-2">{{ optional($rule->criteria)->name ?? 'Unassigned' }}</div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-lg bg-violet-50 text-violet-700">
                                            {{ $rule->field }}
                                        </span>
                                        <code class="px-2 py-1 bg-gray-100 rounded text-xs font-mono text-gray-700">{{ $rule->operator }}</code>
                                    </div>
                                    <div class="text-xs text-gray-600 mt-2 break-words">{{ is_array($rule->value) ? json_encode($rule->value) : (string) $rule->value }}</div>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-lg ml-3 {{ $rule->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                                    <span class="w-1.5 h-1.5 rounded-full mr-1.5 {{ $rule->is_active ? 'bg-green-600' : 'bg-gray-600' }}"></span>
                                    {{ $rule->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-gray-500">Order:</span>
                                    <span class="inline-flex items-center justify-center w-6 h-6 bg-blue-50 text-blue-700 rounded-lg font-semibold text-xs">{{ $rule->order }}</span>
                                </div>
                                <span class="text-xs text-gray-400">{{ optional($rule->updated_at)->diffForHumans() }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">{{ $items->links() }}</div>
        @endif
    </div>
</div>
