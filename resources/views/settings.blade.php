<x-eligify::layout>
    <x-slot:title>Settings</x-slot:title>
    <x-slot:subtitle>Configure your Eligify system preferences and options</x-slot:subtitle>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-slate-50 to-gray-50">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-gradient-to-br from-slate-600 to-gray-700 rounded-xl flex items-center justify-center shadow-lg shadow-slate-500/30">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-base font-semibold text-gray-900">System Configuration</h2>
                    <p class="text-xs text-gray-600 mt-0.5">Manage system-wide settings and preferences</p>
                </div>
            </div>
        </div>
        <div class="p-6">
            @if (class_exists(\Livewire\Livewire::class))
                <livewire:eligify::settings-manager />
            @else
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Livewire Required</h3>
                    <p class="text-sm text-gray-600 max-w-md mx-auto">
                        Livewire is not installed. Please install <code class="px-2 py-0.5 bg-gray-100 rounded text-xs font-mono">livewire/livewire</code> to enable settings management.
                    </p>
                </div>
            @endif
        </div>
    </div>
</x-eligify::layout>
