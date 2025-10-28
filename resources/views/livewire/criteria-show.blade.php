<div class="space-y-3">
    <div class="flex items-center justify-between">
        <div>
            <div class="font-semibold text-lg">{{ $criteria->name }}</div>
            <div class="text-xs text-gray-500">Slug: {{ $criteria->slug }}</div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('eligify.criteria.edit', $criteria->id) }}" class="px-3 py-2 text-sm border rounded">Edit</a>
            <button type="button" wire:click="delete" class="px-3 py-2 text-sm border rounded text-red-700 border-red-300" onclick="return confirm('Delete this criteria?')">Delete</button>
            <a href="{{ route('eligify.criteria.index') }}" class="px-3 py-2 text-sm border rounded">Back</a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-white border rounded p-4">
        <div class="md:col-span-2">
            <div class="text-sm text-gray-700 whitespace-pre-wrap">{{ $criteria->description }}</div>
        </div>
        <div>
            <div class="text-sm">
                <div class="mb-2">Status:
                    <span class="inline-flex items-center px-2 py-0.5 text-xs rounded {{ $criteria->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                        {{ $criteria->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                <div class="mb-2">Rules: <span class="font-semibold">{{ $criteria->rules_count }}</span></div>
                <div class="mb-2">Evaluations: <span class="font-semibold">{{ $criteria->evaluations_count }}</span></div>
                <div class="mb-2">Created: <span class="text-gray-500">{{ optional($criteria->created_at)->toDayDateTimeString() }}</span></div>
                <div class="mb-2">Updated: <span class="text-gray-500">{{ optional($criteria->updated_at)->diffForHumans() }}</span></div>
            </div>
        </div>
    </div>

    <div class="bg-white border rounded p-4">
        <h3 class="font-semibold mb-2">Meta</h3>
        <pre class="text-xs bg-gray-50 rounded p-3 overflow-auto">{{ json_encode($criteria->meta ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    </div>
</div>
