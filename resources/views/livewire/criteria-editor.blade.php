<div class="space-y-3">
    <form wire:submit.prevent="save" class="space-y-4">
        @if (session('status'))
            <div class="p-3 rounded bg-green-100 text-green-800 text-sm">{{ session('status') }}</div>
        @endif

        <div>
            <label class="block text-sm font-medium mb-1">Name</label>
            <input type="text" class="w-full border rounded px-3 py-2" wire:model.defer="name" required>
            @error('name') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Description</label>
            <textarea rows="4" class="w-full border rounded px-3 py-2" wire:model.defer="description"></textarea>
            @error('description') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" class="rounded" wire:model.defer="is_active">
            <label class="text-sm">Active</label>
            @error('is_active') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('eligify.criteria.index') }}" class="px-4 py-2 border rounded">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-gray-900 text-white rounded">Save</button>
        </div>
    </form>
</div>
