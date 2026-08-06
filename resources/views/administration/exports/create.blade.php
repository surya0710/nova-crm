<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Export :entity', ['entity' => $label])"
        :subtitle="__('Choose format, columns, and dataset scope')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Administration'), 'href' => route('administration.home')],
                ['label' => __('Export Center'), 'href' => route('administration.exports.index')],
                ['label' => $label, 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <form method="post" action="{{ route('administration.exports.store') }}" class="mx-auto max-w-3xl space-y-6">
            @csrf
            <input type="hidden" name="entity_type" value="{{ $entity }}">

            <x-entity.section :title="__('Format')">
                <div class="grid gap-2 sm:grid-cols-3">
                    @foreach ($formats as $key => $meta)
                        <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-line px-3 py-3 text-sm has-[:checked]:border-primary-400 has-[:checked]:bg-primary-50/40">
                            <input type="radio" name="format" value="{{ $key }}" class="text-primary-600" @checked(old('format', 'xlsx') === $key) required>
                            <span>{{ $meta['label'] ?? strtoupper($key) }}</span>
                        </label>
                    @endforeach
                </div>
                @error('format') <p class="mt-2 text-sm text-danger">{{ $message }}</p> @enderror
            </x-entity.section>

            <x-entity.section :title="__('Dataset')">
                <select name="selection_mode" class="block w-full rounded-md border-line bg-surface-card text-sm" required>
                    <option value="complete" @selected(old('selection_mode', 'complete') === 'complete')>{{ __('Complete dataset') }}</option>
                    <option value="filtered" @selected(old('selection_mode') === 'filtered')>{{ __('Current / saved filters (via API or listing)') }}</option>
                </select>
                <p class="mt-2 text-xs text-ink-muted">
                    {{ __('For selected records or the current page, use the Export button on a module listing.') }}
                </p>
            </x-entity.section>

            <x-entity.section :title="__('Columns')">
                <p class="mb-3 text-sm text-ink-muted">{{ __('Select and order columns for this export. Defaults are pre-selected.') }}</p>
                <div class="space-y-2" id="export-columns">
                    @foreach ($columns as $column)
                        @continue($column['hidden'] ?? false)
                        <label class="flex items-center gap-2 rounded-md border border-line px-3 py-2 text-sm">
                            <input
                                type="checkbox"
                                name="columns[]"
                                value="{{ $column['key'] }}"
                                class="rounded border-line text-primary-600"
                                @checked(in_array($column['key'], old('columns', $defaultColumns), true))
                            >
                            <span class="font-medium text-ink-heading">{{ $column['label'] }}</span>
                            <span class="text-xs text-ink-muted">{{ $column['data_type'] }}</span>
                        </label>
                    @endforeach
                </div>
                @error('columns') <p class="mt-2 text-sm text-danger">{{ $message }}</p> @enderror
            </x-entity.section>

            <div class="flex justify-end gap-2">
                <x-ui.button :href="route('administration.exports.index')" variant="secondary">{{ __('Cancel') }}</x-ui.button>
                <x-ui.button type="submit" variant="primary">{{ __('Generate export') }}</x-ui.button>
            </div>
        </form>
    </x-layouts.entity-listing>
</x-app-layout>
