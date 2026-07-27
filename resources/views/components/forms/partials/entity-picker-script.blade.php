<script>
function entityPicker(config) {
    return {
        endpoint: config.endpoint,
        inputName: config.inputName || null,
        placeholder: config.placeholder || 'Search…',
        debounceMs: config.debounceMs || 300,
        minSearchLength: config.minSearchLength || 0,
        parent: config.parent || null,
        fieldKey: config.fieldKey || null,
        selectedId: config.initialValue ? String(config.initialValue) : '',
        selectedItem: null,
        query: '',
        results: [],
        open: false,
        loading: false,
        loadingMore: false,
        highlighted: 0,
        page: 1,
        hasMore: false,
        debounceTimer: null,
        controller: null,
        init() {
            if (this.selectedId) {
                this.fetchSelected();
            }
        },
        bindValue(id) {
            if (this.parent && this.fieldKey) {
                this.parent[this.fieldKey] = id;
            }
            this.selectedId = id ? String(id) : '';
        },
        readValue() {
            if (this.parent && this.fieldKey) {
                return this.parent[this.fieldKey] ?? '';
            }
            return this.selectedId;
        },
        onQueryInput() {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => this.search(true), this.debounceMs);
        },
        openDropdown() {
            this.open = true;
            if (this.results.length === 0) {
                this.search(true);
            }
        },
        closeDropdown() {
            this.open = false;
            this.highlighted = 0;
        },
        async fetchSelected() {
            const id = this.readValue() || this.selectedId;
            if (!id) return;
            try {
                const url = new URL(this.endpoint, window.location.origin);
                url.searchParams.set('id', id);
                const res = await fetch(url.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!res.ok) return;
                const json = await res.json();
                const item = (json.data || [])[0];
                if (item) {
                    this.selectedItem = item;
                    this.selectedId = String(item.id);
                }
            } catch (e) {}
        },
        async search(reset) {
            const q = (this.query || '').trim();
            if (this.minSearchLength > 0 && q !== '' && q.length < this.minSearchLength) {
                this.results = [];
                return;
            }
            if (reset) {
                this.page = 1;
                this.hasMore = false;
                this.results = [];
            }
            if (this.controller) this.controller.abort();
            this.controller = new AbortController();
            this.loading = reset;
            this.loadingMore = !reset;
            try {
                const url = new URL(this.endpoint, window.location.origin);
                url.searchParams.set('q', q);
                url.searchParams.set('page', String(this.page));
                const res = await fetch(url.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    signal: this.controller.signal,
                    credentials: 'same-origin',
                });
                if (!res.ok) throw new Error('Search failed');
                const json = await res.json();
                const items = json.data || [];
                this.results = reset ? items : [...this.results, ...items];
                this.hasMore = !!(json.meta && json.meta.has_more);
                this.highlighted = 0;
                this.open = true;
            } catch (e) {
                if (e.name !== 'AbortError') {
                    this.results = reset ? [] : this.results;
                }
            } finally {
                this.loading = false;
                this.loadingMore = false;
            }
        },
        onScroll(event) {
            const el = event.target;
            if (!this.hasMore || this.loadingMore || this.loading) return;
            if (el.scrollTop + el.clientHeight >= el.scrollHeight - 24) {
                this.page += 1;
                this.search(false);
            }
        },
        selectItem(item) {
            this.selectedItem = item;
            this.bindValue(item.id);
            this.selectedId = String(item.id);
            this.query = '';
            this.closeDropdown();
        },
        clearSelection() {
            this.selectedItem = null;
            this.bindValue('');
            this.selectedId = '';
            this.query = '';
            this.results = [];
        },
        highlightNext() {
            if (!this.open || !this.results.length) return;
            this.highlighted = (this.highlighted + 1) % this.results.length;
        },
        highlightPrev() {
            if (!this.open || !this.results.length) return;
            this.highlighted = (this.highlighted - 1 + this.results.length) % this.results.length;
        },
        selectHighlighted() {
            if (!this.open || !this.results.length) return;
            this.selectItem(this.results[this.highlighted]);
        },
    };
}
</script>
