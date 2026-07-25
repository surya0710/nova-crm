import Alpine from 'alpinejs';

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;

async function jsonFetch(url, options = {}) {
    const response = await fetch(url, {
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf() || '',
            'X-Requested-With': 'XMLHttpRequest',
            ...(options.headers || {}),
        },
        credentials: 'same-origin',
        ...options,
    });

    if (!response.ok) {
        throw new Error(`Request failed: ${response.status}`);
    }

    return response.json();
}

export function registerShellStore() {
    Alpine.store('shell', {
        theme: 'light',
        density: 'comfortable',
        sidebarCollapsed: false,
        paletteOpen: false,
        searchOpen: false,
        notificationsOpen: false,
        endpoints: {},

        init(config = {}) {
            this.theme = config.theme || 'light';
            this.density = config.density || 'comfortable';
            this.sidebarCollapsed = !!config.sidebarCollapsed;
            this.endpoints = config.endpoints || {};
            this.applyDocumentTheme();
        },

        applyDocumentTheme() {
            const root = document.documentElement;
            let theme = this.theme;
            if (theme === 'system') {
                theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            root.setAttribute('data-theme', theme);
            root.setAttribute('data-density', this.density);
        },

        async persist(payload) {
            if (!this.endpoints.preferences) return;
            try {
                await jsonFetch(this.endpoints.preferences, {
                    method: 'PATCH',
                    body: JSON.stringify(payload),
                });
            } catch (e) {
                console.warn('Failed to persist shell preferences', e);
            }
        },

        toggleSidebar() {
            this.sidebarCollapsed = !this.sidebarCollapsed;
            this.persist({ sidebar_collapsed: this.sidebarCollapsed });
            window.dispatchEvent(new CustomEvent('shell-sidebar-toggle', {
                detail: { collapsed: this.sidebarCollapsed },
            }));
        },

        cycleTheme() {
            const order = ['light', 'dark', 'system'];
            const next = order[(order.indexOf(this.theme) + 1) % order.length];
            this.theme = next;
            this.applyDocumentTheme();
            this.persist({ theme: next });
        },

        openPalette() {
            this.searchOpen = false;
            this.notificationsOpen = false;
            this.paletteOpen = true;
            queueMicrotask(() => window.dispatchEvent(new CustomEvent('shell-palette-opened')));
        },

        closePalette() {
            this.paletteOpen = false;
        },

        openSearch() {
            if (window.Alpine?.store('shell') && this.endpoints.search) {
                this.paletteOpen = false;
                this.notificationsOpen = false;
                this.searchOpen = true;
                queueMicrotask(() => window.dispatchEvent(new CustomEvent('shell-search-opened')));
                return;
            }
            this.openPalette();
        },

        closeSearch() {
            this.searchOpen = false;
        },

        openNotifications() {
            this.paletteOpen = false;
            this.searchOpen = false;
            this.notificationsOpen = true;
            window.dispatchEvent(new CustomEvent('open-drawer-notifications'));
        },

        closeNotifications() {
            this.notificationsOpen = false;
        },
    });

    window.NovaShell = {
        switchWorkspace(workspace) {
            const endpoints = Alpine.store('shell').endpoints;
            if (!endpoints.workspace) return;
            jsonFetch(endpoints.workspace, {
                method: 'POST',
                body: JSON.stringify({ workspace }),
            }).catch(() => {});
        },
    };

    window.addEventListener('keydown', (event) => {
        const isMod = event.ctrlKey || event.metaKey;
        if (!isMod || event.key.toLowerCase() !== 'k') return;
        event.preventDefault();
        const store = Alpine.store('shell');
        if (store.searchOpen || store.paletteOpen) {
            store.closeSearch();
            store.closePalette();
            return;
        }
        if (event.shiftKey) {
            store.openPalette();
        } else {
            store.openSearch();
        }
    });
}

export function registerShellComponents() {
    Alpine.data('commandPalette', () => ({
        query: '',
        commands: [],
        recent: [],
        loading: false,
        selected: 0,

        init() {
            window.addEventListener('shell-palette-opened', () => {
                this.query = '';
                this.search();
                this.$nextTick(() => this.$refs.input?.focus());
            });
        },

        async search() {
            const url = Alpine.store('shell').endpoints.commands;
            if (!url) return;
            this.loading = true;
            try {
                const data = await jsonFetch(`${url}?q=${encodeURIComponent(this.query)}`);
                this.commands = data.commands || [];
                this.recent = data.recent || [];
                this.selected = 0;
            } catch (e) {
                this.commands = [];
            } finally {
                this.loading = false;
            }
        },

        move(delta) {
            if (!this.commands.length) return;
            this.selected = (this.selected + delta + this.commands.length) % this.commands.length;
        },

        runSelected() {
            const command = this.commands[this.selected];
            if (command) this.run(command);
        },

        async run(command) {
            const store = Alpine.store('shell');
            if (store.endpoints.commandsRecord) {
                jsonFetch(store.endpoints.commandsRecord, {
                    method: 'POST',
                    body: JSON.stringify({
                        id: command.id,
                        label: command.label,
                        href: command.href || null,
                    }),
                }).catch(() => {});
            }

            if (command.action?.startsWith('theme:')) {
                store.theme = command.action.replace('theme:', '');
                store.applyDocumentTheme();
                store.persist({ theme: store.theme });
                store.closePalette();
                return;
            }

            if (command.action === 'search:open') {
                store.closePalette();
                store.openSearch();
                return;
            }

            if (command.href) {
                window.location.href = command.href;
            }
        },
    }));

    Alpine.data('globalSearch', () => ({
        query: '',
        results: [],
        scopes: [{ key: 'all', label: 'All' }],
        recent: [],
        activeScope: 'all',
        loading: false,

        init() {
            window.addEventListener('shell-search-opened', () => {
                this.$nextTick(() => this.$refs.input?.focus());
                this.bootstrap();
            });
        },

        async bootstrap() {
            const url = Alpine.store('shell').endpoints.search;
            if (!url) return;
            try {
                const data = await jsonFetch(`${url}?q=`);
                this.scopes = data.scopes?.length ? data.scopes : this.scopes;
                this.recent = data.recent || [];
            } catch (e) {
                // ignore
            }
        },

        async search() {
            const url = Alpine.store('shell').endpoints.search;
            if (!url || !this.query.trim()) {
                this.results = [];
                return;
            }
            this.loading = true;
            try {
                const data = await jsonFetch(
                    `${url}?q=${encodeURIComponent(this.query)}&scope=${encodeURIComponent(this.activeScope)}`
                );
                this.results = data.results || [];
                this.scopes = data.scopes?.length ? data.scopes : this.scopes;
                this.recent = data.recent || this.recent;
            } catch (e) {
                this.results = [];
            } finally {
                this.loading = false;
            }
        },
    }));

    Alpine.data('notificationDrawer', () => ({
        items: [],
        unread: 0,
        loading: false,
        category: 'all',
        categories: ['all', 'general', 'crm', 'hr', 'projects'],

        get filtered() {
            if (this.category === 'all') return this.items;
            return this.items.filter((i) => (i.category || 'general') === this.category);
        },

        open() {
            Alpine.store('shell').notificationsOpen = true;
            this.fetch();
        },

        close() {
            Alpine.store('shell').closeNotifications();
        },

        async fetch() {
            const url = Alpine.store('shell').endpoints.notifications;
            if (!url) return;
            this.loading = true;
            try {
                const data = await jsonFetch(url);
                this.items = data.notifications || [];
                this.unread = data.unread || 0;
            } catch (e) {
                this.items = [];
            } finally {
                this.loading = false;
            }
        },
    }));
}
