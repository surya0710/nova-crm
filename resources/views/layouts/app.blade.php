<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'NovaCRM') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @stack('page-assets')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-slate-50">
        <x-impersonation-banner />
        <div x-data="{ sidebarOpen: false }" class="min-h-screen flex">
            <div
                x-show="sidebarOpen"
                x-transition:enter="transition-opacity ease-linear duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-linear duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="sidebarOpen = false"
                class="fixed inset-0 z-30 bg-slate-900/60 lg:hidden"
                x-cloak
            ></div>

            <div
                class="fixed inset-y-0 left-0 z-40 w-64 h-screen transform transition-transform duration-200 ease-in-out lg:translate-x-0 lg:sticky lg:top-0 lg:shrink-0"
                :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            >
                @include('layouts.sidebar')
            </div>

            <div class="flex-1 flex flex-col min-w-0">
                <header class="sticky top-0 z-20 bg-white border-b border-slate-200">
                    <div class="flex items-center justify-between gap-4 h-16 px-4 sm:px-6 lg:px-8">
                        <div class="flex items-center gap-4 min-w-0 flex-1">
                            <button
                                @click="sidebarOpen = !sidebarOpen"
                                class="lg:hidden p-2 rounded-lg text-slate-500 hover:bg-slate-100 shrink-0"
                            >
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16"/>
                                </svg>
                            </button>

                            @isset($header)
                                <div class="min-w-0">{{ $header }}</div>
                            @endisset
                        </div>

                        @auth
                            @php $currentOrganization = app(\App\Services\TenantContext::class)->get(); @endphp
                            @if ($currentOrganization)
                                <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                                    <form action="{{ route('search.index') }}" method="GET" class="hidden md:block">
                                        <input type="search" name="q" placeholder="{{ __('Search…') }}" class="w-40 lg:w-56 rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                    </form>

                                    @php
                                        $unreadCount = Auth::user()->unreadNotifications()
                                            ->where('data->organization_id', $currentOrganization->id)
                                            ->count();
                                    @endphp
                                    <a href="{{ route('notifications.index') }}" class="relative p-2 rounded-lg text-slate-500 hover:bg-slate-100" title="{{ __('Notifications') }}">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                        @if ($unreadCount > 0)
                                            <span class="absolute top-1.5 right-1.5 h-2 w-2 rounded-full bg-red-500"></span>
                                        @endif
                                    </a>

                                    <div class="hidden sm:flex items-center gap-2 pl-2 border-l border-slate-200">
                                        <x-organization-logo :organization="$currentOrganization" size="sm" />
                                        <span class="text-sm font-medium text-slate-700 max-w-[120px] truncate">{{ $currentOrganization->name }}</span>
                                    </div>
                                </div>
                            @endif
                        @endauth
                    </div>
                </header>

                <main class="flex-1 p-4 sm:p-6 lg:p-8">
                    {{ $slot }}
                </main>
            </div>
        </div>

        <x-lead-follow-up-alert />
    </body>
</html>
