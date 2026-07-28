@props([
    'shellNav' => [],
])

@php
    $icons = [
        'home' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>',
        'building' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>',
        'user' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>',
        'users' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
        'chart' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>',
        'box' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>',
        'doc' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>',
        'receipt' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>',
        'payment' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        'shield' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>',
        'task' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>',
        'fields' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h10M4 18h7M17 10v8m-4-4h8"/></svg>',
        'workflow' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 7h5m4 0h5M8 7a2 2 0 11-4 0 2 2 0 014 0zm12 10h-5m-4 0H5m11 0a2 2 0 104 0 2 2 0 00-4 0zM12 7v10"/></svg>',
        'hr' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>',
        'cog' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
    ];

    $nav = is_array($shellNav) ? $shellNav : [];
    $currentOrganization = $currentOrganization ?? app(\App\Services\TenantContext::class)->get();
    $user = Auth::user();
    // Width is controlled by Alpine shell store on the parent; labels hide via Alpine.
    $menu = collect($nav['menu'] ?? []);
    $favorites = collect($nav['favorites'] ?? []);
    $pinned = collect($nav['pinned'] ?? []);
    $recents = collect($nav['recents'] ?? []);
    $workspaces = collect($nav['workspaces'] ?? []);
    $currentWorkspace = $nav['currentWorkspace'] ?? 'home';
    $workspaceLabel = data_get($nav, 'currentWorkspaceMeta.label') ?? __('Navigation');
    $adminWorkspaces = $workspaces->where('footer', true)->values();
    $primaryWorkspaces = $workspaces->reject(fn ($w) => (is_array($w) ? ($w['footer'] ?? false) : false))->values();
@endphp

<aside
    class="flex h-full w-full flex-col overflow-x-hidden bg-sidebar text-sidebar-text"
    aria-label="{{ __('Primary') }}"
    data-workspace="{{ $currentWorkspace }}"
