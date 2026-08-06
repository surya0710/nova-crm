<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    data-theme="{{ $shellNav['theme'] ?? 'light' }}"
    data-density="{{ $shellNav['density'] ?? 'comfortable' }}"
    class="h-full overflow-x-hidden"
    @if (! empty($shellNav['branding']))
        style="{{ collect($shellNav['branding'])->map(fn ($v, $k) => $k.':'.$v)->implode(';') }}"
    @endif
>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="application-name" content="{{ config('branding.product_name') }}">
        <meta name="apple-mobile-web-app-title" content="{{ config('branding.product_name') }}">
        <title>{{ config('branding.product_name') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        @stack('page-assets')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        {{-- Critical shell layout: works before Alpine/Tailwind custom tokens load --}}
        <style>
            .nova-shell {
                display: flex;
                height: 100%;
                min-height: 0;
                width: 100%;
                max-width: 100vw;
                overflow: hidden;
            }
            .nova-shell-sidebar {
                width: 16rem;
                z-index: 30;
            }
            .nova-shell[data-sidebar-collapsed="true"] .nova-shell-sidebar {
                width: 4rem;
            }
            .nova-shell-main {
                display: flex;
                flex: 1 1 0%;
                flex-direction: column;
                min-width: 0;
                min-height: 0;
                max-width: 100%;
            }
            @media (min-width: 1024px) {
                .nova-shell-main {
                    margin-left: 16rem;
                }
                .nova-shell[data-sidebar-collapsed="true"] .nova-shell-main {
                    margin-left: 4rem;
                }
            }
            .nova-header {
                position: sticky;
                top: 0;
                z-index: 20;
                flex-shrink: 0;
                overflow: visible;
            }
            .nova-header [role="listbox"],
            .nova-header [role="menu"] {
                z-index: 50;
            }
            .nova-shell-content {
                flex: 1 1 0%;
                min-height: 0;
                min-width: 0;
                overflow-y: auto;
                overflow-x: hidden;
            }
        </style>
    </head>
    <body class="h-full overflow-x-hidden font-sans antialiased bg-app text-ink">
        <a href="#main-content" class="nova-skip-link">{{ __('Skip to content') }}</a>
        <x-impersonation-banner />

        @php
            $enterpriseShell = config('features.enterprise_shell', true);
            $currentOrganization = app(\App\Services\TenantContext::class)->get();
            $unreadCount = 0;
            if (Auth::check() && $currentOrganization) {
                $unreadCount = Auth::user()->unreadNotifications()
                    ->where('data->organization_id', $currentOrganization->id)
                    ->count();
            }
            $shellEndpoints = collect([
                'preferences' => 'shell.preferences.update',
                'workspace' => 'shell.workspace.switch',
                'favorites' => 'shell.favorites.toggle',
                'favoriteWorkspaces' => 'shell.workspace-favorites.toggle',
                'recents' => 'shell.recents.store',
                'recentsClear' => 'shell.recents.clear',
                'commands' => 'shell.commands.index',
                'commandsRecord' => 'shell.commands.record',
                'search' => 'shell.search.index',
                'notifications' => 'shell.notifications.index',
            ])->mapWithKeys(fn ($name, $key) => [
                $key => \Illuminate\Support\Facades\Route::has($name) ? route($name) : null,
            ])->all();
            $workspaceLabel = data_get($shellNav, 'currentWorkspaceMeta.label');
            $sidebarCollapsed = (bool) ($shellNav['sidebarCollapsed'] ?? false);
        @endphp

        <div
            x-data="{ sidebarOpen: false }"
            x-init="
                Alpine.store('shell').init({
                    theme: @js($shellNav['theme'] ?? 'light'),
                    density: @js($shellNav['density'] ?? 'comfortable'),
                    sidebarCollapsed: @js($sidebarCollapsed),
                    currentWorkspace: @js($shellNav['currentWorkspace'] ?? 'home'),
                    searchDefaultScope: @js($shellNav['searchDefaultScope'] ?? 'all'),
                    endpoints: @js($shellEndpoints),
                });
            "
            class="nova-shell flex h-full min-h-0 w-full max-w-[100vw] overflow-hidden"
            data-sidebar-collapsed="{{ $sidebarCollapsed ? 'true' : 'false' }}"
            :data-sidebar-collapsed="$store.shell.sidebarCollapsed ? 'true' : 'false'"
        >
            @if ($enterpriseShell)
                {{-- Mobile overlay --}}
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

                {{-- Fixed sidebar (independent of content scroll) --}}
                <aside
                    class="nova-shell-sidebar fixed inset-y-0 left-0 z-30 flex h-full max-h-screen flex-col transition-[width,transform] duration-200 ease-in-out lg:translate-x-0 {{ $sidebarCollapsed ? 'w-16' : 'w-64' }}"
                    :class="[
                        sidebarOpen ? 'translate-x-0' : '-translate-x-full',
                        $store.shell.sidebarCollapsed ? 'w-16' : 'w-64',
                    ]"
                >
                    <x-nav.sidebar :shell-nav="$shellNav" />
                </aside>

                {{-- Main column: offset from fixed sidebar via critical CSS (data-sidebar-collapsed) --}}
                <div class="nova-shell-main flex min-w-0 flex-1 flex-col overflow-hidden">
                    <x-shell.header
                        :workspace-title="$workspaceLabel"
                        :unread-count="$unreadCount"
                        :organization="$currentOrganization"
                        :theme="$shellNav['theme'] ?? 'light'"
                        :shell-nav="$shellNav"
                    >
                        @isset($header)
                            <x-slot:header>{{ $header }}</x-slot:header>
                        @endisset
                    </x-shell.header>

                    @isset($contextBar)
                        <div class="shrink-0 border-b border-line bg-surface-card px-4 py-2 sm:px-6 lg:px-8">
                            {{ $contextBar }}
                        </div>
                    @endisset

                    <main id="main-content" class="nova-shell-content" tabindex="-1">
                        <div class="nova-content">
                            {{ $slot }}
                        </div>
                    </main>

                    <footer class="shrink-0 border-t border-line px-4 py-2 text-center text-xs text-ink-muted sm:px-6 lg:px-8">
                        {{ config('branding.product_name') }}
                    </footer>
                </div>

                @auth
                    @if (config('features.command_palette'))
                        <x-nav.command-palette />
                    @endif
                    @if (config('features.global_search_modal'))
                        <x-nav.global-search />
                    @endif
                    @if (config('features.notification_drawer'))
                        <x-shell.notification-drawer />
                    @endif
                @endauth
            @else
                {{-- Legacy shell rollback path --}}
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
                    class="fixed inset-y-0 left-0 z-40 h-screen w-64 transform transition-transform duration-200 ease-in-out lg:translate-x-0"
                    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
                >
                    @include('layouts.sidebar')
                </div>
                <div class="hidden w-64 shrink-0 lg:block" aria-hidden="true"></div>
                <div class="flex min-w-0 flex-1 flex-col overflow-hidden">
                    <header class="shrink-0 border-b border-slate-200 bg-white">
                        <div class="flex h-16 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                            <div class="flex min-w-0 flex-1 items-center gap-4">
                                <button @click="sidebarOpen = !sidebarOpen" class="shrink-0 rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16"/></svg>
                                </button>
                                @isset($header)
                                    <div class="min-w-0">{{ $header }}</div>
                                @endisset
                            </div>
                        </div>
                    </header>
                    <main id="main-content" class="min-h-0 flex-1 overflow-y-auto overflow-x-hidden p-4 sm:p-6 lg:p-8">{{ $slot }}</main>
                </div>
            @endif
        </div>

        <x-lead-follow-up-alert />
    </body>
</html>
