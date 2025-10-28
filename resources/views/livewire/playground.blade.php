<div class="space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Evaluation Playground</h2>
            <p class="text-sm text-gray-600 mt-1">Test your criteria rules with sample data in real-time</p>
        </div>
        <div class="flex gap-2">
            <button
                wire:click="$toggle('showExamples')"
                class="px-4 py-2 text-sm border border-gray-300 rounded hover:bg-gray-50"
            >
                {{ $showExamples ? 'Hide' : 'Show' }} Examples
            </button>
            <button
                wire:click="resetPlayground"
                class="px-4 py-2 text-sm border border-gray-300 rounded hover:bg-gray-50"
            >
                Reset
            </button>
        </div>
    </div>

    <!-- Quick Examples Panel -->
    @if($showExamples)
        <div class="bg-gradient-to-r from-purple-50 to-pink-50 border border-purple-200 rounded-lg p-4">
            <h3 class="font-semibold text-purple-900 mb-3 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z"/>
                </svg>
                Quick Example Data
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                @foreach(['numeric' => '🔢 Numeric', 'string' => '📝 String', 'boolean' => '✓ Boolean', 'mixed' => '🎯 Mixed'] as $key => $label)
                    <button
                        wire:click="loadExample('{{ $key }}')"
                        class="p-3 bg-white border border-purple-300 rounded hover:border-purple-500 hover:shadow-md transition text-left"
                    >
                        <div class="font-medium text-sm text-purple-900">{{ $label }}</div>
                        <div class="text-xs text-purple-600 mt-1">Click to load</div>
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Left Panel: Configuration -->
        <div class="space-y-4">
            <!-- Criteria Selection -->
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Select Criteria
                </label>
                <select
                    wire:model.live="selectedCriteriaId"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">-- Choose a criteria to test --</option>
                    @foreach($this->availableCriteria as $criteria)
                        <option value="{{ $criteria->id }}">
                            {{ $criteria->name }} ({{ $criteria->rules_count }} rules)
                        </option>
                    @endforeach
                </select>

                @if($selectedCriteria)
                    <div class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded">
                        <div class="text-sm font-medium text-blue-900">{{ $selectedCriteria->name }}</div>
                        @if($selectedCriteria->description)
                            <div class="text-xs text-blue-700 mt-1">{{ $selectedCriteria->description }}</div>
                        @endif
                        <div class="flex items-center gap-4 mt-2 text-xs text-blue-600">
                            <span>{{ $selectedCriteria->rules->count() }} active rules</span>
                            <span>•</span>
                            <a href="{{ route('eligify.criteria.show', $selectedCriteria->id) }}"
                               class="underline hover:text-blue-800"
                               target="_blank">
                                View details →
                            </a>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Test Data Input -->
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-sm font-medium text-gray-700">
                        Test Data (JSON)
                    </label>
                    <div class="flex items-center gap-2">
                        @if($selectedCriteria && $selectedCriteria->rules->isNotEmpty())
                            <div
                                wire:click="generateFromRules"
                                class="cursor-pointer text-xs px-2 py-1 bg-green-100 text-green-700 hover:bg-green-200 rounded border border-green-300"
                                title="Generate sample data based on your rules"
                            >
                                ✨ Generate from Rules
                            </div>
                        @endif
                        <button
                            wire:click="formatJson"
                            class="text-xs text-blue-600 hover:text-blue-800"
                            title="Format JSON"
                        >
                            Format
                        </button>
                    </div>
                </div>
                <textarea
                    wire:model.live="testDataJson"
                    rows="12"
                    class="w-full border border-gray-300 rounded px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder='{"field": "value", "nested": {"field": "value"}}'
                >{{ !empty($testDataJson) ? $testDataJson : '' }}</textarea>

                <div class="text-xs text-gray-500 mt-2">
                    @if($selectedCriteria && $selectedCriteria->rules->isNotEmpty())
                        <p class="mb-1">💡 <strong>Tip:</strong> Click "✨ Generate from Rules" to auto-create sample data based on your {{ $selectedCriteria->rules->count() }} rule(s)</p>
                    @elseif($selectedCriteria && $selectedCriteria->rules->isEmpty())
                        <p class="mb-1 text-amber-600">⚠️ This criteria has no active rules. Add some rules first.</p>
                    @endif
                    <p>Enter JSON data to test. Supports nested objects using dot notation (e.g., <code class="px-1 py-0.5 bg-gray-100 rounded">applicant.income</code>)</p>
                </div>
            </div>

            <!-- Evaluate Button -->
            <button
                wire:click="evaluate"
                wire:loading.attr="disabled"
                :disabled="!selectedCriteriaId || !testDataJson"
                class="w-full py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-lg font-medium hover:from-blue-700 hover:to-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition shadow-lg hover:shadow-xl"
            >
                <span wire:loading.remove wire:target="evaluate">
                    🚀 Evaluate Now
                </span>
                <span wire:loading wire:target="evaluate">
                    ⏳ Evaluating...
                </span>
            </button>

            <!-- Error Display -->
            @if($error)
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-red-600 mr-2 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-red-900">Error</div>
                            <div class="text-sm text-red-700 mt-1">{{ $error }}</div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Right Panel: Results -->
        <div class="space-y-4">
            @if($evaluationResult)
                <!-- Overall Result -->
                <div class="bg-white border-2 {{ $evaluationResult['passed'] ? 'border-green-500' : 'border-red-500' }} rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-900">Evaluation Result</h3>
                        <div class="text-3xl">
                            {{ $evaluationResult['passed'] ? '✅' : '❌' }}
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="bg-gray-50 rounded p-3">
                            <div class="text-xs text-gray-600 uppercase">Status</div>
                            <div class="text-lg font-bold {{ $evaluationResult['passed'] ? 'text-green-600' : 'text-red-600' }}">
                                {{ $evaluationResult['passed'] ? 'PASSED' : 'FAILED' }}
                            </div>
                        </div>
                        <div class="bg-gray-50 rounded p-3">
                            <div class="text-xs text-gray-600 uppercase">Score</div>
                            <div class="text-lg font-bold text-blue-600">
                                {{ number_format($evaluationResult['score'], 2) }}%
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-600">Rules Passed:</span>
                            <span class="font-semibold text-green-600">{{ $evaluationResult['rules_passed'] ?? 0 }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600">Rules Failed:</span>
                            <span class="font-semibold text-red-600">{{ $evaluationResult['rules_failed'] ?? 0 }}</span>
                        </div>
                    </div>
                </div>

                <!-- Rules Breakdown -->
                <div class="bg-white border border-gray-200 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                        </svg>
                        Rules Breakdown
                    </h4>

                    @if(isset($evaluationResult['execution_log']) && count($evaluationResult['execution_log']) > 0)
                        <div class="space-y-2">
                            @foreach($evaluationResult['execution_log'] as $logEntry)
                                <div class="flex items-start p-3 border {{ $logEntry['passed'] ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50' }} rounded">
                                    <div class="text-lg mr-3">{{ $logEntry['passed'] ? '✓' : '✗' }}</div>
                                    <div class="flex-1 text-sm">
                                        <div class="font-medium {{ $logEntry['passed'] ? 'text-green-900' : 'text-red-900' }}">
                                            {{ $logEntry['field'] ?? 'Unknown' }}
                                        </div>
                                        <div class="text-xs {{ $logEntry['passed'] ? 'text-green-700' : 'text-red-700' }} mt-1">
                                            {{ $logEntry['operator'] ?? 'N/A' }}
                                            <code class="px-1 py-0.5 bg-white rounded">{{ json_encode($logEntry['expected'] ?? 'N/A') }}</code>
                                        </div>
                                        @if(isset($logEntry['actual']))
                                            <div class="text-xs text-gray-600 mt-1">
                                                Actual: <code class="px-1 py-0.5 bg-white rounded">{{ json_encode($logEntry['actual']) }}</code>
                                            </div>
                                        @endif
                                        @if(isset($logEntry['execution_time_ms']))
                                            <div class="text-xs text-gray-500 mt-1">
                                                ⚡ {{ $logEntry['execution_time_ms'] }}ms
                                            </div>
                                        @endif
                                    </div>
                                    @if(isset($logEntry['weight']))
                                        <div class="ml-2 px-2 py-1 text-xs font-medium rounded {{ $logEntry['passed'] ? 'bg-green-200 text-green-800' : 'bg-red-200 text-red-800' }}">
                                            W: {{ $logEntry['weight'] }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500">No rule details available</p>
                    @endif
                </div>                <!-- Raw JSON Response -->
                <details class="bg-gray-50 border border-gray-200 rounded-lg">
                    <summary class="px-4 py-3 cursor-pointer hover:bg-gray-100 font-medium text-sm text-gray-700">
                        📋 View Raw JSON Response
                    </summary>
                    <pre class="px-4 py-3 text-xs overflow-auto max-h-96 font-mono bg-gray-900 text-green-400">{{ json_encode($evaluationResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </details>
            @else
                <!-- Empty State -->
                <div class="bg-white border-2 border-dashed border-gray-300 rounded-lg p-12 text-center">
                    <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Ready to Test</h3>
                    <p class="text-sm text-gray-600 mb-4">
                        Select a criteria and provide test data to see evaluation results here
                    </p>
                    <div class="flex items-center justify-center gap-2 text-xs text-gray-500">
                        <span>💡 Tip: Use the example data to get started quickly</span>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Rules Preview (when criteria selected) -->
    @if($selectedCriteria && $selectedCriteria->rules->count() > 0)
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <h3 class="font-semibold text-gray-900 mb-3">Active Rules ({{ $selectedCriteria->rules->count() }})</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-600">Order</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-600">Field</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-600">Operator</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-600">Expected Value</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-600">Weight</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($selectedCriteria->rules as $rule)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2">{{ $rule->order }}</td>
                                <td class="px-4 py-2 font-medium">{{ $rule->field }}</td>
                                <td class="px-4 py-2">
                                    <code class="px-2 py-1 bg-gray-100 rounded text-xs">{{ $rule->operator }}</code>
                                </td>
                                <td class="px-4 py-2">
                                    @if(is_array($rule->value))
                                        <code class="px-2 py-1 bg-blue-50 text-blue-800 rounded text-xs">
                                            {{ json_encode($rule->value) }}
                                        </code>
                                    @else
                                        <span>{{ $rule->value }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded text-xs font-medium">
                                        {{ $rule->weight }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
