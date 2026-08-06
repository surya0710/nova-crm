<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Bulk Operation')"
        :subtitle="$actionLabel"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Administration'), 'href' => route('administration.home')],
                ['label' => __('Bulk Operations'), 'href' => route('administration.bulk.index')],
                ['label' => __('Status'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="mx-auto max-w-3xl space-y-6">
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
                <x-ui.stat-card :label="__('Total')" :value="(string) $operation->total_count" />
                <x-ui.stat-card :label="__('Success')" :value="(string) $operation->success_count" class="!border-success/30" />
                <x-ui.stat-card :label="__('Failed')" :value="(string) $operation->failed_count" class="!border-danger/30" />
                <x-ui.stat-card :label="__('Skipped')" :value="(string) $operation->skipped_count" />
                <x-ui.stat-card :label="__('Progress')" :value="$operation->progressPercent().'%'" />
            </div>

            <x-entity.section :title="__('Details')">
                <x-entity.definition-list>
                    <x-entity.definition-item :label="__('Status')">{{ ucfirst($operation->status) }}</x-entity.definition-item>
                    <x-entity.definition-item :label="__('Entity')">{{ $operation->entity_type }}</x-entity.definition-item>
                    <x-entity.definition-item :label="__('Initiated by')">{{ $operation->initiator?->name ?? '—' }}</x-entity.definition-item>
                    <x-entity.definition-item :label="__('Started')">{{ $operation->started_at?->toDayDateTimeString() ?? '—' }}</x-entity.definition-item>
                    <x-entity.definition-item :label="__('Completed')">{{ $operation->completed_at?->toDayDateTimeString() ?? '—' }}</x-entity.definition-item>
                    <x-entity.definition-item :label="__('Duration')">{{ $operation->durationSeconds() !== null ? $operation->durationSeconds().'s' : '—' }}</x-entity.definition-item>
                    @if ($operation->last_error)
                        <x-entity.definition-item :label="__('Error')">{{ $operation->last_error }}</x-entity.definition-item>
                    @endif
                </x-entity.definition-list>

                <div class="mt-6 flex flex-wrap gap-3">
                    <x-ui.button :href="route('administration.bulk.history')" variant="primary">{{ __('History') }}</x-ui.button>
                    @if ($operation->failed_count > 0)
                        <x-ui.button :href="route('administration.bulk.errors', $operation)" variant="secondary">{{ __('Download failures') }}</x-ui.button>
                    @endif
                    @if (! $operation->isTerminal())
                        <x-ui.button :href="route('administration.bulk.show', $operation)" variant="secondary">{{ __('Refresh') }}</x-ui.button>
                    @endif
                </div>
            </x-entity.section>
        </div>
    </x-layouts.entity-listing>
</x-app-layout>
