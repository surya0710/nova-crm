@props([
    'workspaces' => collect(),
    'current' => null,
    'favoriteWorkspaces' => collect(),
    'recentWorkspaces' => collect(),
])

@php
    $items = collect($workspaces)
        ->reject(fn ($w) => (is_array($w) ? ($w['footer'] ?? false) : false))
        ->map(function ($w) {
            $row = is_array($w) ? $w : (array) $w;

            return [
                'id' => (string) ($row['id'] ?? ''),
                'label' => (string) ($row['label'] ?? ''),
                'href' => $row['href'] ?? null,
                'icon' => $row['icon'] ?? 'home',
            ];
        })
        ->filter(fn ($w) => $w['id'] !== '')
        ->values();

    $favoriteIds = collect($favoriteWorkspaces)
        ->map(fn ($w) => is_array($w) ? ($w['id'] ?? null) : (is_string($w) ? $w : null))
        ->filter()
        ->values()
        ->all();

    $recentIds = collect($recentWorkspaces)
        ->map(fn ($w) => is_array($w) ? ($w['id'] ?? null) : (is_string($w) ? $w : null))
        ->filter()
        ->values()
        ->all();

    $switcherPayload = [
        'workspaces' => $items->all(),
        'current' => $current,
        'favorites' => $favoriteIds,
        'recent' => $recentIds,
    ];
@endphp

<div
    {{ $attributes->class(['relative']) }}
    data-testid="header-workspace-switcher"
    x-data="headerWorkspaceSwitcher(@js($switcherPayload))"
    @keydown.escape.window="open = false"
    @click.outside="open = false"
>
    <button
        type="button"
        class="inline-flex min-w-0 max-w-[12rem] items-center gap-2 rounded-lg border border-line bg-surface-muted px-3 py-1.5 text-left text-sm font-semibold text-ink-heading hover:bg-app sm:max-w-[14rem]"
        @click="open = ! open"
        @click.stop
        aria-haspopup="listbox"
        :aria-expanded="open.toString()"
        aria-controls="header-workspace-menu"
    >
        <span class="truncate" x-text="currentLabel">{{ $items->firstWhere('id', $current)['label'] ?? __('Workspace') }}</span>
        <svg class="h-4 w-4 shrink-0 text-ink-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 9l-7 7-7-7"/></svg>
    </button>

    <div
        id="header-workspace-menu"
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute left-0 z-dropdown mt-1 w-72 origin-top-left max-w-[calc(100vw-2rem)] rounded-xl border border-line bg-surface-card p-2 shadow-lg"
        role="listbox"
        @click.stop
    >
        <input
            type="search"
            x-model="query"
            x-ref="search"
            @keydown.arrow-down.prevent="move(1)"
            @keydown.arrow-up.prevent="move(-1)"
            @keydown.enter.prevent="selectFocused()"
            class="mb-2 w-full rounded-md border-line text-sm focus:border-primary-500 focus:ring-primary-500"
            placeholder="{{ __('Search workspaces…') }}"
            aria-label="{{ __('Search workspaces') }}"
        />

        <template x-if="filteredFavorites.length">
            <div class="mb-2">
                <p class="px-2 py-1 text-[11px] font-semibold uppercase tracking-wide text-ink-muted">{{ __('Favorites') }}</p>
                <template x-for="workspace in filteredFavorites" :key="'fav-' + workspace.id">
                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            class="flex min-w-0 flex-1 items-center gap-2 rounded-md px-2 py-2 text-left text-sm hover:bg-surface-muted"
                            :class="workspace.id === current ? 'bg-primary-50 text-primary-700' : 'text-ink'"
                            @click="switchTo(workspace)"
                        >
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-surface-muted text-[11px] font-semibold uppercase text-ink-muted" x-text="(workspace.label || '?').slice(0, 2)"></span>
                            <span class="truncate" x-text="workspace.label"></span>
                        </button>
                        <button type="button" class="rounded p-1.5 text-amber-500 hover:bg-surface-muted" @click.stop="toggleFavorite(workspace.id)" :aria-label="'{{ __('Unfavorite') }}'">★</button>
                    </div>
                </template>
            </div>
        </template>

        <template x-if="filteredRecent.length">
            <div class="mb-2">
                <p class="px-2 py-1 text-[11px] font-semibold uppercase tracking-wide text-ink-muted">{{ __('Recent') }}</p>
                <template x-for="workspace in filteredRecent" :key="'recent-' + workspace.id">
                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            class="flex min-w-0 flex-1 items-center gap-2 rounded-md px-2 py-2 text-left text-sm hover:bg-surface-muted"
                            :class="workspace.id === current ? 'bg-primary-50 text-primary-700' : 'text-ink'"
                            @click="switchTo(workspace)"
                        >
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-surface-muted text-[11px] font-semibold uppercase text-ink-muted" x-text="(workspace.label || '?').slice(0, 2)"></span>
                            <span class="truncate" x-text="workspace.label"></span>
                        </button>
                        <button
                            type="button"
                            class="rounded p-1.5 text-ink-muted hover:bg-surface-muted"
                            @click.stop="toggleFavorite(workspace.id)"
                            :aria-label="'{{ __('Favorite workspace') }}'"
                            x-text="isFavorite(workspace.id) ? '★' : '☆'"
                        ></button>
                    </div>
                </template>
            </div>
        </template>

        <p class="px-2 py-1 text-[11px] font-semibold uppercase tracking-wide text-ink-muted">{{ __('All workspaces') }}</p>
        <div class="max-h-56 overflow-y-auto">
            {{-- Server-rendered list: visible if Alpine filtering has not taken over / as progressive enhancement --}}
            @foreach ($items as $index => $workspace)
                <div
                    class="flex items-center gap-1"
                    x-show="isVisible(@js($workspace['id']))"
                >
                    <button
                        type="button"
                        class="flex min-w-0 flex-1 items-center gap-2 rounded-md px-2 py-2 text-left text-sm hover:bg-surface-muted {{ ($workspace['id'] ?? null) === $current ? 'bg-primary-50 text-primary-700' : 'text-ink' }}"
                        :class="focusedIndex === {{ $index }} ? 'ring-1 ring-primary-500' : ''"
                        @click="switchTo(@js($workspace))"
                        role="option"
                        @if (($workspace['id'] ?? null) === $current) aria-selected="true" @endif
                    >
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-surface-muted text-[11px] font-semibold uppercase text-ink-muted">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($workspace['label'] ?? '?', 0, 2)) }}</span>
                        <span class="truncate">{{ $workspace['label'] }}</span>
                    </button>
                    <button
                        type="button"
                        class="rounded p-1.5 text-ink-muted hover:bg-surface-muted"
                        @click.stop="toggleFavorite(@js($workspace['id']))"
                        aria-label="{{ __('Favorite workspace') }}"
                        x-text="isFavorite(@js($workspace['id'])) ? '★' : '☆'"
                    >☆</button>
                </div>
            @endforeach

            <p x-show="filteredAll.length === 0" x-cloak class="px-2 py-3 text-sm text-ink-muted">{{ __('No workspaces match your search.') }}</p>

            @if ($items->isEmpty())
                <p class="px-2 py-3 text-sm text-ink-muted">{{ __('No workspaces available.') }}</p>
            @endif
        </div>
    </div>
</div>
