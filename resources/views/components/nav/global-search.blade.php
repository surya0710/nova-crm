<div
    x-data="globalSearch()"
    x-show="$store.shell.searchOpen"
    x-cloak
    class="fixed inset-0 z-command"
    role="dialog"
    aria-modal="true"
    aria-label="{{ __('Global search') }}"
    @keydown.escape.window="if ($store.shell.searchOpen) $store.shell.closeSearch()"
>
    <div class="absolute inset-0 bg-[var(--nova-color-bg-overlay)]" @click="$store.shell.closeSearch()"></div>
    <div class="relative mx-auto mt-[10vh] w-full max-w-2xl px-4">
        <div class="overflow-hidden rounded-xl border border-line bg-surface-card shadow-lg">
            <div class="border-b border-line px-3 py-2">
                <input
                    type="search"
                    x-model="query"
                    x-ref="input"
                    @input.debounce.250ms="search()"
                    class="w-full rounded-md border-line text-sm focus:border-primary-500 focus:ring-primary-500"
                    placeholder="{{ __('Search across your workspace…') }}"
                />
                <div class="mt-2 flex flex-wrap gap-1.5">
                    <template x-for="scope in scopes" :key="scope.key">
                        <button
                            type="button"
                            class="rounded-full px-2.5 py-1 text-xs font-medium"
                            :class="activeScope === scope.key ? 'bg-primary-600 text-white' : 'bg-surface-muted text-ink-muted hover:bg-app'"
                            @click="activeScope = scope.key; search()"
                            x-text="scope.label"
                        ></button>
                    </template>
                </div>
            </div>
            <div class="max-h-96 overflow-y-auto">
                <template x-if="!query && recent.length">
                    <div class="p-3">
                        <p class="mb-2 px-1 text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('Recent searches') }}</p>
                        <template x-for="item in recent" :key="item.q + (item.at || '')">
                            <button type="button" class="block w-full rounded-md px-2 py-1.5 text-left text-sm hover:bg-surface-muted" @click="query = item.q; search()" x-text="item.q"></button>
                        </template>
                    </div>
                </template>
                <template x-if="loading">
                    <div class="px-4 py-6"><x-ui.loading /></div>
                </template>
                <template x-if="!loading && query && results.length === 0">
                    <div class="px-6 py-8 text-center">
                        <h3 class="text-base font-semibold text-ink-heading">{{ __('No results') }}</h3>
                        <p class="mt-1 text-sm text-ink-muted">{{ __('Try a different keyword or scope.') }}</p>
                    </div>
                </template>
                <template x-for="result in results" :key="result.url + result.title">
                    <a :href="result.url" class="block border-b border-line px-4 py-3 hover:bg-surface-muted" @click="$store.shell.closeSearch()">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium text-ink-heading" x-text="result.title"></p>
                                <p class="text-xs text-ink-muted" x-text="result.subtitle || result.label"></p>
                            </div>
                            <span class="text-[11px] font-medium uppercase tracking-wide text-ink-muted" x-text="result.type"></span>
                        </div>
                    </a>
                </template>
            </div>
            <div class="border-t border-line px-4 py-2 text-xs text-ink-muted">
                <a href="{{ route('search.index') }}" class="hover:text-ink">{{ __('Open full search') }}</a>
            </div>
        </div>
    </div>
</div>
