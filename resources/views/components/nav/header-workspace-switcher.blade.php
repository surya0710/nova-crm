@props([
    'workspaces' => [],
    'current' => null,
    'favoriteWorkspaces' => [],
    'recentWorkspaces' => [],
])

@php
    $iconSvgs = [
        'home' => '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>',
        'users' => '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
        'task' => '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>',
        'hr' => '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>',
        'chart' => '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>',
        'cog' => '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
    ];

    $items = collect($workspaces)
        ->map(function ($w) {
            $row = is_array($w) ? $w : (array) $w;

            return [
                'id' => (string) ($row['id'] ?? ''),
                'label' => (string) ($row['label'] ?? ''),
                'href' => $row['href'] ?? null,
                'icon' => $row['icon'] ?? 'home',
                'footer' => (bool) ($row['footer'] ?? false),
            ];
        })
        ->filter(fn ($w) => $w['id'] !== '' && $w['href'])
        ->values();

    $currentItem = $items->firstWhere('id', $current);
    $currentLabel = $currentItem['label'] ?? __('Workspace');
    $currentIcon = $currentItem['icon'] ?? 'home';

    $favoriteIds = collect($favoriteWorkspaces)
        ->map(fn ($w) => is_array($w) ? ($w['id'] ?? null) : (is_string($w) ? $w : null))
        ->filter()
        ->values();

    $ordered = $items->sortBy(function ($w) use ($favoriteIds, $current) {
        if ($w['id'] === $current) {
            return 0;
        }
        if ($favoriteIds->contains($w['id'])) {
            return 1;
        }
        if ($w['footer'] ?? false) {
            return 3;
        }

        return 2;
    })->values();

    $hrefMap = $ordered->pluck('href', 'id')->all();
@endphp

{{--
  Workspace rows are server-rendered links (work without JS).
  Alpine handles open/close, filter, favorites, and background workspace persistence.
--}}
<div
    {{ $attributes->class(['relative shrink-0']) }}
    data-testid="header-workspace-switcher"
    x-data="{
        open: false,
        query: '',
        favorites: @js($favoriteIds->all()),
        hrefs: @js($hrefMap),
        labels: @js($ordered->pluck('label', 'id')->all()),
        matches(id) {
            const q = this.query.trim().toLowerCase();
            if (!q) return true;
            return String(this.labels[id] || '').toLowerCase().includes(q);
        },
        hasVisibleMatches() {
            return Object.keys(this.labels).some((id) => this.matches(id));
        },
        isFavorite(id) {
            return this.favorites.includes(id);
        },
        switchTo(event, id) {
            const href = this.hrefs[id] || event?.currentTarget?.getAttribute('href');
            if (!href || id === @js($current)) {
                if (event) event.preventDefault();
                this.open = false;
                return;
            }
            event?.preventDefault();
            this.open = false;
            if (window.NovaShell) {
                window.NovaShell.switchWorkspace(id, href);
            } else {
                window.location.assign(href);
            }
        },
        async toggleFavorite(id, event) {
            event?.preventDefault();
            event?.stopPropagation();
            if (!window.NovaShell?.toggleFavoriteWorkspace) return;
            try {
                this.favorites = await NovaShell.toggleFavoriteWorkspace(id);
            } catch (e) {}
        },
        focusSearch() {
            this.$nextTick(() => this.$refs.search?.focus());
        }
    }"
    @keydown.escape.window="open = false"
>
    <button
        type="button"
        class="inline-flex min-w-[8.5rem] max-w-[16rem] items-center gap-2 rounded-lg border border-line bg-surface-muted px-3 py-2 text-left text-sm font-semibold text-ink-heading hover:bg-app"
        @click.stop="open = ! open; if (open) focusSearch()"
        aria-haspopup="listbox"
        :aria-expanded="open.toString()"
        aria-controls="header-workspace-menu"
        aria-label="{{ __('Workspace switcher') }}"
    >
        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-primary-50 text-primary-700">
            {!! $iconSvgs[$currentIcon] ?? $iconSvgs['home'] !!}
        </span>
        <span class="min-w-0 flex-1 truncate">{{ $currentLabel }}</span>
        <svg class="h-4 w-4 shrink-0 text-ink-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 9l-7 7-7-7"/></svg>
    </button>

    <div
        id="header-workspace-menu"
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-1"
        class="absolute left-0 z-50 mt-1 w-80 max-w-[min(20rem,calc(100vw-1.5rem))] rounded-xl border border-line bg-surface-card shadow-lg"
        role="listbox"
        @click.outside="open = false"
    >
        <div class="max-h-72 overflow-y-auto overflow-x-hidden p-2">
            @forelse ($ordered as $workspace)
                @php
                    $isActive = $workspace['id'] === $current;
                    $isFav = $favoriteIds->contains($workspace['id']);
                @endphp
                <div
                    class="group flex items-center gap-0.5"
                    x-show="matches(@js($workspace['id']))"
                    data-workspace-id="{{ $workspace['id'] }}"
                >
                    <a
                        href="{{ $workspace['href'] }}"
                        class="flex min-w-0 flex-1 items-center gap-2.5 rounded-lg px-2.5 py-2 text-left text-sm transition hover:bg-surface-muted {{ $isActive ? 'bg-primary-50 text-primary-700 ring-1 ring-primary-200' : 'text-ink' }}"
                        @click="switchTo($event, @js($workspace['id']))"
                        role="option"
                        @if ($isActive) aria-selected="true" @endif
                    >
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $isActive ? 'bg-primary-100 text-primary-700' : 'bg-surface-muted text-ink-muted' }}">
                            {!! $iconSvgs[$workspace['icon']] ?? $iconSvgs['home'] !!}
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate font-medium">{{ $workspace['label'] }}</span>
                            @if ($isActive)
                                <span class="block text-[11px] text-primary-600">{{ __('Current workspace') }}</span>
                            @endif
                        </span>
                    </a>
                    <button
                        type="button"
                        class="rounded-md p-2 text-ink-muted opacity-70 hover:bg-surface-muted hover:opacity-100 group-hover:opacity-100"
                        @click="toggleFavorite(@js($workspace['id']), $event)"
                        :aria-label="isFavorite(@js($workspace['id'])) ? '{{ __('Unfavorite') }}' : '{{ __('Favorite') }}'"
                        :class="isFavorite(@js($workspace['id'])) ? 'text-amber-500 opacity-100' : ''"
                    >
                        <span x-text="isFavorite(@js($workspace['id'])) ? '★' : '☆'">{{ $isFav ? '★' : '☆' }}</span>
                    </button>
                </div>
            @empty
                <p class="px-3 py-4 text-sm text-ink-muted">{{ __('No workspaces available.') }}</p>
            @endforelse

            <p
                x-show="query.trim() && !hasVisibleMatches()"
                x-cloak
                class="px-3 py-3 text-sm text-ink-muted"
            >{{ __('No workspaces match your search.') }}</p>
        </div>

        <div class="border-t border-line p-2">
            <label class="sr-only" for="workspace-switcher-search">{{ __('Search workspaces') }}</label>
            <div class="relative">
                <svg class="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"/></svg>
                <input
                    id="workspace-switcher-search"
                    type="search"
                    x-model="query"
                    x-ref="search"
                    class="w-full rounded-lg border-line py-2 pl-8 pr-3 text-sm focus:border-primary-500 focus:ring-primary-500"
                    placeholder="{{ __('Search workspaces…') }}"
                    autocomplete="off"
                    @click.stop
                    @keydown.stop
                />
            </div>
        </div>
    </div>
</div>
