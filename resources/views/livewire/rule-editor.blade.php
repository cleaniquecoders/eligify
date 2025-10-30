<div class="space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold">{{ $mode === 'edit' ? 'Edit Rule' : 'Create Rule' }}</h2>
            @if($criteria)
                <p class="text-sm text-gray-600">For criteria: {{ $criteria->name }}</p>
            @endif
        </div>
        <a href="{{ route('eligify.criteria.show', $criteriaId) }}" class="px-3 py-2 text-sm border rounded">Cancel</a>
    </div>

    <form wire:submit.prevent="save" class="space-y-4 bg-white border rounded p-4">
        <!-- Mapping Selection & Manual Input Toggle -->
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900">Field Selection Method</h3>
                <button
                    type="button"
                    wire:click="toggleManualInput"
                    class="px-3 py-1 text-xs border rounded {{ $useManualInput ? 'bg-blue-50 border-blue-300 text-blue-700' : 'bg-white border-gray-300 text-gray-700' }}"
                >
                    {{ $useManualInput ? '✓ Manual Input' : 'Use Manual Input' }}
                </button>
            </div>

            @if(!$useManualInput)
                <!-- Mapping Selection -->
                <div>
                    <label for="selectedMapping" class="block text-sm font-medium text-gray-700 mb-1">
                        1. Select Model Mapping <span class="text-gray-400">(Optional)</span>
                    </label>
                    <select
                        id="selectedMapping"
                        wire:model.live="selectedMapping"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">-- Select a mapping or use manual input --</option>
                        @foreach($this->mappingClasses as $class => $meta)
                            <option value="{{ $class }}">{{ $meta['name'] }} ({{ $meta['model'] }})</option>
                        @endforeach
                    </select>
                    @if($selectedMapping)
                        <p class="text-xs text-gray-600 mt-1">
                            📋 {{ $this->mappingClasses[$selectedMapping]['description'] ?? 'No description available' }}
                        </p>
                    @endif
                </div>

                @if($selectedMapping)
                    <!-- Field Selection from Mapping -->
                    <div>
                        <label for="field" class="block text-sm font-medium text-gray-700 mb-1">
                            2. Select Field from Mapping
                        </label>
                        <select
                            id="field"
                            wire:model.live="field"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        >
                            <option value="">-- Select a field --</option>
                            @php
                                $fields = $this->availableFields;
                                $categories = ['attribute' => 'Model Attributes', 'computed' => 'Computed Fields', 'relationship' => 'Relationships'];
                            @endphp
                            @foreach($categories as $category => $label)
                                @php
                                    $categoryFields = array_filter($fields, fn($f) => $f['category'] === $category);
                                @endphp
                                @if(!empty($categoryFields))
                                    <optgroup label="{{ $label }}">
                                        @foreach($categoryFields as $fieldName => $fieldMeta)
                                            <option value="{{ $fieldName }}">
                                                {{ $fieldName }} ({{ $fieldMeta['type'] }})
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endif
                            @endforeach
                        </select>
                        @error('field') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        @if($field && isset($this->availableFields[$field]))
                            <p class="text-xs text-gray-600 mt-1">
                                ℹ️ {{ $this->availableFields[$field]['description'] ?? 'No description' }}
                            </p>
                        @endif
                    </div>
                @endif
            @endif

            @if($useManualInput || !$selectedMapping)
                <!-- Manual Field Input -->
                <div>
                    <label for="field_manual" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ $useManualInput ? 'Field Name' : '2. Or Enter Field Manually' }}
                    </label>
                    <input
                        type="text"
                        id="field_manual"
                        wire:model="field"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="e.g., income, age, credit_score"
                        required
                    >
                    @error('field') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    <p class="text-xs text-gray-500 mt-1">Enter the field name to evaluate (e.g., model attribute or data key)</p>
                </div>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Field Type -->
            <div>
                <label for="fieldType" class="block text-sm font-medium text-gray-700 mb-1">
                    Field Type <span class="text-gray-400">(Optional)</span>
                </label>
                <select
                    id="fieldType"
                    wire:model.live="fieldType"
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">-- Select Type --</option>
                    @foreach($this->fieldTypes as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('fieldType') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                <p class="text-xs text-gray-500 mt-1">
                    @if($fieldType)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-indigo-100 text-indigo-800">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Filters operators & value input
                        </span>
                    @else
                        Filters available operators & changes value input type
                    @endif
                </p>
            </div>

            <!-- Priority -->
            <div>
                <label for="priority" class="block text-sm font-medium text-gray-700 mb-1">Priority <span class="text-gray-400">(Optional)</span></label>
                <select
                    id="priority"
                    wire:model="priority"
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">-- Select Priority --</option>
                    @foreach($this->priorities as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('priority') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                <p class="text-xs text-gray-500 mt-1">Auto-sets weight based on importance</p>
            </div>
        </div>

        <!-- Field Type Info Box -->
        @if($fieldType)
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-blue-600 mr-2 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-blue-900">
                            Field Type: <strong>{{ $this->fieldTypes[$fieldType] }}</strong>
                        </p>
                        <ul class="mt-2 text-xs text-blue-800 space-y-1">
                            <li class="flex items-center">
                                <svg class="w-3 h-3 mr-1.5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <span>Input type: <code class="px-1 py-0.5 bg-blue-100 rounded">{{ $this->valueInputType }}</code></span>
                            </li>
                            <li class="flex items-center">
                                <svg class="w-3 h-3 mr-1.5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <span>{{ count($this->availableOperators) }} compatible operator(s)</span>
                            </li>
                            @if($this->isBooleanInput)
                                <li class="flex items-center">
                                    <svg class="w-3 h-3 mr-1.5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    <span>Using True/False selection</span>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <!-- Operator -->
        <div>
            <label for="operator" class="block text-sm font-medium text-gray-700 mb-1">Operator</label>
            <select
                id="operator"
                wire:model="operator"
                class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                required
            >
                @foreach($this->availableOperators as $op => $label)
                    <option value="{{ $op }}">{{ $label }}</option>
                @endforeach
            </select>
            @error('operator') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            @if($fieldType)
                <p class="text-xs text-gray-500 mt-1">Operators filtered for {{ $this->fieldTypes[$fieldType] ?? 'selected' }} type</p>
            @endif
        </div>

        <!-- Value -->
        <div>
            <label for="value" class="block text-sm font-medium text-gray-700 mb-1">Value</label>

            @if($this->isBooleanInput && !$this->requiresMultipleValues)
                <!-- Boolean Input -->
                <div class="flex items-center gap-4 py-2">
                    <label class="inline-flex items-center">
                        <input
                            type="radio"
                            wire:model="value"
                            value="true"
                            class="rounded-full border-gray-300 text-blue-600 focus:ring-blue-500"
                        >
                        <span class="ml-2 text-sm">True</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input
                            type="radio"
                            wire:model="value"
                            value="false"
                            class="rounded-full border-gray-300 text-blue-600 focus:ring-blue-500"
                        >
                        <span class="ml-2 text-sm">False</span>
                    </label>
                </div>
            @elseif($this->requiresMultipleValues || $fieldType === 'array')
                <!-- Array/Multiple Values Input -->
                <textarea
                    id="value"
                    wire:model="value"
                    rows="3"
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono"
                    placeholder="{{ $this->valuePlaceholder }}"
                    required
                ></textarea>
            @else
                <!-- Standard Input (Text, Number, Date) -->
                <input
                    type="{{ $this->valueInputType }}"
                    id="value"
                    wire:model="value"
                    @if($this->valueInputType === 'number')
                        step="{{ $this->numericStep }}"
                    @endif
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="{{ $this->valuePlaceholder }}"
                    required
                >
            @endif

            @error('value') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            <p class="text-xs text-gray-500 mt-1">
                {{ $this->valueHelpText }}
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Weight -->
            <div>
                <label for="weight" class="block text-sm font-medium text-gray-700 mb-1">Weight</label>
                <input
                    type="number"
                    id="weight"
                    wire:model="weight"
                    min="0"
                    max="100"
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                >
                @error('weight') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                <p class="text-xs text-gray-500 mt-1">Importance of this rule (0-100){{ $priority ? ' - Auto-set from priority' : '' }}</p>
            </div>

            <!-- Order -->
            <div>
                <label for="order" class="block text-sm font-medium text-gray-700 mb-1">Order</label>
                <input
                    type="number"
                    id="order"
                    wire:model="order"
                    min="0"
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                >
                @error('order') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                <p class="text-xs text-gray-500 mt-1">Execution order (lower executes first)</p>
            </div>
        </div>

        <!-- Is Active -->
        <div class="flex items-center gap-2">
            <input
                type="checkbox"
                id="is_active"
                wire:model="is_active"
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
            >
            <label for="is_active" class="text-sm font-medium text-gray-700">Active</label>
            @error('is_active') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
        </div>

        <!-- Submit Button -->
        <div class="flex items-center justify-end gap-2 pt-4 border-t">
            <a href="{{ route('eligify.criteria.show', $criteriaId) }}" class="px-4 py-2 text-sm border rounded">Cancel</a>
            <button
                type="submit"
                class="px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
                {{ $mode === 'edit' ? 'Update Rule' : 'Create Rule' }}
            </button>
        </div>
    </form>
</div>
