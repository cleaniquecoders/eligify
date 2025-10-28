<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white rounded-xl p-4 shadow-sm border border-gray-100">
        <div class="flex items-center gap-3 flex-1">
            <div class="relative flex-1 max-w-2xl">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <input type="text" placeholder="Search audit logs (event/type/ip/ua)..." class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all" wire:model.live.debounce.300ms="search">
            </div>
        </div>
        <div class="flex items-center gap-3">
            <select class="px-3 py-2.5 border border-gray-300 rounded-lg text-sm font-medium bg-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all" wire:model.live="perPage">
                <option value="10">10 per page</option>
                <option value="25">25 per page</option>
                <option value="50">50 per page</option>
            </select>
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <h3 class="text-base font-semibold text-gray-900 mb-1">No audit logs found</h3>
                <p class="text-sm text-gray-500">Try adjusting your search</p>
            </div>
        @else
            @if ($view === 'list')
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gradient-to-r from-gray-50 to-slate-50 border-b border-gray-200">
                            <tr>
                                <th class="text-left px-6 py-4 font-semibold text-gray-700">Event</th>
                                <th class="text-left px-6 py-4 font-semibold text-gray-700">Auditable</th>
                                <th class="text-left px-6 py-4 font-semibold text-gray-700">Details</th>
                                <th class="text-left px-6 py-4 font-semibold text-gray-700">When</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($items as $log)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-lg bg-emerald-50 text-emerald-700">
                                            {{ $log->event }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 font-medium text-gray-900">{{ $log->auditable_type }} <span class="text-gray-500">#{{ $log->auditable_id }}</span></td>
                                    <td class="px-6 py-4 text-gray-600">{{ $log->getDescription() }}</td>
                                    <td class="px-6 py-4 text-gray-500 text-xs">{{ optional($log->created_at)->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6">
                    @foreach ($items as $log)
                        <div class="border border-gray-100 rounded-xl p-5 hover:shadow-xl hover:border-emerald-200 transition-all duration-300 bg-white">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-lg bg-emerald-50 text-emerald-700">
                                        {{ $log->event }}
                                    </span>
                                    <div class="font-semibold text-gray-900 mt-2">{{ $log->auditable_type }} <span class="text-gray-500">#{{ $log->auditable_id }}</span></div>
                                    <div class="text-xs text-gray-600 mt-2">{{ $log->getDescription() }}</div>
                                </div>
                            </div>
                            @if ($log->ip_address)
                                <div class="flex items-center gap-2 text-xs text-gray-500 mt-3 pt-3 border-t border-gray-100">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                                    </svg>
                                    {{ $log->ip_address }}
                                </div>
                            @endif
                            <div class="text-xs text-gray-400 mt-2">{{ optional($log->created_at)->diffForHumans() }}</div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">{{ $items->links() }}</div>
        @endif
    </div>
</div>
