@props([
    'workspaceTitle' => null,
    'unreadCount' => 0,
    'organization' => null,
    'theme' => 'light',
    'shellNav' => [],
])

@php
    $nav = is_array($shellNav) ? $shellNav : [];
    $workspaces = collect($nav['workspaces'] ?? []);
    $currentWorkspace = $nav['currentWorkspace'] ?? null;
    $favoriteWorkspaces = collect($nav['favoriteWorkspaces'] ?? []);
    $recentWorkspaces = collect($nav['recentWorkspaces'] ?? []);
    $quickActions = $nav['quickActions'] ?? [];
    $useHeaderSwitcher = config('features.header_workspace_switcher', true)
        && config('features.workspace_nav')
        && $workspaces->isNotEmpty();
@endphp

<header class="sticky top-0 z-sticky border-b border-line bg-surface-card">
    <div class="flex h-16 items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
        <div class="flex min-w-0 flex-1 items-center gap-3">
            <button
                type="button"
                class="rounded-lg p-2 text-ink-muted hover:bg-surface-muted lg:hidden"
                @click="sidebarOpen = ! sidebarOpen"
                aria-label="{{ __('Open navigation') }}"
            >
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>

            <button
                type="button"
                class="hidden rounded-lg p-2 text-ink-muted hover:bg-surface-muted lg:inline-flex"
                @click="Alpine.store('shell').toggleSidebar()"
                aria-label="{{ __('Collapse sidebar') }}"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h10M4 18h16"/></svg>
            </button>

            @if ($useHeaderSwitcher)
                <x-nav.header-workspace-switcher
                    :workspaces="$workspaces"
                    :current="$currentWorkspace"
                    :favorite-workspaces="$favoriteWorkspaces"
                    :recent-workspaces="$recentWorkspaces"
                />
            @elseif ($workspaceTitle)
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-ink-heading">{{ $workspaceTitle }}</p>
                </div>
            @endif

            @isset($header)
                <div class="min-w-0">{{ $header }}</div>
            @endisset
        </div>

        <div class="flex shrink-0 items-center gap-1 sm:gap-2">
            @if (count($quickActions))
                <x-nav.header-quick-actions :actions="$quickActions" />
            @endif

            @if (config('features.global_search_modal') || config('features.command_palette'))
                <button
                    type="button"
                    class="hidden items-center gap-2 rounded-lg border border-line bg-surface-muted px-3 py-1.5 text-sm text-ink-muted hover:bg-app md:inline-flex"
                    @click="Alpine.store('shell').openSearch()"
                    aria-label="{{ __('Search') }}"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"/></svg>
                    <span>{{ __('Search…') }}</span>
                    <kbd class="rounded border border-line bg-surface-card px-1.5 text-[10px] font-medium text-ink-muted">Ctrl K</kbd>
                </button>
                <button
                    type="button"
                    class="rounded-lg p-2 text-ink-muted hover:bg-surface-muted md:hidden"
                    @click="Alpine.store('shell').openSearch()"
                    aria-label="{{ __('Search') }}"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"/></svg>
                </button>
            @endif

            @isset($quickActions)
                <div class="hidden sm:block">{{ $quickActions }}</div>
            @endisset

            @if (config('documentation.help.button.show_in_header', true))
                <x-help-dropdown />
            @endif

            @if (config('features.notification_drawer'))
                <button
                    type="button"
                    class="relative rounded-lg p-2 text-ink-muted hover:bg-surface-muted"
                    @click="Alpine.store('shell').openNotifications()"
                    aria-label="{{ __('Notifications') }}"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    @if ($unreadCount > 0)
                        <span class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-danger"></span>
                    @endif
                </button>
            @else
                <a href="{{ route('notifications.index') }}" class="relative rounded-lg p-2 text-ink-muted hover:bg-surface-muted" title="{{ __('Notifications') }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    @if ($unreadCount > 0)
                        <span class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-danger"></span>
                    @endif
                </a>
            @endif

            @if (config('features.theme_switcher'))
                <button
                    type="button"
                    class="rounded-lg p-2 text-ink-muted hover:bg-surface-muted"
                    @click="Alpine.store('shell').cycleTheme()"
                    aria-label="{{ __('Toggle theme') }}"
                    title="{{ __('Theme') }}"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                </button>
            @endif

            @if ($organization)
                <div class="hidden items-center gap-2 border-l border-line pl-2 sm:flex">
                    <x-organization-logo :organization="$organization" size="sm" />
                    <span class="max-w-[120px] truncate text-sm font-medium text-ink-heading">{{ $organization->name }}</span>
                </div>
            @endif

            <x-ui.dropdown align="right" width="56">
                <x-slot:trigger>
                    <button type="button" class="rounded-lg p-1.5 hover:bg-surface-muted" aria-label="{{ __('User menu') }}">
                        <x-ui.avatar :name="Auth::user()->name" size="sm" />
                    </button>
                </x-slot:trigger>
                <x-slot:content>
                    <div class="border-b border-line px-3 py-2">
                        <p class="truncate text-sm font-medium text-ink-heading">{{ Auth::user()->name }}</p>
                        <p class="truncate text-xs text-ink-muted">{{ Auth::user()->email }}</p>
                    </div>
                    <a href="{{ route('profile.edit') }}" class="block px-3 py-2 text-sm text-ink hover:bg-surface-muted">{{ __('Profile') }}</a>
                    @if (Route::has('knowledge.index'))
                        <a href="{{ route('knowledge.index') }}" class="block px-3 py-2 text-sm text-ink hover:bg-surface-muted">{{ __('Knowledge Center') }}</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="block w-full px-3 py-2 text-left text-sm text-ink hover:bg-surface-muted">{{ __('Log Out') }}</button>
                    </form>
                </x-slot:content>
            </x-ui.dropdown>
        </div>
    </div>
</header>
