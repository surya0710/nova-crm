<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" data-density="comfortable">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="application-name" content="{{ config('branding.product_name') }}">
        <meta name="apple-mobile-web-app-title" content="{{ config('branding.product_name') }}">
        <title>{{ __('Platform') }} — {{ config('branding.product_name') }}</title>
        <x-brand-favicon />
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-app text-ink">
        <a href="#main-content" class="nova-skip-link">{{ __('Skip to content') }}</a>

        @php
            $platformUser = auth('platform')->user();
            $shellEndpoints = [
                'commands' => route('platform.shell.commands'),
                'commandsRecord' => null,
                'search' => route('platform.shell.search'),
                'preferences' => null,
                'workspace' => null,
                'favorites' => null,
                'recents' => null,
                'recentsClear' => null,
                'notifications' => null,
            ];
        @endphp

        <div
            x-data="{ sidebarOpen: false }"
            x-init="
                Alpine.store('shell').init({
                    theme: 'light',
                    density: 'comfortable',
                    sidebarCollapsed: false,
                    endpoints: @js($shellEndpoints),
                });
            "
            class="flex min-h-screen"
        >
            <div
                x-show="sidebarOpen"
                x-transition:enter="transition-opacity ease-linear duration-normal"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-linear duration-normal"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="sidebarOpen = false"
                class="fixed inset-0 z-drawer bg-[var(--nova-color-bg-overlay)] lg:hidden"
                x-cloak
            ></div>

            <aside
                class="fixed inset-y-0 left-0 z-sidebar flex h-screen w-64 transform flex-col border-r border-line bg-surface-card transition-transform duration-normal ease-in-out lg:sticky lg:top-0 lg:translate-x-0"
                :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
                aria-label="{{ __('Platform navigation') }}"
            >
                @include('platform.partials.sidebar')
            </aside>

            <div class="flex min-w-0 flex-1 flex-col">
                <header class="sticky top-0 z-header border-b border-line bg-surface-card/95 backdrop-blur">
                    <div class="flex h-14 items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
                        <div class="flex min-w-0 items-center gap-3">
                            <button
                                type="button"
                                @click="sidebarOpen = !sidebarOpen"
                                class="rounded-md p-2 text-ink-muted hover:bg-surface-muted lg:hidden"
                                aria-label="{{ __('Open navigation') }}"
                            >
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16"/></svg>
                            </button>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-ink-heading">{{ __('Platform Administration') }}</p>
                                @isset($header)
                                    <div class="truncate text-xs text-ink-muted">{{ $header }}</div>
                                @endisset
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                class="hidden rounded-md border border-line px-2.5 py-1.5 text-xs text-ink-muted hover:bg-surface-muted sm:inline-flex"
                                @click="$store.shell.openSearch()"
                                aria-label="{{ __('Search') }}"
                            >
                                {{ __('Search') }}
                                <kbd class="ml-2 text-[10px]">⌘K</kbd>
                            </button>
                            <button
                                type="button"
                                class="hidden rounded-md border border-line px-2.5 py-1.5 text-xs text-ink-muted hover:bg-surface-muted sm:inline-flex"
                                @click="$store.shell.openPalette()"
                                aria-label="{{ __('Command palette') }}"
                            >
                                {{ __('Commands') }}
                            </button>
                            <div class="text-right text-sm">
                                <div class="font-medium text-ink-heading">{{ $platformUser->name }}</div>
                                <div class="text-xs text-ink-muted">{{ $platformUser->roleName() }}</div>
                            </div>
                        </div>
                    </div>
                </header>

                <main id="main-content" class="flex-1 p-4 sm:p-6 lg:p-8" tabindex="-1">
                    @if (session('status'))
                        <div class="mb-4" role="status">
                            <x-ui.alert variant="success" :title="session('status')" />
                        </div>
                    @endif
                    @if ($errors->any())
                        <div class="mb-4" role="alert">
                            <x-ui.alert variant="danger" :title="__('Please fix the errors below.')" />
                        </div>
                    @endif
                    {{ $slot }}
                </main>

                <footer class="border-t border-line px-4 py-3 flex items-center justify-center gap-2 sm:px-6 lg:px-8">
                    <x-product-logo variant="dark" size="sm" />
                </footer>
            </div>
        </div>

        <x-nav.command-palette />
        <x-nav.global-search />
    </body>
</html>
