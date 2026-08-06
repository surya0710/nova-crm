<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="__('Employment history')"
        :subtitle="$employee->full_name"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Employees'), 'href' => route('hrms.employees.index')],
                ['label' => $employee->full_name, 'href' => route('hrms.employees.show', $employee)],
                ['label' => __('Timeline'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button :href="route('hrms.employees.show', $employee)" variant="secondary" size="sm">{{ __('Back to Employee') }}</x-ui.button>
        </x-slot:actions>

        <x-entity.section :title="__('Timeline')" :subtitle="__('Employment history and key events')">
            <x-activity.timeline
                :empty="collect($timeline ?? [])->isEmpty()"
                :empty-title="__('No timeline events yet')"
                :empty-description="__('Employment history events will appear here.')"
            >
                @foreach ($timeline ?? [] as $event)
                    <x-activity.timeline-item
                        :actor="$event['label'] ?? __('Event')"
                        :timestamp="isset($event['date']) ? \Carbon\Carbon::parse($event['date']) : null"
                    >
                        {{ $event['description'] ?? '' }}
                    </x-activity.timeline-item>
                @endforeach
            </x-activity.timeline>
        </x-entity.section>
    </x-layouts.entity-detail>
</x-app-layout>
