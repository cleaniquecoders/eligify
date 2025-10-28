<x-eligify::layout>
    <x-slot:title>Audit Log</x-slot:title>
    <x-slot:subtitle>Track and review all eligibility evaluations and decisions</x-slot:subtitle>

    @if (class_exists(\Livewire\Livewire::class))
        <livewire:eligify.audit-log-list />
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
                    Livewire is not installed. Please install <code class="px-2 py-0.5 bg-gray-100 rounded text-xs font-mono">livewire/livewire</code> to enable audit log listing.
                </p>
            </div>
        </div>
            @endif
        </div>
    </div>
</x-eligify::layout>
