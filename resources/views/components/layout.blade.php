<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ trim(($title ?? '') . ' - ' . config('eligify.ui.brand.name', 'Eligify')) }}</title>

    @if (data_get(config('eligify.ui.assets'), 'use_cdn', true))
        <script src="{{ data_get(config('eligify.ui.assets'), 'tailwind_cdn', 'https://cdn.tailwindcss.com') }}"></script>
        <script>
            tailwind.config = {
                darkMode: 'class',
                theme: {
                    extend: {
                        colors: {
                            primary: {
                                50: '#f0f9ff',
                                100: '#e0f2fe',
                                200: '#bae6fd',
                                300: '#7dd3fc',
                                400: '#38bdf8',
                                500: '#0ea5e9',
                                600: '#0284c7',
                                700: '#0369a1',
                                800: '#075985',
                                900: '#0c4a6e',
                                950: '#082f49',
                            }
                        }
                    }
                }
            };
        </script>
    @endif

    @if (class_exists(\Livewire\Livewire::class))
        @livewireStyles
    @endif

    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="h-full bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 text-gray-900 antialiased" x-data="{ mobileMenuOpen: false }">
    <!-- Top Navigation -->
    <nav class="sticky top-0 z-50 bg-white/80 backdrop-blur-xl border-b border-gray-200/50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo & Brand -->
                <div class="flex items-center space-x-8">
                    <a href="{{ route('eligify.dashboard') }}" class="flex items-center space-x-3 group">
                        <div class="w-10 h-10 bg-gradient-to-br from-primary-500 to-primary-700 rounded-xl flex items-center justify-center shadow-lg shadow-primary-500/30 group-hover:shadow-primary-500/50 transition-all duration-300 group-hover:scale-105">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <span class="font-bold text-xl bg-gradient-to-r from-primary-600 to-primary-800 bg-clip-text text-transparent">
                            {{ config('eligify.ui.brand.name', 'Eligify') }}
                        </span>
                    </a>

                    <!-- Desktop Navigation -->
                    <div class="hidden md:flex items-center space-x-1">
                        <a href="{{ route('eligify.dashboard') }}" class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('eligify.dashboard') ? 'bg-primary-50 text-primary-700 shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                            Dashboard
                        </a>
                        <a href="{{ route('eligify.criteria.index') }}" class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('eligify.criteria.*') ? 'bg-primary-50 text-primary-700 shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                            Criteria
                        </a>
                        <a href="{{ route('eligify.rule-library.index') }}" class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('eligify.rule-library.*') ? 'bg-primary-50 text-primary-700 shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                            Rule Library
                        </a>
                        <a href="{{ route('eligify.playground') }}" class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('eligify.playground') ? 'bg-primary-50 text-primary-700 shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                            Playground
                        </a>
                        <a href="{{ route('eligify.audit') }}" class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('eligify.audit') ? 'bg-primary-50 text-primary-700 shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                            Audit
                        </a>
                        <a href="{{ route('eligify.settings') }}" class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('eligify.settings') ? 'bg-primary-50 text-primary-700 shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                            Settings
                        </a>
                    </div>
                </div>

                <!-- Right Side Actions -->
                <div class="flex items-center space-x-3">
                    <div class="hidden md:block text-xs text-gray-500">
                        {{ $actions ?? '' }}
                    </div>

                    <!-- Mobile Menu Button -->
                    <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden p-2 rounded-lg text-gray-600 hover:bg-gray-100 transition-colors">
                        <svg x-show="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                        <svg x-show="mobileMenuOpen" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Navigation -->
        <div x-show="mobileMenuOpen" x-cloak x-transition class="md:hidden border-t border-gray-200 bg-white">
            <div class="px-4 py-3 space-y-1">
                <a href="{{ route('eligify.dashboard') }}" class="block px-4 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('eligify.dashboard') ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    Dashboard
                </a>
                <a href="{{ route('eligify.criteria.index') }}" class="block px-4 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('eligify.criteria.*') ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    Criteria
                </a>
                <a href="{{ route('eligify.rule-library.index') }}" class="block px-4 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('eligify.rule-library.*') ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    Rule Library
                </a>
                <a href="{{ route('eligify.playground') }}" class="block px-4 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('eligify.playground') ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    Playground
                </a>
                <a href="{{ route('eligify.audit') }}" class="block px-4 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('eligify.audit') ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    Audit
                </a>
                <a href="{{ route('eligify.settings') }}" class="block px-4 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('eligify.settings') ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    Settings
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="min-h-[calc(100vh-4rem)]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Page Header -->
            @if (isset($title))
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 tracking-tight">{{ $title }}</h1>
                @if (isset($subtitle))
                <p class="mt-2 text-sm text-gray-600">{{ $subtitle }}</p>
                @endif
            </div>
            @endif

            <!-- Status Messages -->
            @if (session('status'))
                <div class="mb-6 rounded-xl bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 text-green-800 px-5 py-4 shadow-sm flex items-center space-x-3">
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-sm font-medium">{{ session('status') }}</span>
                </div>
            @endif

            <!-- Page Content -->
            <div class="space-y-6">
                {{ $slot }}
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white/50 backdrop-blur-sm border-t border-gray-200 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col md:flex-row items-center justify-between space-y-2 md:space-y-0">
                <div class="text-sm text-gray-500">
                    &copy; {{ date('Y') }} {{ config('eligify.ui.brand.name', 'Eligify') }}. Define criteria. Enforce rules. Decide eligibility.
                </div>
                <div class="text-xs text-gray-400">
                    Powered by CleaniqueCoders
                </div>
            </div>
        </div>
    </footer>

@if (class_exists(\Livewire\Livewire::class))
    @livewireScripts
@endif
</body>
</html>
