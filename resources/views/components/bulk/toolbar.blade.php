@props([
    'entityType',
    'actions' => [],
    'pageIds' => [],
    'redirectTo' => null,
    'exportEnabled' => false,
    'filters' => [],
])

@php
    $redirectTo = $redirectTo ?? url()->current();
    $showToolbar = count($actions) > 0 || $exportEnabled;
    $lookupBaseUrl = route('shell.lookups.search', ['entity' => '__ENTITY__']);
    $lookupFieldTypes = config('lookups.bulk_field_types', []);
    $lookupTypeEntities = config('lookups.bulk_type_entities', []);
@endphp

@if ($showToolbar)
<div
    class="space-y-3"
    x-data="bulkToolbar({
        actions: @js($actions),
        pageIds: @js($pageIds),
        entityType: @js($entityType),
        storeUrl: @js(route('administration.bulk.store')),
        exportUrl: @js(route('administration.exports.store')),
        exportEnabled: @js((bool) $exportEnabled),
        filters: @js($filters),
        redirectTo: @js($redirectTo),
        csrf: @js(csrf_token()),
        lookupBaseUrl: @js($lookupBaseUrl),
        lookupFieldTypes: @js($lookupFieldTypes),
        lookupTypeEntities: @js($lookupTypeEntities),
    })"
