<div class="space-y-3">
    <form wire:submit.prevent="save" class="space-y-4">
        @if (session('status'))
            <div class="p-3 rounded bg-green-100 text-green-800 text-sm">{{ session('status') }}</div>
        @endif

        <div>
            <label class="block text-sm font-medium mb-1">Name</label>
            <x-eligify::ui.input type="text" wire:model.blur="name" required />
            @error('name') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Description</label>
            <x-eligify::ui.textarea rows="4" wire:model.blur="description" />
            @error('description') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="block text-sm font-medium mb-1">Type</label>
                <x-eligify::ui.input type="text" wire:model.blur="type" placeholder="e.g. subscription, feature, policy" />
                @error('type') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Group</label>
                <x-eligify::ui.input type="text" wire:model.blur="group" placeholder="e.g. billing, access-control" />
                @error('group') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Category</label>
                <x-eligify::ui.input type="text" wire:model.blur="category" placeholder="e.g. basic, premium, enterprise" />
                @error('category') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Tags</label>
            <x-eligify::ui.input type="text" wire:model.blur="tags" placeholder="comma,separated,tags" />
            <p class="text-xs text-gray-500 mt-1">Enter comma-separated values. Tags are normalized to lowercase.</p>
            @error('tags') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
        </div>

        <div class="flex items-center gap-2">
            <x-eligify::ui.checkbox wire:model.blur="is_active" />
            <label class="text-sm">Active</label>
            @error('is_active') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
        </div>

        <div class="flex items-center gap-2">
            <x-eligify::ui.button as="a" href="{{ route('eligify.criteria.index') }}" variant="secondary">Cancel</x-eligify::ui.button>
            <x-eligify::ui.button type="submit">Save</x-eligify::ui.button>
        </div>
    </form>
</div>
