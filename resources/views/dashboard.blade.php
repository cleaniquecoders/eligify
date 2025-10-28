<x-eligify::layout>
    <x-slot:title>Dashboard</x-slot:title>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white border border-gray-200 rounded p-4">
            <div class="text-xs text-gray-500">Criteria</div>
            <div class="text-2xl font-semibold">{{ number_format($metrics['criteria_count'] ?? 0) }}</div>
        </div>
        <div class="bg-white border border-gray-200 rounded p-4">
            <div class="text-xs text-gray-500">Rules</div>
            <div class="text-2xl font-semibold">{{ number_format($metrics['rules_count'] ?? 0) }}</div>
        </div>
        <div class="bg-white border border-gray-200 rounded p-4">
            <div class="text-xs text-gray-500">Evaluations (24h)</div>
            <div class="text-2xl font-semibold">{{ number_format($metrics['evaluations_24h'] ?? 0) }}</div>
        </div>
        <div class="bg-white border border-gray-200 rounded p-4">
            <div class="text-xs text-gray-500">Pass Rate</div>
            <div class="text-2xl font-semibold">
                @if (!is_null($metrics['pass_rate_24h'] ?? null))
                    {{ number_format($metrics['pass_rate_24h'], 2) }}%
                @else
                    —
                @endif
            </div>
        </div>
    </div>
    <div class="mt-6 bg-white border border-gray-200 rounded p-4">
        <div class="font-semibold mb-2">Recent Activity</div>
        @php($activity = $metrics['recent_activity'] ?? [])
        @if (count($activity) === 0)
            <div class="text-sm text-gray-500">No activity to show yet.</div>
        @else
            <ul class="divide-y divide-gray-200">
                @foreach ($activity as $item)
                    <li class="py-2 flex items-center justify-between text-sm">
                        <div>
                            <span class="font-medium text-gray-800">{{ ucfirst(str_replace('_', ' ', $item['event'])) }}</span>
                            <span class="text-gray-500">on {{ $item['entity'] }}</span>
                        </div>
                        <div class="text-gray-400">{{ \Illuminate\Support\Carbon::parse($item['created_at'])->diffForHumans() }}</div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-eligify::layout>