>
    <div
        x-show="hasSelection()"
        x-cloak
        class="sticky top-2 z-20 rounded-lg border border-line bg-surface-card px-3 py-2 shadow-sm"
    >
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-sm font-semibold text-ink-heading" x-text="selectionHeading()"></span>

            @if (count($actions) > 0)
                <template x-for="action in actions" :key="action.key">
                    <button
                        type="button"
                        class="inline-flex items-center rounded-md border border-line bg-surface-muted px-2.5 py-1.5 text-xs font-medium text-ink-heading hover:bg-app sm:text-sm"
                        @click="runAction(action.key)"
                    >
                        <span x-text="action.label"></span>
                    </button>
                </template>
            @endif

            @if ($exportEnabled)
                <x-ui.button type="button" variant="secondary" size="sm" @click="openExport()">
                    {{ __('Export') }}
                </x-ui.button>
            @endif

            <button type="button" class="ml-auto text-sm text-ink-muted hover:underline" @click="clearSelection()">
                {{ __('Clear selection') }}
            </button>
        </div>

        <div class="mt-2 flex flex-wrap items-center gap-3 text-sm text-ink-muted">
            <label class="inline-flex items-center gap-2 text-sm text-ink">
                <input type="checkbox" class="rounded border-line text-primary-600" @change="togglePage($event.target.checked)" :checked="pageSelected">
                {{ __('Select page') }}
            </label>
            <button type="button" class="text-primary-700 hover:underline" @click="selectAllFiltered()">
                {{ __('Select all filtered records') }}
            </button>
        </div>
    </div>

    {{ $slot }}

    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <div class="w-full max-w-lg rounded-xl border border-line bg-surface-card p-5 shadow-lg" @click.outside="showModal = false">
            <h3 class="text-lg font-semibold text-ink-heading" x-text="currentAction()?.label"></h3>
            <p class="mt-1 text-sm text-ink-muted" x-text="currentAction()?.confirmation"></p>

            <div class="mt-4 space-y-3">
                <template x-for="field in (currentAction()?.input_fields || [])" :key="field.key">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-heading" x-text="field.label + (field.required ? ' *' : '')"></label>
                        <template x-if="field.type === 'select'">
                            <select class="block w-full rounded-md border-line bg-surface-card text-sm" x-model="input[field.key]">
                                <option value="">{{ __('Select…') }}</option>
                                <template x-for="(label, value) in (field.options || {})" :key="value">
                                    <option :value="value" x-text="label"></option>
                                </template>
                            </select>
                        </template>
                        <template x-if="field.type === 'boolean'">
                            <label class="inline-flex items-center gap-2 text-sm">
                                <input type="checkbox" class="rounded border-line text-primary-600" x-model="input[field.key]">
                                <span x-text="field.label"></span>
                            </label>
                        </template>
                        <template x-if="isLookupField(field.type)">
                            <div
                                x-data="entityPicker({
                                    endpoint: lookupEndpoint(field),
                                    parent: input,
                                    fieldKey: field.key,
                                    placeholder: @js(__('Search…')),
                                    debounceMs: @js((int) config('lookups.debounce_ms', 300)),
                                    minSearchLength: @js((int) config('lookups.min_search_length', 0)),
                                })"
                            >
                                <div x-show="selectedItem" x-cloak class="mb-2 flex items-center justify-between gap-2 rounded-md border border-line bg-surface-muted/50 px-3 py-2">
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium text-ink-heading" x-text="selectedItem?.label"></p>
                                        <p class="truncate text-xs text-ink-muted" x-show="selectedItem?.subtitle" x-text="selectedItem?.subtitle"></p>
                                    </div>
                                    <span x-show="selectedItem?.badge" class="shrink-0 rounded-full bg-primary-50 px-2 py-0.5 text-xs font-medium text-primary-700" x-text="selectedItem?.badge"></span>
                                    <button type="button" class="shrink-0 text-xs text-ink-muted hover:text-ink" @click="clearSelection()">{{ __('Clear') }}</button>
                                </div>
                                <div class="relative">
                                    <input
                                        type="text"
                                        class="block w-full rounded-md border-line bg-surface-card text-sm"
                                        :placeholder="placeholder"
                                        x-model="query"
                                        @input="onQueryInput()"
                                        @focus="openDropdown()"
                                        @keydown.down.prevent="highlightNext()"
                                        @keydown.up.prevent="highlightPrev()"
                                        @keydown.enter.prevent="selectHighlighted()"
                                        @keydown.escape="closeDropdown()"
                                        autocomplete="off"
                                    >
                                    <div x-show="open" x-cloak class="absolute z-50 mt-1 max-h-48 w-full overflow-auto rounded-lg border border-line bg-surface-card shadow-lg" @scroll="onScroll($event)">
                                        <div x-show="loading" class="px-3 py-2 text-sm text-ink-muted">{{ __('Loading…') }}</div>
                                        <div x-show="!loading && results.length === 0" class="px-3 py-2 text-sm text-ink-muted">{{ __('No results found.') }}</div>
                                        <template x-for="(item, index) in results" :key="item.id">
                                            <button type="button" class="flex w-full items-start justify-between gap-2 px-3 py-2 text-left text-sm hover:bg-surface-muted/60" :class="index === highlighted ? 'bg-surface-muted/80' : ''" @click="selectItem(item)" @mouseenter="highlighted = index">
                                                <div class="min-w-0 flex-1">
                                                    <p class="truncate font-medium text-ink-heading" x-text="item.label"></p>
                                                    <p class="truncate text-xs text-ink-muted" x-show="item.subtitle" x-text="item.subtitle"></p>
                                                </div>
                                                <span x-show="item.badge" class="shrink-0 rounded-full bg-surface-muted px-2 py-0.5 text-xs text-ink-muted" x-text="item.badge"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <template x-if="!isLookupField(field.type) && field.type !== 'select' && field.type !== 'boolean'">
                            <input type="text" class="block w-full rounded-md border-line bg-surface-card text-sm" x-model="input[field.key]">
                        </template>
                    </div>
                </template>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <x-ui.button type="button" variant="secondary" size="sm" @click="showModal = false">{{ __('Cancel') }}</x-ui.button>
                <x-ui.button type="button" variant="primary" size="sm" @click="submit()" x-bind:disabled="submitting">
                    {{ __('Confirm & Execute') }}
                </x-ui.button>
            </div>
            <p class="mt-2 text-xs text-danger" x-show="error" x-text="error"></p>
        </div>
    </div>

    <div x-show="showExportModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <div class="w-full max-w-md rounded-xl border border-line bg-surface-card p-5 shadow-lg" @click.outside="showExportModal = false">
            <h3 class="text-lg font-semibold text-ink-heading">{{ __('Export selected records') }}</h3>
            <p class="mt-1 text-sm text-ink-muted" x-text="selectionLabel()"></p>

            <div class="mt-4 space-y-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-heading">{{ __('Format') }}</label>
                    <select class="block w-full rounded-md border-line bg-surface-card text-sm" x-model="exportFormat">
                        <option value="xlsx">{{ __('Excel (.xlsx)') }}</option>
                        <option value="csv">{{ __('CSV') }}</option>
                        <option value="pdf">{{ __('PDF') }}</option>
                    </select>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <x-ui.button type="button" variant="secondary" size="sm" @click="showExportModal = false">{{ __('Cancel') }}</x-ui.button>
                <x-ui.button type="button" variant="primary" size="sm" @click="submitExport()" x-bind:disabled="exportSubmitting">
                    {{ __('Generate file') }}
                </x-ui.button>
            </div>
            <p class="mt-2 text-xs text-danger" x-show="exportError" x-text="exportError"></p>
        </div>
    </div>
</div>

