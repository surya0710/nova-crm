@props([
    'entityType',
    'actions' => [],
    'pageIds' => [],
    'redirectTo' => null,
])

@php
    $redirectTo = $redirectTo ?? url()->current();
@endphp

@if (count($actions) > 0)
<div
    class="space-y-3"
    x-data="bulkToolbar({
        actions: @js($actions),
        pageIds: @js($pageIds),
        entityType: @js($entityType),
        storeUrl: @js(route('administration.bulk.store')),
        redirectTo: @js($redirectTo),
        csrf: @js(csrf_token()),
    })"
>
    <div class="flex flex-wrap items-center gap-3 rounded-lg border border-line bg-surface-muted/40 px-3 py-2">
        <label class="inline-flex items-center gap-2 text-sm text-ink">
            <input type="checkbox" class="rounded border-line text-primary-600" @change="togglePage($event.target.checked)" :checked="pageSelected">
            {{ __('Select page') }}
        </label>
        <button type="button" class="text-sm text-primary-700 hover:underline" @click="selectAllMode = true; selected = [...pageIds]">
            {{ __('Select all results') }}
        </button>
        <button type="button" class="text-sm text-ink-muted hover:underline" @click="clearSelection()">
            {{ __('Clear') }}
        </button>
        <span class="text-sm text-ink-muted" x-text="selectionLabel()"></span>

        <div class="ml-auto flex flex-wrap items-center gap-2">
            <select x-model="actionKey" class="block rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500">
                <option value="">{{ __('Bulk actions…') }}</option>
                <template x-for="action in actions" :key="action.key">
                    <option :value="action.key" x-text="action.label"></option>
                </template>
            </select>
            <x-ui.button type="button" variant="secondary" size="sm" @click="openConfigure()" x-bind:disabled="!actionKey || selected.length === 0">
                {{ __('Configure') }}
            </x-ui.button>
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
                        <template x-if="field.type !== 'select' && field.type !== 'boolean'">
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
</div>

@once
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
        redirectTo: config.redirectTo,
        csrf: config.csrf,
        currentAction() {
            return this.actions.find(a => a.key === this.actionKey) || null;
        },
        selectionLabel() {
            if (this.selectAllMode) {
                return this.selected.length + ' selected (all results mode)';
            }
            return this.selected.length + ' selected';
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
        }
    }
}
</script>
@endonce
@else
    {{ $slot }}
@endif
