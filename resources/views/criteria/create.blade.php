<x-eligify::layout>
    <x-slot:title>New Criteria</x-slot:title>
    <x-slot:subtitle>Define a new eligibility criteria for your system</x-slot:subtitle>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-indigo-50">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/30">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-semibold text-gray-900">Create Criteria</h2>
                        <p class="text-xs text-gray-600 mt-0.5">Build your eligibility conditions</p>
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
            <livewire:eligify::criteria-editor mode="create" />
        </div>
    </div>
</x-eligify::layout>
