<div class="space-y-6">
    <div class="bg-white border rounded">
        <div class="px-4 py-3 border-b font-semibold">UI</div>
        <div class="p-4 space-y-3">
            <label class="flex items-center gap-2 text-sm"><x-eligify::ui.checkbox wire:model.live="ui_enabled" /> Enable Dashboard</label>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-sm text-gray-600">Route Prefix</label>
                    <x-eligify::ui.input type="text" wire:model.live="ui_route_prefix" />
                </div>
                <div>
                    <label class="block text-sm text-gray-600">Brand Name</label>
                    <x-eligify::ui.input type="text" wire:model.live="ui_brand_name" />
                </div>
                <div class="flex items-end">
                    <label class="flex items-center gap-2 text-sm"><x-eligify::ui.checkbox wire:model.live="ui_assets_use_cdn" /> Use CDN Assets</label>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white border rounded">
        <div class="px-4 py-3 border-b font-semibold">Scoring</div>
        <div class="p-4 grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="block text-sm text-gray-600">Pass Threshold</label>
                <x-eligify::ui.input type="number" min="0" max="100" wire:model.live="scoring_pass_threshold" />
                @error('scoring_pass_threshold')<div class="text-xs text-red-600">{{ $message }}</div>@enderror
            </div>
            <div>
                <label class="block text-sm text-gray-600">Method</label>
                <x-eligify::ui.select wire:model.live="scoring_method">
                    <option value="weighted">Weighted</option>
                    <option value="simple">Simple</option>
                </x-eligify::ui.select>
            </div>
        </div>
    </div>

    <div class="bg-white border rounded">
        <div class="px-4 py-3 border-b font-semibold">Evaluation</div>
        <div class="p-4 grid grid-cols-1 md:grid-cols-3 gap-3">
            <label class="flex items-center gap-2 text-sm"><x-eligify::ui.checkbox wire:model.live="evaluation_cache_enabled" /> Cache Enabled</label>
            <div>
                <label class="block text-sm text-gray-600">Cache TTL (minutes)</label>
                <x-eligify::ui.input type="number" min="1" wire:model.live="evaluation_cache_ttl" />
                @error('evaluation_cache_ttl')<div class="text-xs text-red-600">{{ $message }}</div>@enderror
            </div>
            <label class="flex items-center gap-2 text-sm"><x-eligify::ui.checkbox wire:model.live="evaluation_fail_fast" /> Fail Fast</label>
        </div>
    </div>

    <div class="bg-white border rounded">
        <div class="px-4 py-3 border-b font-semibold">Audit</div>
        <div class="p-4 grid grid-cols-1 md:grid-cols-3 gap-3">
            <label class="flex items-center gap-2 text-sm"><x-eligify::ui.checkbox wire:model.live="audit_enabled" /> Audit Enabled</label>
            <div>
                <label class="block text-sm text-gray-600">Retention Days</label>
                <x-eligify::ui.input type="number" min="1" wire:model.live="audit_retention_days" />
                @error('audit_retention_days')<div class="text-xs text-red-600">{{ $message }}</div>@enderror
            </div>
            <label class="flex items-center gap-2 text-sm"><x-eligify::ui.checkbox wire:model.live="audit_auto_cleanup" /> Auto Cleanup</label>
        </div>
    </div>

    <div class="bg-white border rounded">
        <div class="px-4 py-3 border-b font-semibold">Workflow</div>
        <div class="p-4 grid grid-cols-1 md:grid-cols-3 gap-3">
            <label class="flex items-center gap-2 text-sm"><x-eligify::ui.checkbox wire:model.live="workflow_enabled" /> Workflow Enabled</label>
            <label class="flex items-center gap-2 text-sm"><x-eligify::ui.checkbox wire:model.live="workflow_async" /> Async Callbacks</label>
            <div>
                <label class="block text-sm text-gray-600">Queue Name</label>
                <x-eligify::ui.input type="text" wire:model.live="workflow_queue_name" />
            </div>
        </div>
    </div>

    <div class="flex justify-end">
        <x-eligify::ui.button type="button" wire:click="save">Save Settings</x-eligify::ui.button>
    </div>
</div>
