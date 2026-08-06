<div
    x-data="commandPalette()"
    x-show="$store.shell.paletteOpen"
    x-cloak
    class="fixed inset-0 z-command"
    role="dialog"
    aria-modal="true"
    aria-label="{{ __('Command palette') }}"
    @keydown.escape.window="if ($store.shell.paletteOpen) $store.shell.closePalette()"
>
    <div class="absolute inset-0 bg-[var(--nova-color-bg-overlay)]" @click="$store.shell.closePalette()"></div>
    <div class="relative mx-auto mt-[12vh] w-full max-w-xl px-4">
        <div class="overflow-hidden rounded-xl border border-line bg-surface-card shadow-lg">
            <div class="flex items-center gap-2 border-b border-line px-3">
                <svg class="h-5 w-5 text-ink-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"/></svg>
                <input
                    type="search"
                    x-model="query"
                    x-ref="input"
                    @input.debounce.200ms="search()"
                    @keydown.enter.prevent="runSelected()"
                    @keydown.arrow-down.prevent="move(1)"
                    @keydown.arrow-up.prevent="move(-1)"
                    class="w-full border-0 bg-transparent py-3 text-sm text-ink focus:ring-0"
                    placeholder="{{ __('Type a command or search…') }}"
                    aria-autocomplete="list"
                />
            </div>
            <div class="max-h-80 overflow-y-auto py-2">
                <template x-if="loading">
                    <div class="px-4 py-6"><x-ui.loading /></div>
                </template>
                <template x-if="!loading && commands.length === 0">
                    <p class="px-4 py-6 text-center text-sm text-ink-muted">{{ __('No commands found') }}</p>
                </template>
                <template x-for="(command, index) in commands" :key="command.id">
                    <button
                        type="button"
                        class="flex w-full items-center justify-between gap-3 px-4 py-2 text-left text-sm hover:bg-surface-muted"
                        :class="index === selected ? 'bg-surface-muted' : ''"
                        @click="run(command)"
                        @mouseenter="selected = index"
                    >
                        <span>
                            <span class="block font-medium text-ink-heading" x-text="command.label"></span>
                            <span class="block text-xs text-ink-muted" x-text="command.group"></span>
                        </span>
                    </button>
                </template>
            </div>
        </div>
    </div>
</div>
