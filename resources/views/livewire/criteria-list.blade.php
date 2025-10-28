<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white rounded-xl p-4 shadow-sm border border-gray-100">
        <div class="flex items-center gap-3 flex-1">
            <div class="relative flex-1 max-w-md">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <input type="text" placeholder="Search criteria..." class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all" wire:model.live.debounce.300ms="search">
            </div>
            <label class="inline-flex items-center gap-2 text-sm text-gray-700 bg-gray-50 px-4 py-2.5 border border-gray-200 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer">
                <input type="checkbox" class="rounded text-primary-600 focus:ring-primary-500 border-gray-300" wire:model.live="onlyActive">
                <span class="font-medium">Active only</span>
            </label>
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
                <h3 class="text-base font-semibold text-gray-900 mb-1">No criteria found</h3>
                <p class="text-sm text-gray-500">Try adjusting your search or filters</p>
            </div>
        @else
            @if ($view === 'list')
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gradient-to-r from-gray-50 to-slate-50 border-b border-gray-200">
                            <tr>
                                <th class="text-left px-6 py-4 font-semibold text-gray-700">Name</th>
                                <th class="text-left px-6 py-4 font-semibold text-gray-700">Status</th>
                                <th class="text-left px-6 py-4 font-semibold text-gray-700">Rules</th>
                                <th class="text-left px-6 py-4 font-semibold text-gray-700">Evaluations</th>
                                <th class="text-left px-6 py-4 font-semibold text-gray-700">Updated</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($items as $criteria)
                                <tr class="hover:bg-gray-50 transition-colors group">
                                    <td class="px-6 py-4">
                                        <a class="text-primary-600 hover:text-primary-700 font-medium hover:underline transition-colors" href="{{ route('eligify.criteria.show', $criteria->id) }}">{{ $criteria->name }}</a>
                                        <div class="text-xs text-gray-500 mt-1">{{ $criteria->description }}</div>
                                        <div class="mt-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <a class="text-xs text-primary-600 hover:text-primary-700 font-medium inline-flex items-center gap-1" href="{{ route('eligify.criteria.edit', $criteria->id) }}">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                                Edit
                                            </a>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-lg {{ $criteria->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                                            <span class="w-1.5 h-1.5 rounded-full mr-1.5 {{ $criteria->is_active ? 'bg-green-600' : 'bg-gray-600' }}"></span>
                                            {{ $criteria->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center justify-center w-8 h-8 bg-blue-50 text-blue-700 rounded-lg font-semibold">{{ $criteria->rules_count }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center justify-center w-8 h-8 bg-purple-50 text-purple-700 rounded-lg font-semibold">{{ $criteria->evaluations_count }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-gray-500 text-xs">{{ optional($criteria->updated_at)->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6">
                    @foreach ($items as $criteria)
                        <div class="border border-gray-100 rounded-xl p-5 hover:shadow-xl hover:border-primary-200 transition-all duration-300 group bg-white">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <a class="text-base font-semibold text-gray-900 hover:text-primary-600 transition-colors" href="{{ route('eligify.criteria.show', $criteria->id) }}">{{ $criteria->name }}</a>
                                    <div class="text-xs text-gray-500 mt-2 line-clamp-2">{{ \Illuminate\Support\Str::limit($criteria->description, 120) }}</div>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-lg ml-3 {{ $criteria->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                                    <span class="w-1.5 h-1.5 rounded-full mr-1.5 {{ $criteria->is_active ? 'bg-green-600' : 'bg-gray-600' }}"></span>
                                    {{ $criteria->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            <div class="flex items-center gap-4 mb-4">
                                <div class="flex items-center gap-2 text-sm">
                                    <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center">
                                        <span class="text-blue-700 font-semibold text-xs">{{ $criteria->rules_count }}</span>
                                    </div>
                                    <span class="text-gray-600">Rules</span>
                                </div>
                                <div class="flex items-center gap-2 text-sm">
                                    <div class="w-8 h-8 bg-purple-50 rounded-lg flex items-center justify-center">
                                        <span class="text-purple-700 font-semibold text-xs">{{ $criteria->evaluations_count }}</span>
                                    </div>
                                    <span class="text-gray-600">Evals</span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                                <span class="text-xs text-gray-400">{{ optional($criteria->updated_at)->diffForHumans() }}</span>
                                <a class="text-xs text-primary-600 hover:text-primary-700 font-medium inline-flex items-center gap-1 group-hover:gap-2 transition-all" href="{{ route('eligify.criteria.edit', $criteria->id) }}">
                                    Edit
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">{{ $items->links() }}</div>
        @endif
    </div>
</div>
