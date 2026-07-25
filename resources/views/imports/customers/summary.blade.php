<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Import Summary')"
        :subtitle="$session->original_filename"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => __('Imports'), 'href' => route('customers.import.create')],
                ['label' => crm_term('customers'), 'href' => route('customers.import.create')],
                ['label' => __('Summary'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button :href="route('customers.index')" variant="secondary" size="sm">
                {{ __('Back to :customers', ['customers' => crm_term('customers')]) }}
            </x-ui.button>
        </x-slot:actions>

        <div class="mx-auto max-w-3xl space-y-6">
            @include('imports._steps', ['current' => 'summary'])

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

                <div class="mt-6 flex flex-wrap gap-3">
                    <x-ui.button :href="route('customers.index')" variant="primary">
                        {{ __('View :customers', ['customers' => crm_term('customers')]) }}
                    </x-ui.button>
                    <x-ui.button :href="route('customers.import.create')" variant="secondary">
                        {{ __('Import another file') }}
                    </x-ui.button>
                    @if ($session->failed_count > 0 || $duplicateRows > 0)
                        <x-ui.button :href="route('customers.import.errors', $session)" variant="secondary">
                            {{ __('Download error report') }}
                        </x-ui.button>
                    @endif
                </div>
            </x-entity.section>
        </div>
    </x-layouts.entity-listing>
</x-app-layout>
