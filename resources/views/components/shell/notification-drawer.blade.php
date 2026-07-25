<div
    x-data="notificationDrawer()"
    x-on:open-drawer-notifications.window="open(); $store.shell.notificationsOpen = true"
>
    <div
        x-show="$store.shell.notificationsOpen"
        x-cloak
        class="fixed inset-0 z-drawer"
        role="dialog"
        aria-modal="true"
        aria-label="{{ __('Notifications') }}"
        @keydown.escape.window="if ($store.shell.notificationsOpen) close()"
    >
        <div class="absolute inset-0 bg-[var(--nova-color-bg-overlay)]" @click="close()"></div>
        <div
            class="absolute inset-y-0 right-0 flex w-full max-w-md flex-col bg-surface-card shadow-lg"
            x-transition:enter="transition ease-out duration-moderate"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-normal"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
        >
            <div class="flex items-center justify-between border-b border-line px-4 py-3">
                <div>
                    <h2 class="text-sm font-semibold text-ink-heading">{{ __('Notifications') }}</h2>
                    <p class="text-xs text-ink-muted"><span x-text="unread"></span> {{ __('unread') }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('notifications.index') }}" class="text-xs font-medium text-primary-600 hover:text-primary-700">{{ __('View all') }}</a>
                    <button type="button" class="rounded-md p-2 text-ink-muted hover:bg-surface-muted" @click="close()" aria-label="{{ __('Close') }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
            <div class="flex gap-1 overflow-x-auto border-b border-line px-3 py-2">
                <template x-for="cat in categories" :key="cat">
                    <button
                        type="button"
                        class="rounded-full px-2.5 py-1 text-xs font-medium"
                        :class="category === cat ? 'bg-primary-600 text-white' : 'bg-surface-muted text-ink-muted'"
                        @click="category = cat"
                        x-text="cat"
                    ></button>
                </template>
            </div>
            <div class="flex-1 overflow-y-auto">
                <template x-if="loading">
                    <div class="p-4"><x-ui.skeleton :lines="5" /></div>
                </template>
                <template x-if="!loading && filtered.length === 0">
                    <div class="px-6 py-10 text-center">
                        <h3 class="text-base font-semibold text-ink-heading">{{ __('You are all caught up') }}</h3>
                        <p class="mt-1 text-sm text-ink-muted">{{ __('No notifications in this category.') }}</p>
                    </div>
                </template>
                <template x-for="item in filtered" :key="item.id">
                    <a :href="item.url" class="block border-b border-line px-4 py-3 hover:bg-surface-muted" :class="item.read ? 'opacity-70' : ''">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium text-ink-heading" x-text="item.title"></p>
                                <p class="mt-0.5 text-xs text-ink-muted" x-text="item.body"></p>
                                <p class="mt-1 text-[11px] text-ink-muted">
                                    <span x-text="item.category"></span>
                                    <span x-show="item.workspace"> · <span x-text="item.workspace"></span></span>
                                    · <span x-text="item.created_at"></span>
                                </p>
                            </div>
                            <span x-show="!item.read" class="mt-1 h-2 w-2 shrink-0 rounded-full bg-primary-600"></span>
                        </div>
                    </a>
                </template>
            </div>
        </div>
    </div>
</div>
