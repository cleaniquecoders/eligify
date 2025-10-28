<x-eligify::layout>
    <x-slot:title>Edit Criteria</x-slot:title>
    <x-slot:subtitle>Modify criteria configuration and rules</x-slot:subtitle>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-amber-50 to-orange-50">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center shadow-lg shadow-amber-500/30">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-semibold text-gray-900">Edit Criteria</h2>
                        <p class="text-xs text-gray-600 mt-0.5">Update criteria settings and conditions</p>
                    </div>
                </div>
                <a href="{{ route('eligify.criteria.index') }}" class="text-sm text-gray-600 hover:text-gray-900 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </a>
            </div>
        </div>
        <div class="p-6">
            <livewire:eligify.criteria-editor mode="edit" :criteria-id="$id" />
        </div>
    </div>
</x-eligify::layout>
