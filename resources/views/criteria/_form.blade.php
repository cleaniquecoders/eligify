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
        <input type="text" name="name" value="{{ old('name', $criteria->name) }}" class="w-full border rounded px-3 py-2" required>
        @error('name') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium mb-1">Description</label>
        <textarea name="description" rows="4" class="w-full border rounded px-3 py-2">{{ old('description', $criteria->description) }}</textarea>
        @error('description') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div class="flex items-center gap-2">
        <input type="checkbox" name="is_active" value="1" id="is_active" class="rounded" {{ old('is_active', $criteria->is_active) ? 'checked' : '' }}>
        <label for="is_active" class="text-sm">Active</label>
        @error('is_active') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium mb-1">Meta (JSON)</label>
        <textarea name="meta_json" rows="4" class="w-full border rounded px-3 py-2" placeholder="{\n  &quot;category&quot;: &quot;finance&quot;\n}">{{ old('meta_json', isset($criteria->meta) ? json_encode($criteria->meta, JSON_PRETTY_PRINT) : '') }}</textarea>
        <p class="text-xs text-gray-500 mt-1">Optional additional metadata for this criteria.</p>
    </div>

    <div class="flex items-center gap-2">
        <a href="{{ route('eligify.criteria.index') }}" class="px-4 py-2 border rounded">Cancel</a>
        <button type="submit" class="px-4 py-2 bg-gray-900 text-white rounded">Save</button>
    </div>
</form>
