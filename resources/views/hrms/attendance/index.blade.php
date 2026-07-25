@php
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [
        __('Date'),
        __('Employee'),
        __('Status'),
        __('Clock In'),
        __('Clock Out'),
        __('Working'),
        __('Actions'),
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Attendance')"
        :subtitle="__('Daily attendance records and clock events')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Attendance'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button :href="route('hrms.attendance.summary')" variant="secondary" size="sm">{{ __('Daily Summary') }}</x-ui.button>
            <x-ui.button :href="route('hrms.attendance.corrections.index')" variant="secondary" size="sm">{{ __('Corrections') }}</x-ui.button>
        </x-slot:actions>

        <x-slot:filters>
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <x-forms.field :label="__('Date')" name="date" class="mb-0">
                    <x-forms.input type="date" name="date" :value="$filterDate" />
                </x-forms.field>
                <x-ui.button type="submit" variant="primary" size="sm">{{ __('Filter') }}</x-ui.button>
            </form>
        </x-slot:filters>

        <div class="grid grid-cols-2 gap-3 mb-4 md:grid-cols-5">
            @foreach ([
                'present' => __('Present'),
                'late' => __('Late'),
                'absent' => __('Absent'),
                'pending' => __('Pending'),
                'overtime' => __('Overtime'),
            ] as $key => $label)
                <x-ui.stat-card :label="$label" :value="$summary[$key]" />
            @endforeach
        </div>

        @can('manage', App\Models\AttendanceRecord::class)
            <div class="grid grid-cols-1 gap-4 mb-6 md:grid-cols-2">
                <x-ui.card>
                    <x-entity.section :title="__('Clock In')">
                        <form method="POST" action="{{ route('hrms.attendance.clock-in') }}" class="space-y-3">
                            @csrf
                            <x-forms.field :label="__('Employee')" name="employee_id">
                                <x-forms.select name="employee_id" required>
                                    <option value="">{{ __('Select Employee') }}</option>
                                    @foreach ($employees as $employee)
                                        <option value="{{ $employee->id }}">{{ $employee->full_name }}</option>
                                    @endforeach
                                </x-forms.select>
                            </x-forms.field>
                            <x-forms.field :label="__('Clock in at')" name="clock_in_at">
                                <x-forms.input type="datetime-local" name="clock_in_at" />
                            </x-forms.field>
                            <x-ui.button type="submit" variant="primary" size="sm">{{ __('Clock In') }}</x-ui.button>
                        </form>
                    </x-entity.section>
                </x-ui.card>
                <x-ui.card>
                    <x-entity.section :title="__('Clock Out')">
                        <form method="POST" action="{{ route('hrms.attendance.clock-out') }}" class="space-y-3">
                            @csrf
                            <x-forms.field :label="__('Employee')" name="employee_id">
                                <x-forms.select name="employee_id" required>
                                    <option value="">{{ __('Select Employee') }}</option>
                                    @foreach ($employees as $employee)
                                        <option value="{{ $employee->id }}">{{ $employee->full_name }}</option>
                                    @endforeach
                                </x-forms.select>
                            </x-forms.field>
                            <x-forms.field :label="__('Clock out at')" name="clock_out_at">
                                <x-forms.input type="datetime-local" name="clock_out_at" />
                            </x-forms.field>
                            <x-ui.button type="submit" variant="primary" size="sm">{{ __('Clock Out') }}</x-ui.button>
                        </form>
                    </x-entity.section>
                </x-ui.card>
            </div>
        @endcan

        @if ($records->isEmpty())
            <x-ui.card>
                <x-ui.empty-state-preset variant="attendance" />
            </x-ui.card>
        @else
            <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
                @foreach ($records as $record)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $record->attendance_date?->format('Y-m-d') }}</td>
                        <td class="px-4 py-3 text-sm text-ink-heading">{{ $record->employee?->full_name }}</td>
                        <td class="px-4 py-3">
                            <x-ui.badge variant="neutral">{{ $record->statusLabel() }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $record->clock_in_at?->format('H:i') ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $record->clock_out_at?->format('H:i') ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $record->working_minutes }} min</td>
                        <td class="px-4 py-3">
                            <x-ui.button :href="route('hrms.attendance.show', $record)" variant="ghost" size="sm">{{ __('View') }}</x-ui.button>
                        </td>
                    </tr>
                @endforeach
            </x-tables.table>
            <div class="mt-4">{{ $records->links() }}</div>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