>
    @if ($currentOrganization)
        <div class="shrink-0 overflow-visible border-b border-sidebar-border px-3 py-4">
            <div class="flex items-center gap-3">
                <x-organization-logo :organization="$currentOrganization" size="md" />
                <div class="min-w-0 flex-1" x-show="! $store.shell.sidebarCollapsed" x-cloak>
                    <p class="truncate text-sm font-medium">{{ $currentOrganization->name }}</p>
                    <p class="truncate text-xs text-sidebar-muted">{{ $currentOrganization->currency }} · {{ $currentOrganization->timezone }}</p>
                </div>
            </div>

            <div class="mt-3" x-show="! $store.shell.sidebarCollapsed" x-cloak>
                @if ($user->organizations->count() > 1)
                    <form
                        id="org-switch-form"
                        method="POST"
                        action="{{ route('organization.switch', $currentOrganization) }}"
                        class="org-switch-form"
                    >
                        @csrf
                        <select
                            class="w-full rounded-lg border-slate-700 bg-slate-800 px-2 py-1.5 text-xs text-slate-300 focus:border-primary-500 focus:ring-primary-500"
                            aria-label="{{ __('Switch organization') }}"
                            data-current="{{ $currentOrganization->id }}"
                            onchange="window.NovaOrgSwitch && window.NovaOrgSwitch.submit(this)"
                        >
                            @foreach ($user->organizations as $org)
                                <option
                                    value="{{ $org->id }}"
                                    data-url="{{ route('organization.switch', $org) }}"
                                    @selected($org->id === $currentOrganization->id)
                                >{{ $org->name }}</option>
                            @endforeach
                        </select>
                    </form>
                @endif

                @if (config('features.workspace_nav') && $primaryWorkspaces->isNotEmpty() && ! config('features.header_workspace_switcher', true))
                    <div class="relative mt-3">
                        <x-nav.workspace-switcher :workspaces="$primaryWorkspaces" :current="$currentWorkspace" />
                    </div>
                @endif
            </div>
        </div>
    @endif

    <nav class="sidebar-scroll min-h-0 flex-1 space-y-4 overflow-y-auto px-2 py-3">
        <div x-show="! $store.shell.sidebarCollapsed" x-cloak>
            @if ($favorites->isNotEmpty())
                <div class="mb-4">
                    <p class="mb-1.5 px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">{{ __('Favorites') }}</p>
                    <div class="space-y-0.5">
                        @foreach ($favorites as $fav)
                            <x-nav.sidebar-link :href="$fav['href']" :icon="$icons[$fav['icon'] ?? 'doc'] ?? $icons['doc']">{{ $fav['label'] }}</x-nav.sidebar-link>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($pinned->isNotEmpty())
                <div class="mb-4">
                    <p class="mb-1.5 px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">{{ __('Pinned') }}</p>
                    <div class="space-y-0.5">
                        @foreach ($pinned as $pin)
                            <x-nav.sidebar-link :href="$pin['href']" :icon="$icons[$pin['icon'] ?? 'doc'] ?? $icons['doc']">{{ $pin['label'] }}</x-nav.sidebar-link>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div data-workspace-nav="{{ $currentWorkspace }}">
            <p class="mb-1.5 px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500" x-show="! $store.shell.sidebarCollapsed" x-cloak>
                {{ $workspaceLabel }}
            </p>
            <div class="space-y-0.5">
                @forelse ($menu as $item)
                    @if (! empty($item['children']))
                        <div x-data="{ open: {{ ($item['open'] ?? false) ? 'true' : 'false' }} }">
                            <button
                                type="button"
                                class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-sidebar-muted hover:bg-sidebar-hover hover:text-sidebar-text"
                                @click="open = ! open"
                                :aria-expanded="open.toString()"
                            >
                                @if (! empty($item['icon']))
                                    <span class="shrink-0">{!! $icons[$item['icon']] ?? $icons['doc'] !!}</span>
                                @endif
                                <span class="flex-1 text-left" x-show="! $store.shell.sidebarCollapsed" x-cloak>{{ $item['label'] }}</span>
                                <svg class="h-4 w-4 transition" x-show="! $store.shell.sidebarCollapsed" x-cloak :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7"/></svg>
                            </button>
                            <div x-show="open && ! $store.shell.sidebarCollapsed" x-cloak class="ml-4 space-y-0.5 border-l border-sidebar-border pl-2">
                                @foreach ($item['children'] as $child)
                                    <x-nav.sidebar-link
                                        :href="$child['href']"
                                        :active="$child['active'] ?? false"
                                        :badge="$child['badge'] ?? null"
                                    >{{ $child['label'] }}</x-nav.sidebar-link>
                                @endforeach
                            </div>
                        </div>
                    @elseif (! empty($item['href']))
                        <x-nav.sidebar-link
                            :href="$item['href']"
                            :active="$item['active'] ?? false"
                            :badge="$item['badge'] ?? null"
                            :icon="! empty($item['icon']) ? ($icons[$item['icon']] ?? null) : null"
                        >{{ $item['label'] }}</x-nav.sidebar-link>
                    @endif
                @empty
                    <p class="px-3 py-2 text-xs text-sidebar-muted" x-show="! $store.shell.sidebarCollapsed" x-cloak>{{ __('No navigation items in this workspace.') }}</p>
                @endforelse
            </div>
        </div>

        <div x-show="! $store.shell.sidebarCollapsed" x-cloak>
            @if ($recents->isNotEmpty())
                <div class="mt-4" x-data="{ open: true }">
                    <button type="button" class="mb-1.5 flex w-full items-center justify-between px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500" @click="open = ! open">
                        <span>{{ __('Recents') }}</span>
                        <svg class="h-3.5 w-3.5" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" class="space-y-0.5">
                        @foreach ($recents as $recent)
                            <x-nav.sidebar-link :href="$recent['href']" :icon="$icons['doc']">{{ $recent['label'] }}</x-nav.sidebar-link>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($adminWorkspaces->isNotEmpty() && $currentWorkspace !== 'administration')
                <div class="mt-4 border-t border-sidebar-border pt-3">
                    <p class="mb-1.5 px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">{{ __('Administration') }}</p>
                    <div class="space-y-0.5">
                        @foreach ($adminWorkspaces as $admin)
                            <x-nav.sidebar-link :href="$admin['href']" :icon="$icons[$admin['icon'] ?? 'cog'] ?? $icons['cog']">{{ $admin['label'] }}</x-nav.sidebar-link>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </nav>

    <div class="shrink-0 border-t border-sidebar-border bg-sidebar p-3">
        <div class="flex items-center gap-3">
            <x-ui.avatar :name="$user->name" size="md" />
            <div class="min-w-0 flex-1" x-show="! $store.shell.sidebarCollapsed" x-cloak>
                <p class="truncate text-sm font-medium">{{ $user->name }}</p>
                <p class="truncate text-xs text-sidebar-muted">{{ $user->email }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}" x-show="! $store.shell.sidebarCollapsed" x-cloak>
                @csrf
                <button type="submit" class="rounded-lg p-1.5 text-sidebar-muted transition hover:bg-sidebar-hover hover:text-white" title="{{ __('Log Out') }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                </button>
            </form>
        </div>
    </div>
</aside>
