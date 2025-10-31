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
                <div class="mb-2">Type:
                    @if(!empty($criteria->type))
                        <span class="inline-flex items-center px-2 py-0.5 text-xs rounded bg-sky-50 text-sky-700 border border-sky-200">{{ $criteria->type }}</span>
                    @else
                        <span class="text-gray-400 text-xs">-</span>
                    @endif
                </div>
                <div class="mb-2">Group:
                    @if(!empty($criteria->group))
                        <span class="inline-flex items-center px-2 py-0.5 text-xs rounded bg-amber-50 text-amber-700 border border-amber-200">{{ $criteria->group }}</span>
                    @else
                        <span class="text-gray-400 text-xs">-</span>
                    @endif
                </div>
                <div class="mb-2">Category:
                    @if(!empty($criteria->category))
                        <span class="inline-flex items-center px-2 py-0.5 text-xs rounded bg-emerald-50 text-emerald-700 border border-emerald-200">{{ $criteria->category }}</span>
                    @else
                        <span class="text-gray-400 text-xs">-</span>
                    @endif
                </div>
                <div class="mb-2">Tags:
                    @if(is_array($criteria->tags) && count($criteria->tags))
                        <span class="inline-flex flex-wrap gap-1">
                            @foreach($criteria->tags as $tag)
                                <span class="inline-flex items-center px-2 py-0.5 text-[10px] rounded bg-gray-100 text-gray-700 border border-gray-200">#{{ $tag }}</span>
                            @endforeach
                        </span>
                    @else
                        <span class="text-gray-400 text-xs">-</span>
                    @endif
                </div>
                <div class="mb-2">Rules: <span class="font-semibold">{{ $criteria->rules_count }}</span></div>
                <div class="mb-2">Evaluations: <span class="font-semibold">{{ $criteria->evaluations_count }}</span></div>
                <div class="mb-2">Created: <span class="text-gray-500">{{ optional($criteria->created_at)->toDayDateTimeString() }}</span></div>
                <div class="mb-2">Updated: <span class="text-gray-500">{{ optional($criteria->updated_at)->diffForHumans() }}</span></div>
            </div>
        </div>
    </div>

    <!-- Rules Section -->
    <div class="bg-white border rounded p-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-lg">Rules</h3>
            <a href="{{ route('eligify.rules.create', ['criteria_id' => $criteria->id]) }}" class="px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">
                Add Rule
            </a>
        </div>

        @if(session()->has('rule_status'))
            <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-800 rounded text-sm">
                {{ session('rule_status') }}
            </div>
        @endif

        @if($rules->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Field</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Operator</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Value</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Weight</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($rules as $rule)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm">{{ $rule->order }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="font-medium">{{ $rule->field }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if(isset($rule->meta['field_type']))
                                        @php
                                            $fieldType = \CleaniqueCoders\Eligify\Enums\FieldType::tryFrom($rule->meta['field_type']);
                                        @endphp
                                        @if($fieldType)
                                            <span class="inline-flex items-center px-2 py-0.5 text-xs rounded bg-indigo-100 text-indigo-800">
                                                {{ $fieldType->label() }}
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-gray-400 text-xs">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @php
                                        $operator = \CleaniqueCoders\Eligify\Enums\RuleOperator::tryFrom($rule->operator);
                                    @endphp
                                    <code class="px-2 py-1 bg-gray-100 rounded text-xs" title="{{ $operator?->description() ?? '' }}">
                                        {{ $operator?->label() ?? $rule->operator }}
                                    </code>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if(is_array($rule->value))
                                        <code class="px-2 py-1 bg-blue-50 text-blue-800 rounded text-xs">
                                            {{ json_encode($rule->value) }}
                                        </code>
                                    @else
                                        <span class="text-gray-700">{{ $rule->value }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if(isset($rule->meta['priority']))
                                        @php
                                            $priority = \CleaniqueCoders\Eligify\Enums\RulePriority::tryFrom($rule->meta['priority']);
                                        @endphp
                                        @if($priority)
                                            <span class="inline-flex items-center px-2 py-0.5 text-xs rounded font-medium"
                                                  style="background-color: {{ $priority->getColor() }}22; color: {{ $priority->getColor() }};">
                                                {{ $priority->label() }}
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-gray-400 text-xs">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded text-xs font-medium">
                                        {{ $rule->weight }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <button
                                        wire:click="toggleRuleStatus({{ $rule->id }})"
                                        class="inline-flex items-center px-2 py-0.5 text-xs rounded {{ $rule->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}"
                                    >
                                        {{ $rule->is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </td>
                                <td class="px-4 py-3 text-sm text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('eligify.rules.edit', ['id' => $rule->id]) }}"
                                           class="text-blue-600 hover:text-blue-800 text-xs">
                                            Edit
                                        </a>
                                        <button
                                            type="button"
                                            wire:click="deleteRule({{ $rule->id }})"
                                            onclick="return confirm('Delete this rule?')"
                                            class="text-red-600 hover:text-red-800 text-xs">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $rules->links() }}
            </div>
        @else
            <div class="text-center py-8 text-gray-500">
                <p class="mb-4">No rules defined yet.</p>
                <a href="{{ route('eligify.rules.create', ['criteria_id' => $criteria->id]) }}" class="inline-flex items-center px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">
                    Create First Rule
                </a>
            </div>
        @endif
    </div>
</div>
