<div
    x-data="{
        open: false,
        focusSearch() {
            this.open = true;
            this.$nextTick(() => this.$refs.searchInput?.focus());
        },
        closeSearch() {
            this.open = false;
            this.$refs.searchInput?.blur();
        },
        handleShortcut(event) {
            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
                event.preventDefault();
                this.focusSearch();
            }
            if (event.key === 'Escape') {
                this.closeSearch();
            }
        }
    }"
    @keydown.window="handleShortcut($event)"
    class="relative"
>
    <form
        method="GET"
        action="{{ route('knowledge.search') }}"
        class="flex items-center gap-2"
        role="search"
        aria-label="{{ __('Documentation search') }}"
    >
        <label for="knowledge-search-input" class="sr-only">{{ __('Search documentation') }}</label>
        <input
            id="knowledge-search-input"
            x-ref="searchInput"
            type="search"
            name="q"
            value="{{ $query ?? '' }}"
            placeholder="{{ __('Search documentation...') }}"
            class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
            autocomplete="off"
            @focus="open = true"
        />
        <kbd class="hidden sm:inline-flex items-center rounded border border-slate-200 px-2 py-1 text-xs text-slate-500">Ctrl+K</kbd>
        <x-primary-button type="submit">{{ __('Search') }}</x-primary-button>
    </form>

    <div
        x-show="open"
        x-cloak
        class="fixed inset-0 z-40 bg-slate-900/30"
        @click="closeSearch()"
        aria-hidden="true"
    ></div>
</div>
