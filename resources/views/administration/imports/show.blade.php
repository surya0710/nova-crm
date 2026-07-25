<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Import Status')"
        :subtitle="$session->original_filename"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Administration'), 'href' => route('administration.home')],
                ['label' => __('Import Center'), 'href' => route('administration.imports.index')],
                ['label' => __('History'), 'href' => route('administration.imports.history')],
                ['label' => __('Status'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="mx-auto max-w-3xl space-y-6">
            @include('imports._steps', ['current' => 'summary'])

            @if (session('status') === 'import-started' && $session->status === \App\Models\ImportSession::STATUS_READY)
                <x-ui.alert variant="info">
                    {{ __('Large import queued for background processing. Refresh this page to track progress.') }}
                </x-ui.alert>
            @endif

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
                <x-ui.stat-card :label="__('Created')" :value="(string) $session->created_count" class="!border-success/30" />
                <x-ui.stat-card :label="__('Updated')" :value="(string) $session->updated_count" />
                <x-ui.stat-card :label="__('Skipped')" :value="(string) $session->skipped_count" />
                <x-ui.stat-card :label="__('Failed')" :value="(string) $session->failed_count" class="!border-danger/30" />
                <x-ui.stat-card :label="__('Processed')" :value="(string) $session->processed_rows.'/'.(string) $session->total_rows" />
            </div>

            <x-entity.section :title="__('Import details')">
                <x-entity.definition-list>
                    <x-entity.definition-item :label="__('Entity')">{{ $entityLabel }}</x-entity.definition-item>
                    <x-entity.definition-item :label="__('Status')">{{ ucfirst($session->status) }}</x-entity.definition-item>
                    <x-entity.definition-item :label="__('Imported by')">{{ $session->uploader?->name ?? '—' }}</x-entity.definition-item>
                    <x-entity.definition-item :label="__('Started')">{{ $session->started_at?->toDayDateTimeString() ?? '—' }}</x-entity.definition-item>
                    <x-entity.definition-item :label="__('Completed')">{{ $session->completed_at?->toDayDateTimeString() ?? '—' }}</x-entity.definition-item>
                    @if ($session->last_error)
                        <x-entity.definition-item :label="__('Last error')">{{ $session->last_error }}</x-entity.definition-item>
                    @endif
                </x-entity.definition-list>

                <div class="mt-6 flex flex-wrap gap-3">
                    <x-ui.button :href="route('administration.imports.history')" variant="primary">
                        {{ __('View history') }}
                    </x-ui.button>
                    <x-ui.button :href="route('administration.imports.create', $session->entity_type)" variant="secondary">
                        {{ __('Import another file') }}
                    </x-ui.button>
                    @if ($session->failed_count > 0)
                        <x-ui.button :href="route('administration.imports.errors', $session)" variant="secondary">
                            {{ __('Download error report') }}
                        </x-ui.button>
                    @endif
                    @if (in_array($session->status, [\App\Models\ImportSession::STATUS_IMPORTING, \App\Models\ImportSession::STATUS_READY], true))
                        <x-ui.button :href="route('administration.imports.show', $session)" variant="secondary">
                            {{ __('Refresh') }}
                        </x-ui.button>
                    @endif
                </div>
            </x-entity.section>
        </div>
    </x-layouts.entity-listing>
</x-app-layout>
