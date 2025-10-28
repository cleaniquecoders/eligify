<x-eligify::layout>
    <x-slot:title>Edit Criteria</x-slot:title>

    <div class="bg-white border border-gray-200 rounded p-4">
        <div class="flex items-center justify-between mb-3">
            <h2 class="font-semibold">Edit Criteria</h2>
        </div>

        <livewire:eligify.criteria-editor mode="edit" :criteria-id="$id" />
    </div>
</x-eligify::layout>
