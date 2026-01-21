<x-eligify::layout>
    <x-slot:title>Criteria</x-slot:title>
    <x-slot:subtitle>Manage eligibility criteria and conditions for your system</x-slot:subtitle>

    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-gradient-to-br from-primary-500 to-primary-600 rounded-xl flex items-center justify-center shadow-lg shadow-primary-500/30">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
            </div>
            <div>
                <div class="text-sm font-medium text-gray-500">All Criteria</div>
            </div>
        </div>
        <a href="{{ route('eligify.criteria.create') }}" class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white text-sm font-medium rounded-xl shadow-lg shadow-primary-500/30 hover:shadow-primary-500/50 transition-all duration-200 hover:scale-105">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            New Criteria
        </a>
    </div>

    @if (class_exists(\Livewire\Livewire::class))
        <livewire:eligify::criteria-list />
    @else
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
            <div class="text-center">
                <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Livewire Required</h3>
                <p class="text-sm text-gray-600 max-w-md mx-auto">
                    Livewire is not installed. Please install <code class="px-2 py-0.5 bg-gray-100 rounded text-xs font-mono">livewire/livewire</code> to enable criteria listing and management.
                </p>
            </div>
        </div>
    @endif
</x-eligify::layout>
