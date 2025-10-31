<form method="POST" action="{{ $action }}" class="space-y-4">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    @if (session('status'))
        <div class="p-3 rounded bg-green-100 text-green-800 text-sm">{{ session('status') }}</div>
    @endif

    <div>
        <label class="block text-sm font-medium mb-1">Name</label>
        <x-eligify::ui.input type="text" name="name" value="{{ old('name', $criteria->name) }}" required />
        @error('name') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium mb-1">Description</label>
        <x-eligify::ui.textarea name="description" rows="4">{{ old('description', $criteria->description) }}</x-eligify::ui.textarea>
        @error('description') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div class="flex items-center gap-2">
        <x-eligify::ui.checkbox name="is_active" value="1" id="is_active" {{ old('is_active', $criteria->is_active) ? 'checked' : '' }} />
        <label for="is_active" class="text-sm">Active</label>
        @error('is_active') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium mb-1">Meta (JSON)</label>
        <x-eligify::ui.textarea name="meta_json" rows="4" placeholder="{&#10;  &quot;category&quot;: &quot;finance&quot;&#10;}">{{ old('meta_json', isset($criteria->meta) ? json_encode($criteria->meta, JSON_PRETTY_PRINT) : '') }}</x-eligify::ui.textarea>
        <p class="text-xs text-gray-500 mt-1">Optional additional metadata for this criteria.</p>
    </div>

    <div class="flex items-center gap-2">
        <x-eligify::ui.button as="a" href="{{ route('eligify.criteria.index') }}" variant="secondary">Cancel</x-eligify::ui.button>
        <x-eligify::ui.button type="submit">Save</x-eligify::ui.button>
    </div>
</form>
