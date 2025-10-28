<x-eligify::layout>
    <x-slot:title>Audit</x-slot:title>
    @if (class_exists(\Livewire\Livewire::class))
        <livewire:eligify.audit-log-list />
    @else
        <div class="bg-white border border-gray-200 rounded p-4">
            <div class="font-semibold mb-2">Audit Explorer</div>
            <p class="text-sm text-gray-600">Livewire is not installed. Please install livewire/livewire to enable this listing.</p>
        </div>
    @endif
</x-eligify::layout>