@once
@include('components.forms.partials.entity-picker-script')
<script>
function bulkToolbar(config) {
    return {
        actions: config.actions || [],
        pageIds: (config.pageIds || []).map(Number),
        selected: [],
        selectAllMode: false,
        actionKey: '',
        input: {},
        showModal: false,
        submitting: false,
        error: '',
        pageSelected: false,
        storeUrl: config.storeUrl,
        exportUrl: config.exportUrl,
        exportEnabled: !!config.exportEnabled,
        filters: config.filters || {},
        entityType: config.entityType,
        redirectTo: config.redirectTo,
        csrf: config.csrf,
        showExportModal: false,
        exportFormat: 'xlsx',
        exportSubmitting: false,
        exportError: '',
        lookupBaseUrl: config.lookupBaseUrl || '/shell/lookups/__ENTITY__',
        lookupFieldTypes: config.lookupFieldTypes || [],
        lookupTypeEntities: config.lookupTypeEntities || {},
        isLookupField(type) {
            return this.lookupFieldTypes.includes(type);
        },
        lookupEndpoint(field) {
            const entity = field.lookup || this.lookupTypeEntities[field.type] || field.type;
            return this.lookupBaseUrl.replace('__ENTITY__', entity);
        },
        hasSelection() {
            return this.selected.length > 0 || this.selectAllMode;
        },
        selectionHeading() {
            if (this.selectAllMode) {
                return this.selected.length + ' {{ __('records selected') }} ({{ __('all filtered') }})';
            }
            return this.selected.length + ' {{ __('records selected') }}';
        },
        currentAction() {
            return this.actions.find(a => a.key === this.actionKey) || null;
        },
        selectionLabel() {
            if (this.selectAllMode) {
                return this.selected.length + ' selected (all results mode)';
            }
            return this.selected.length + ' selected';
        },
        selectAllFiltered() {
            this.selectAllMode = true;
            this.pageSelected = true;
            this.selected = [...this.pageIds];
        },
        togglePage(checked) {
            this.pageSelected = checked;
            this.selectAllMode = false;
            this.selected = checked ? [...this.pageIds] : [];
        },
        clearSelection() {
            this.selected = [];
            this.selectAllMode = false;
            this.pageSelected = false;
        },
        toggleId(id, checked) {
            id = Number(id);
            this.selectAllMode = false;
            if (checked) {
                if (!this.selected.includes(id)) this.selected.push(id);
            } else {
                this.selected = this.selected.filter(x => x !== id);
            }
            this.pageSelected = this.pageIds.length > 0 && this.pageIds.every(pid => this.selected.includes(pid));
        },
        openConfigure() {
            this.error = '';
            this.input = {};
            const action = this.currentAction();
            (action?.input_fields || []).forEach(field => {
                if (field.type === 'boolean') this.input[field.key] = true;
            });
            this.showModal = true;
        },
        runAction(key) {
            this.actionKey = key;
            this.openConfigure();
        },
        openExport() {
            this.exportError = '';
            this.exportFormat = 'xlsx';
            this.showExportModal = true;
        },
        async submit() {
            this.submitting = true;
            this.error = '';
            try {
                const body = new FormData();
                body.append('_token', this.csrf);
                body.append('action_key', this.actionKey);
                body.append('selection_mode', this.selectAllMode ? 'all' : 'ids');
                body.append('confirm', '1');
                body.append('redirect_to', this.redirectTo);
                this.selected.forEach(id => body.append('ids[]', id));
                Object.entries(this.input).forEach(([key, value]) => {
                    if (typeof value === 'boolean') {
                        body.append('input[' + key + ']', value ? '1' : '0');
                    } else if (value !== null && value !== undefined) {
                        body.append('input[' + key + ']', value);
                    }
                });
                const response = await fetch(this.storeUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body,
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    this.error = data.message || Object.values(data.errors || {}).flat().join(' ') || 'Bulk action failed.';
                    this.submitting = false;
                    return;
                }
                window.location.href = data.redirect || this.redirectTo;
            } catch (e) {
                this.error = e.message || 'Bulk action failed.';
                this.submitting = false;
            }
        },
        async submitExport() {
            this.exportSubmitting = true;
            this.exportError = '';
            try {
                const body = new FormData();
                body.append('_token', this.csrf);
                body.append('entity_type', this.entityType);
                body.append('format', this.exportFormat);
                body.append('selection_mode', this.selectAllMode ? 'filtered' : 'ids');
                body.append('redirect_to', this.redirectTo);
                this.selected.forEach(id => body.append('ids[]', id));
                Object.entries(this.filters || {}).forEach(([key, value]) => {
                    if (value !== null && value !== undefined && value !== '') {
                        body.append('filters[' + key + ']', value);
                    }
                });
                const response = await fetch(this.exportUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body,
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    this.exportError = data.message || Object.values(data.errors || {}).flat().join(' ') || 'Export failed.';
                    this.exportSubmitting = false;
                    return;
                }
                window.location.href = data.redirect || this.redirectTo;
            } catch (e) {
                this.exportError = e.message || 'Export failed.';
                this.exportSubmitting = false;
            }
        }
    }
}
</script>
@endonce
@else
    {{ $slot }}
@endif
