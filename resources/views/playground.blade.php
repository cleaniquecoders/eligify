<x-eligify::layout>
    <x-slot:title>Playground</x-slot:title>
    <x-slot:subtitle>Test and experiment with eligibility rules in real-time</x-slot:subtitle>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-indigo-50 to-purple-50">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-500/30">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-base font-semibold text-gray-900">Interactive Testing Environment</h2>
                    <p class="text-xs text-gray-600 mt-0.5">Run eligibility checks and see results instantly</p>
                </div>
            </div>
        </div>
        <div class="p-6">
            <livewire:eligify.playground />
        </div>
    </div>
</x-eligify::layout>
