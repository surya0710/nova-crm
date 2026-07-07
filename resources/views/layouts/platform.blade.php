<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ __('Platform') }} — {{ config('app.name', 'NovaCRM') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-slate-950 text-slate-100">
        <div x-data="{ sidebarOpen: false }" class="min-h-screen flex">
            <div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 z-30 bg-black/60 lg:hidden" x-cloak></div>

            <aside class="fixed inset-y-0 left-0 z-40 w-64 bg-slate-900 border-r border-slate-800 transform transition-transform lg:translate-x-0 lg:sticky lg:top-0 lg:h-screen"
                :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
                @include('platform.partials.sidebar')
            </aside>

            <div class="flex-1 flex flex-col min-w-0">
                <header class="sticky top-0 z-20 bg-slate-900/95 border-b border-slate-800 backdrop-blur">
                    <div class="flex items-center justify-between h-16 px-4 sm:px-6 lg:px-8">
                        <div class="flex items-center gap-4 min-w-0">
                            <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-2 rounded-lg text-slate-400 hover:bg-slate-800">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16"/></svg>
                            </button>
                            @isset($header)
                                <div class="min-w-0 text-white">{{ $header }}</div>
                            @endisset
                        </div>
                        <div class="flex items-center gap-3 text-sm text-slate-400">
                            <span>{{ auth('platform')->user()->name }}</span>
                            <span class="hidden sm:inline px-2 py-0.5 rounded bg-slate-800 text-xs">{{ auth('platform')->user()->roleName() }}</span>
                        </div>
                    </div>
                </header>

                <main class="flex-1 p-4 sm:p-6 lg:p-8">
                    @if (session('status'))
                        <div class="mb-4 rounded-lg bg-emerald-900/40 border border-emerald-700 text-emerald-200 px-4 py-3 text-sm">
                            {{ session('status') }}
                        </div>
                    @endif
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
