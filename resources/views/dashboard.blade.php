<x-eligify::layout>
    <x-slot:title>Dashboard</x-slot:title>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white border border-gray-200 rounded p-4">
            <div class="text-xs text-gray-500">Criteria</div>
            <div class="text-2xl font-semibold">—</div>
        </div>
        <div class="bg-white border border-gray-200 rounded p-4">
            <div class="text-xs text-gray-500">Rules</div>
            <div class="text-2xl font-semibold">—</div>
        </div>
        <div class="bg-white border border-gray-200 rounded p-4">
            <div class="text-xs text-gray-500">Evaluations (24h)</div>
            <div class="text-2xl font-semibold">—</div>
        </div>
        <div class="bg-white border border-gray-200 rounded p-4">
            <div class="text-xs text-gray-500">Pass Rate</div>
            <div class="text-2xl font-semibold">—</div>
        </div>
    </div>
    <div class="mt-6 bg-white border border-gray-200 rounded p-4">
        <div class="font-semibold mb-2">Recent Activity</div>
        <div class="text-sm text-gray-500">No activity to show yet.</div>
    </div>
</x-eligify::layout>
