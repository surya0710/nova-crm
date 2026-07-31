<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Import Summary')"
        :subtitle="$session->original_filename"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => __('Imports'), 'href' => route('leads.import.create')],
                ['label' => crm_term('leads'), 'href' => route('leads.import.create')],
                ['label' => __('Summary'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button :href="route('leads.index')" variant="secondary" size="sm">
                {{ __('Back to :leads', ['leads' => crm_term('leads')]) }}
            </x-ui.button>
        </x-slot:actions>

        <div class="mx-auto max-w-3xl space-y-6">
            @include('imports._steps', ['current' => 'summary'])

            @if ($session->status === \App\Models\ImportSession::STATUS_QUEUED)
                <x-ui.alert variant="info">
                    {{ __('This import is queued. The counters will update automatically when a queue worker processes it.') }}
                </x-ui.alert>
            @elseif ($session->status === \App\Models\ImportSession::STATUS_IMPORTING)
                <x-ui.alert variant="info">
                    {{ __('Import is processing. This page refreshes automatically every five seconds.') }}
                </x-ui.alert>
            @elseif ($session->last_error)
                <x-ui.alert variant="danger">
                    {{ $session->last_error }}
                </x-ui.alert>
            @endif

            @if (in_array($session->status, [\App\Models\ImportSession::STATUS_QUEUED, \App\Models\ImportSession::STATUS_IMPORTING], true))
                <meta http-equiv="refresh" content="5">
            @endif

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <x-ui.stat-card :label="__('Created')" :value="(string) $session->created_count" class="!border-success/30" />
                <x-ui.stat-card :label="__('Skipped')" :value="(string) $session->skipped_count" />
                <x-ui.stat-card :label="__('Failed')" :value="(string) $session->failed_count" class="!border-danger/30" />
                <x-ui.stat-card :label="__('Duplicate')" :value="(string) $duplicateRows" class="!border-warning/30" />
            </div>

            <x-entity.section :title="__('Import details')">
                <x-entity.definition-list>
                    <x-entity.definition-item :label="__('Status')">{{ ucfirst($session->status) }}</x-entity.definition-item>
                    <x-entity.definition-item :label="__('Processed rows')">{{ $session->processed_rows }}</x-entity.definition-item>
                    <x-entity.definition-item :label="__('Started')">{{ $session->started_at?->toDayDateTimeString() ?? '—' }}</x-entity.definition-item>
                    <x-entity.definition-item :label="__('Completed')">{{ $session->completed_at?->toDayDateTimeString() ?? '—' }}</x-entity.definition-item>
                </x-entity.definition-list>

                @php
                    $execution = $session->validation_summary['execution_summary'] ?? [];
                    $executionErrors = $session->validation_summary['execution_errors'] ?? [];
                @endphp

                @if ($execution !== [])
                    <div class="mt-6">
                        <h3 class="text-sm font-semibold text-ink-heading">{{ __('Diagnostics') }}</h3>
                        <x-entity.definition-list class="mt-3">
                            <x-entity.definition-item :label="__('Valid rows')">{{ $execution['rows_valid'] ?? 0 }}</x-entity.definition-item>
                            <x-entity.definition-item :label="__('Invalid rows')">{{ $execution['rows_invalid'] ?? 0 }}</x-entity.definition-item>
                            <x-entity.definition-item :label="__('Owner errors')">{{ $execution['owner_resolution_errors'] ?? 0 }}</x-entity.definition-item>
                            <x-entity.definition-item :label="__('Database errors')">{{ $execution['database_errors'] ?? 0 }}</x-entity.definition-item>
                            <x-entity.definition-item :label="__('Processing time')">{{ number_format((int) ($execution['processing_time_ms'] ?? 0)) }} ms</x-entity.definition-item>
                            <x-entity.definition-item :label="__('Created IDs')">{{ implode(', ', array_slice($execution['created_ids'] ?? [], 0, 50)) ?: '—' }}</x-entity.definition-item>
                        </x-entity.definition-list>
                    </div>
                @endif

                @if ($executionErrors !== [])
                    <div class="mt-6">
                        <h3 class="text-sm font-semibold text-danger">{{ __('Row failures') }}</h3>
                        <ul class="mt-3 space-y-2 text-sm text-ink">
                            @foreach (array_slice($executionErrors, 0, 20) as $error)
                                <li>
                                    <span class="font-medium">{{ __('Row :row', ['row' => $error['row_number'] ?? '—']) }}:</span>
                                    {{ $error['error'] ?? __('Unknown import error') }}
                                </li>
                            @endforeach
                        </ul>
                        @if (count($executionErrors) > 20)
                            <p class="mt-3 text-xs text-ink-muted">{{ __('Download the error report to see every failure.') }}</p>
                        @endif
                    </div>
                @endif

                <div class="mt-6 flex flex-wrap gap-3">
                    <x-ui.button :href="route('leads.index')" variant="primary">
                        {{ __('View :leads', ['leads' => crm_term('leads')]) }}
                    </x-ui.button>
                    <x-ui.button :href="route('leads.import.create')" variant="secondary">
                        {{ __('Import another file') }}
                    </x-ui.button>
                    @if ($session->failed_count > 0 || $duplicateRows > 0)
                        <x-ui.button :href="route('leads.import.errors', $session)" variant="secondary">
                            {{ __('Download error report') }}
                        </x-ui.button>
                    @endif
                </div>
            </x-entity.section>
        </div>
    </x-layouts.entity-listing>
</x-app-layout>
