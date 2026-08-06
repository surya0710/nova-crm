<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Import Preview')"
        :subtitle="$session->original_filename"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Administration'), 'href' => route('administration.home')],
                ['label' => __('Import Center'), 'href' => route('administration.imports.index')],
                ['label' => $entityLabel, 'href' => route('administration.imports.create', $session->entity_type)],
                ['label' => __('Preview'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="space-y-6">
            @include('imports._steps', ['current' => 'preview'])

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
                <x-ui.stat-card :label="__('Rows')" :value="(string) $preview->totalRows" />
                <x-ui.stat-card :label="__('Valid')" :value="(string) $preview->validRows" class="!border-success/30" />
                <x-ui.stat-card :label="__('Invalid')" :value="(string) $preview->invalidRows" class="!border-danger/30" />
                <x-ui.stat-card :label="__('Errors')" :value="(string) count($preview->errors)" class="!border-danger/30" />
                <x-ui.stat-card
                    :label="__('Duplicates')"
                    :value="(string) (int) ($session->validation_summary['duplicate_rows'] ?? 0)"
                    class="!border-warning/30"
                />
            </div>

            <x-entity.section :title="__('Field mapping')">
                <form method="POST" action="{{ route('administration.imports.map', $session) }}" class="space-y-4">
                    @csrf
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($fields as $field)
                            <x-forms.field :label="$field->label.($field->required ? ' *' : '')" :name="'mapping.'.$field->key">
                                <select name="mapping[{{ $field->key }}]" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                    <option value="">{{ __('— Ignore —') }}</option>
                                    @foreach ($preview->detectedColumns as $header)
                                        <option
                                            value="{{ $header }}"
                                            @selected(($preview->mappedFields[$field->key] ?? null) === $header)
                                        >{{ $header }}</option>
                                    @endforeach
                                </select>
                            </x-forms.field>
                        @endforeach
                    </div>
                    <x-ui.button type="submit" variant="secondary" size="sm">{{ __('Apply mapping') }}</x-ui.button>
                </form>
            </x-entity.section>

            <x-entity.section :title="__('Row preview')" :padding="false">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-line text-sm">
                        <thead class="bg-surface-muted/50">
                            <tr>
                                <th class="px-4 py-2 text-left font-medium text-ink-muted">{{ __('Row') }}</th>
                                <th class="px-4 py-2 text-left font-medium text-ink-muted">{{ __('Status') }}</th>
                                <th class="px-4 py-2 text-left font-medium text-ink-muted">{{ __('Values') }}</th>
                                <th class="px-4 py-2 text-left font-medium text-ink-muted">{{ __('Errors') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach (array_slice($preview->rows, 0, 50) as $row)
                                <tr>
                                    <td class="px-4 py-2 text-ink">{{ $row['row_number'] }}</td>
                                    <td class="px-4 py-2">
                                        @if ($row['valid'])
                                            <x-ui.badge variant="success">{{ __('Valid') }}</x-ui.badge>
                                        @else
                                            <x-ui.badge variant="danger">{{ __('Invalid') }}</x-ui.badge>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-ink-muted">
                                        {{ collect($row['values'])->filter()->map(fn ($v, $k) => $k.': '.$v)->take(4)->implode(' · ') }}
                                    </td>
                                    <td class="px-4 py-2 text-danger">{{ implode('; ', $row['errors']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-entity.section>

            <div class="flex flex-wrap items-center gap-3">
                @if ($preview->validRows > 0 && $session->status === \App\Models\ImportSession::STATUS_READY)
                    <form method="POST" action="{{ route('administration.imports.execute', $session) }}" class="flex flex-wrap items-end gap-3">
                        @csrf
                        <input type="hidden" name="confirm" value="1">
                        <x-forms.field :label="__('Duplicate strategy')" name="duplicate_strategy">
                            <select name="duplicate_strategy" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                <option value="skip" @selected(($session->metadata['duplicate_strategy'] ?? 'skip') === 'skip')>{{ __('Skip') }}</option>
                                <option value="update" @selected(($session->metadata['duplicate_strategy'] ?? null) === 'update')>{{ __('Update') }}</option>
                                <option value="create" @selected(($session->metadata['duplicate_strategy'] ?? null) === 'create')>{{ __('Create') }}</option>
                            </select>
                        </x-forms.field>
                        <x-ui.button type="submit" variant="primary">
                            {{ __('Start import (:count valid rows)', ['count' => $preview->validRows]) }}
                        </x-ui.button>
                    </form>
                @endif

                @if ($preview->invalidRows > 0 || count($preview->errors) > 0)
                    <x-ui.button :href="route('administration.imports.errors', $session)" variant="secondary">
                        {{ __('Download error report') }}
                    </x-ui.button>
                @endif

                <x-ui.button :href="route('administration.imports.create', $session->entity_type)" variant="secondary">
                    {{ __('Upload another file') }}
                </x-ui.button>
            </div>
        </div>
    </x-layouts.entity-listing>
</x-app-layout>
