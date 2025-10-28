<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ trim(($title ?? '') . ' - ' . config('eligify.ui.brand.name', 'Eligify')) }}</title>

    @if (data_get(config('eligify.ui.assets'), 'use_cdn', true))
        <script src="{{ data_get(config('eligify.ui.assets'), 'tailwind_cdn', 'https://cdn.tailwindcss.com') }}"></script>
        <script>
            tailwind.config = { darkMode: 'class' };
        </script>
        <script defer src="{{ data_get(config('eligify.ui.assets'), 'alpine_cdn', 'https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js') }}"></script>
    @endif

    @if (class_exists(\Livewire\Livewire::class))
        @livewireStyles
    @endif
</head>
<body class="h-full bg-gray-50 text-gray-900 antialiased">
<div class="min-h-screen flex">
    <aside class="w-64 bg-white border-r border-gray-200 hidden md:block">
        <div class="h-16 flex items-center px-4 font-semibold text-lg">
            {{ config('eligify.ui.brand.name', 'Eligify') }}
        </div>
        <nav class="p-2 space-y-1">
            <a href="{{ route('eligify.dashboard') }}" class="block px-3 py-2 rounded hover:bg-gray-100 text-sm">Dashboard</a>
            <a href="{{ route('eligify.criteria.index') }}" class="block px-3 py-2 rounded hover:bg-gray-100 text-sm">Criteria</a>
            <a href="{{ route('eligify.rule-library.index') }}" class="block px-3 py-2 rounded hover:bg-gray-100 text-sm">Rule Library</a>
            <a href="{{ route('eligify.playground') }}" class="block px-3 py-2 rounded hover:bg-gray-100 text-sm">Playground</a>
            <a href="{{ route('eligify.audit') }}" class="block px-3 py-2 rounded hover:bg-gray-100 text-sm">Audit</a>
            <a href="{{ route('eligify.settings') }}" class="block px-3 py-2 rounded hover:bg-gray-100 text-sm">Settings</a>
        </nav>
    </aside>
    <main class="flex-1 min-w-0">
        <header class="h-16 bg-white border-b border-gray-200 flex items-center px-4 justify-between">
            <div class="font-semibold">{{ $title ?? config('eligify.ui.brand.name', 'Eligify') }}</div>
            <div class="text-sm text-gray-500">{{ $actions ?? '' }}</div>
        </header>
        <section class="p-4">
            @if (session('status'))
                <div class="mb-4 rounded bg-green-50 text-green-800 px-3 py-2 text-sm">{{ session('status') }}</div>
            @endif
            {{ $slot }}
        </section>
    </main>
</div>

@if (class_exists(\Livewire\Livewire::class))
    @livewireScripts
@endif
</body>
</html>
