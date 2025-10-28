<x-eligify::layout>
    <x-slot:title>Create Rule</x-slot:title>
    <div class="bg-white border border-gray-200 rounded p-4">
        <livewire:eligify.rule-editor mode="create" :criteria-id="$criteriaId" />
    </div>
</x-eligify::layout>
