@php
    $density = $shellNav['density'] ?? 'comfortable';
    $correctionColumns = [
        __('Status'),
        __('Reason'),
        __('Reviewed By'),
    ];
@endphp

<x-app-layout>
    <x-layouts.entity-detail
        :title="__('Attendance Details')"
        :subtitle="$record->employee?->full_name"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('attendance.label'), 'href' => route('hrms.attendance.index')],
                ['label' => $record->attendance_date?->format('M j, Y') ?? __('Record'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button :href="route('hrms.attendance.index')" variant="secondary" size="sm">{{ __('Back') }}</x-ui.button>
        </x-slot:actions>

        <x-slot:tabs>
            <x-ui.badge variant="neutral">{{ $record->statusLabel() }}</x-ui.badge>
        </x-slot:tabs>

        <x-entity.section :title="__('Attendance record')">
            <x-entity.definition-list>
                <x-entity.definition-item :label="__('Employee')">{{ $record->employee?->full_name ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Date')">{{ $record->attendance_date?->format('Y-m-d') ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Shift')">{{ $record->shift?->name ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Status')">{{ $record->statusLabel() }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Source')">{{ $record->sourceLabel() }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Clock In')">{{ $record->clock_in_at?->format('Y-m-d H:i') ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Clock Out')">{{ $record->clock_out_at?->format('Y-m-d H:i') ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Working Minutes')">{{ $record->working_minutes }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Late Minutes')">{{ $record->late_minutes }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Early Departure')">{{ $record->early_departure_minutes }} min</x-entity.definition-item>
                <x-entity.definition-item :label="__('Overtime')">{{ $record->overtime_minutes }} min</x-entity.definition-item>
            </x-entity.definition-list>
        </x-entity.section>

        @if ($record->corrections->isNotEmpty())
            <x-entity.section :title="__('Corrections')">
                <x-tables.table :columns="$correctionColumns" :dense="$density === 'compact'">
                    @foreach ($record->corrections as $correction)
                        <tr class="hover:bg-surface-muted/60 transition">
                            <td class="px-4 py-3">
                                <x-ui.badge variant="neutral">{{ $correction->statusLabel() }}</x-ui.badge>
                            </td>
                            <td class="px-4 py-3 text-sm text-ink-muted">{{ $correction->reason }}</td>
                            <td class="px-4 py-3 text-sm text-ink-muted">{{ $correction->reviewer?->name ?? '—' }}</td>
                        </tr>
                    @endforeach
                </x-tables.table>
            </x-entity.section>
        @endif
    </x-layouts.entity-detail>
</x-app-layout>
