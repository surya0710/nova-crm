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

window.NovaOrgSwitch = {
    submit(select) {
        const option = select.options[select.selectedIndex];
        const url = option?.dataset?.url;
        const form = select.form;
        if (!url || !form) {
            return;
        }
        if (String(select.dataset.current || '') === String(select.value || '')) {
            return;
        }
        form.action = url;
        form.submit();
    },
};

export function registerShellStore() {
    Alpine.store('shell', {
        theme: 'light',
        density: 'comfortable',
        sidebarCollapsed: false,
        paletteOpen: false,
        searchOpen: false,
        notificationsOpen: false,
        currentWorkspace: 'home',
        searchDefaultScope: 'all',
        endpoints: {},

        init(config = {}) {
            this.theme = config.theme || 'light';
            this.density = config.density || 'comfortable';
            this.sidebarCollapsed = !!config.sidebarCollapsed;
            this.currentWorkspace = config.currentWorkspace || 'home';
            this.searchDefaultScope = config.searchDefaultScope || 'all';
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
        switchWorkspace(workspace, href = null) {
            const store = Alpine.store('shell');
            const endpoints = store.endpoints;

            const navigate = (target) => {
                if (target) {
                    window.location.assign(target);
                }
            };

            // Prefer direct navigation — never block the user on a fetch.
            if (href) {
                if (endpoints.workspace) {
                    jsonFetch(endpoints.workspace, {
                        method: 'POST',
                        body: JSON.stringify({ workspace }),
                    })
                        .then((data) => {
                            if (data?.workspace) {
                                store.currentWorkspace = data.workspace;
                            }
                            if (data?.search_scope) {
                                store.searchDefaultScope = data.search_scope;
                            }
                        })
                        .catch(() => {});
                }
                navigate(href);

                return;
            }

            if (!endpoints.workspace) {
                return;
            }

            jsonFetch(endpoints.workspace, {
                method: 'POST',
                body: JSON.stringify({ workspace }),
            })
                .then((data) => {
                    if (data?.workspace) {
                        store.currentWorkspace = data.workspace;
                    }
                    if (data?.search_scope) {
                        store.searchDefaultScope = data.search_scope;
                    }
                    navigate(data?.href || href);
                })
                .catch(() => {
                    navigate(href);
                });
        },
        toggleFavoriteWorkspace(workspace) {
            const endpoint = Alpine.store('shell').endpoints.favoriteWorkspaces;
            if (!endpoint) return Promise.resolve([]);
            return jsonFetch(endpoint, {
                method: 'POST',
                body: JSON.stringify({ workspace }),
            }).then((data) => data.favorite_workspaces || []);
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

function headerWorkspaceSwitcher(initial = {}) {
    const favorites = Array.isArray(initial.favorites)
        ? initial.favorites.map((item) => (typeof item === 'string' ? item : item?.id)).filter(Boolean)
        : [];

    const recent = Array.isArray(initial.recent)
        ? initial.recent.map((item) => (typeof item === 'string' ? item : item?.id)).filter(Boolean)
        : [];

    return {
        open: false,
        query: '',
        workspaces: Array.isArray(initial.workspaces) ? initial.workspaces : [],
        current: initial.current ?? null,
        favorites,
        recent,
        focusedIndex: 0,

        init() {
            this.$watch('open', (isOpen) => {
                if (isOpen) {
                    this.focusedIndex = 0;
                    this.$nextTick(() => this.$refs.search?.focus());
                }
            });
        },

        get currentLabel() {
            const match = this.workspaces.find((w) => w.id === this.current);
            return match?.label || 'Workspace';
        },

        get filteredAll() {
            const q = this.query.trim().toLowerCase();
            if (!q) return this.workspaces;
            return this.workspaces.filter((w) => (w.label || '').toLowerCase().includes(q));
        },

        get filteredFavorites() {
            const ids = new Set(this.favorites);
            return this.filteredAll.filter((w) => ids.has(w.id));
        },

        get filteredRecent() {
            const favoriteIds = new Set(this.favorites);
            const recentIds = new Set(this.recent);
            return this.filteredAll.filter((w) => recentIds.has(w.id) && !favoriteIds.has(w.id));
        },

        isVisible(id) {
            return this.filteredAll.some((w) => w.id === id);
        },

        isFavorite(id) {
            return this.favorites.includes(id);
        },

        move(delta) {
            if (!this.filteredAll.length) return;
            this.focusedIndex = (this.focusedIndex + delta + this.filteredAll.length) % this.filteredAll.length;
        },

        selectFocused() {
            const workspace = this.filteredAll[this.focusedIndex];
            if (workspace) this.switchTo(workspace);
        },

        switchTo(workspace) {
            this.open = false;
            if (!workspace?.id) return;
            if (window.NovaShell) {
                NovaShell.switchWorkspace(workspace.id, workspace.href || null);
            } else if (workspace.href) {
                window.location.href = workspace.href;
            }
        },

        async toggleFavorite(workspaceId) {
            if (!window.NovaShell?.toggleFavoriteWorkspace) return;
            try {
                this.favorites = await NovaShell.toggleFavoriteWorkspace(workspaceId);
            } catch (e) {
                // ignore
            }
        },
    };
}

window.headerWorkspaceSwitcher = headerWorkspaceSwitcher;

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
            const store = Alpine.store('shell');
            this.activeScope = store.searchDefaultScope || 'all';
            window.addEventListener('shell-search-opened', () => {
                this.$nextTick(() => this.$refs.input?.focus());
                this.bootstrap();
            });
        },

        async bootstrap() {
            const store = Alpine.store('shell');
            this.activeScope = store.searchDefaultScope || this.activeScope || 'all';
            const url = store.endpoints.search;
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

    Alpine.data('headerWorkspaceSwitcher', headerWorkspaceSwitcher);
}
