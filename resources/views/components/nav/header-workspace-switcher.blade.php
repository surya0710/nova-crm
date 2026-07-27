@props([
    'workspaces' => collect(),
    'current' => null,
    'favoriteWorkspaces' => collect(),
    'recentWorkspaces' => collect(),
])

@php
    $items = collect($workspaces)->reject(fn ($w) => ($w['footer'] ?? false))->values();
    $switcherPayload = [
        'workspaces' => $items->all(),
        'current' => $current,
        'favorites' => collect($favoriteWorkspaces)->pluck('id')->values()->all(),
        'recent' => collect($recentWorkspaces)->values()->all(),
    ];
@endphp

<div
    {{ $attributes->class(['relative']) }}
    x-data="headerWorkspaceSwitcher(@js($switcherPayload))"
    @click.outside="open = false"
>
    <button
        type="button"
        class="inline-flex min-w-0 max-w-[12rem] items-center gap-2 rounded-lg border border-line bg-surface-muted px-3 py-1.5 text-left text-sm font-semibold text-ink-heading hover:bg-app sm:max-w-[14rem]"
        @click="open = ! open"
        aria-haspopup="listbox"
        :aria-expanded="open.toString()"
    >
        <span class="truncate" x-text="currentLabel"></span>
        <svg class="h-4 w-4 shrink-0 text-ink-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 9l-7 7-7-7"/></svg>
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition
        class="absolute left-0 z-dropdown mt-1 w-72 max-w-[calc(100vw-2rem)] rounded-xl border border-line bg-surface-card p-2 shadow-lg"
        role="listbox"
        @keydown.escape.window="open = false"
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
        />

        <template x-if="filteredFavorites.length">
            <div class="mb-2">
                <p class="px-2 py-1 text-[11px] font-semibold uppercase tracking-wide text-ink-muted">{{ __('Favorites') }}</p>
                <template x-for="(workspace, index) in filteredFavorites" :key="'fav-' + workspace.id">
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
                        <button type="button" class="rounded p-1.5 text-amber-500 hover:bg-surface-muted" @click.stop="toggleFavorite(workspace.id)" :aria-label="'{{ __('Unfavorite') }}'">
                            ★
                        </button>
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
            <template x-for="(workspace, index) in filteredAll" :key="workspace.id">
                <div class="flex items-center gap-1">
                    <button
                        type="button"
                        class="flex min-w-0 flex-1 items-center gap-2 rounded-md px-2 py-2 text-left text-sm hover:bg-surface-muted"
                        :class="[
                            workspace.id === current ? 'bg-primary-50 text-primary-700' : 'text-ink',
                            focusedIndex === index ? 'ring-1 ring-primary-500' : '',
                        ]"
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
            <p x-show="!filteredAll.length" class="px-2 py-3 text-sm text-ink-muted">{{ __('No workspaces match your search.') }}</p>
        </div>
    </div>
</div>
