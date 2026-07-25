<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Import Preview')"
        :subtitle="$session->original_filename"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => __('Imports'), 'href' => route('customers.import.create')],
                ['label' => crm_term('customers'), 'href' => route('customers.import.create')],
                ['label' => __('Preview'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button :href="route('customers.import.create')" variant="secondary" size="sm">
                {{ __('Upload another file') }}
            </x-ui.button>
        </x-slot:actions>

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

            <x-entity.section :title="__('Column mapping')">
                <dl class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                    @foreach ($preview->mappedFields as $field => $header)
                        <div class="flex items-center justify-between gap-3 rounded-lg bg-surface-muted/50 px-3 py-2 text-sm">
                            <dt class="font-medium text-ink-heading">{{ $field }}</dt>
                            <dd class="text-ink-muted">{{ $header ?: '—' }}</dd>
                        </div>
                    @endforeach
                </dl>
                @if (! empty($preview->unknownColumns))
                    <p class="mt-3 text-sm text-warning">
                        {{ __('Unknown columns:') }} {{ implode(', ', $preview->unknownColumns) }}
                    </p>
                @endif
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
                                    <td class="px-4 py-2 text-danger">
                                        {{ implode('; ', $row['errors']) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if (count($preview->rows) > 50)
                    <p class="border-t border-line px-5 py-3 text-xs text-ink-muted">
                        {{ __('Showing the first 50 rows. Download the error report for the full list.') }}
                    </p>
                @endif
            </x-entity.section>

            <div class="flex flex-wrap items-center gap-3">
                @if ($preview->validRows > 0 && $session->status === \App\Models\ImportSession::STATUS_READY)
                    <form method="POST" action="{{ route('customers.import.execute', $session) }}">
                        @csrf
                        <x-ui.button type="submit" variant="primary">
                            {{ __('Import :count valid rows', ['count' => $preview->validRows]) }}
                        </x-ui.button>
                    </form>
                @endif

                @if (! empty($preview->errors))
                    <x-ui.button :href="route('customers.import.report', $session)" variant="secondary" size="sm">
                        {{ __('Download Validation Report') }}
                    </x-ui.button>
                    <x-ui.button :href="route('customers.import.report.xlsx', $session)" variant="secondary" size="sm">
                        {{ __('Download Validation Report (Excel)') }}
                    </x-ui.button>
                @endif

                @if ($preview->invalidRows > 0 || ! empty($preview->errors))
                    <x-ui.button :href="route('customers.import.errors', $session)" variant="link" size="sm">
                        {{ __('Download error report') }}
                    </x-ui.button>
                @endif

                <x-ui.button :href="route('customers.index')" variant="link" size="sm">{{ __('Cancel') }}</x-ui.button>
            </div>
        </div>
    </x-layouts.entity-listing>
</x-app-layout>
