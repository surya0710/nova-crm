@php
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [
        __('Employee'),
        __('Date'),
        __('Status'),
        __('Reason'),
        __('Actions'),
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Attendance Corrections')"
        :subtitle="__('Review and submit attendance correction requests')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('attendance.label'), 'href' => route('hrms.attendance.index')],
                ['label' => __('Corrections'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button :href="route('hrms.attendance.index')" variant="secondary" size="sm">{{ __('Back') }}</x-ui.button>
        </x-slot:actions>

        @can('create', App\Models\AttendanceCorrection::class)
            <x-ui.card class="mb-6">
                <x-entity.section :title="__('Submit correction')">
                    <form method="POST" action="{{ route('hrms.attendance.corrections.store') }}" class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        @csrf
                        <x-forms.field :label="__('Attendance Record')" name="attendance_record_id">
                            <x-forms.select name="attendance_record_id" required>
                                <option value="">{{ __('Attendance Record') }}</option>
                                @foreach ($records as $record)
                                    <option value="{{ $record->id }}">{{ $record->employee?->full_name }} — {{ $record->attendance_date?->format('Y-m-d') }}</option>
                                @endforeach
                            </x-forms.select>
                        </x-forms.field>
                        <x-forms.field :label="__('Reason')" name="reason">
                            <x-forms.textarea name="reason" rows="2" placeholder="{{ __('Reason') }}" required />
                        </x-forms.field>
                        <x-forms.field :label="__('Requested clock in')" name="requested_clock_in_at">
                            <x-forms.input type="datetime-local" name="requested_clock_in_at" />
                        </x-forms.field>
                        <x-forms.field :label="__('Requested clock out')" name="requested_clock_out_at">
                            <x-forms.input type="datetime-local" name="requested_clock_out_at" />
                        </x-forms.field>
                        <div class="md:col-span-2">
                            <x-ui.button type="submit" variant="primary" size="sm">{{ __('Submit Correction') }}</x-ui.button>
                        </div>
                    </form>
                </x-entity.section>
            </x-ui.card>
        @endcan

        @if ($corrections->isEmpty())
            <x-ui.card>
                <x-ui.empty-state-preset variant="attendance" />
            </x-ui.card>
        @else
            <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
                @foreach ($corrections as $correction)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3 text-sm text-ink-heading">{{ $correction->employee?->full_name }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $correction->attendanceRecord?->attendance_date?->format('Y-m-d') }}</td>
                        <td class="px-4 py-3">
                            <x-ui.badge variant="neutral">{{ $correction->statusLabel() }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ Str::limit($correction->reason, 60) }}</td>
                        <td class="px-4 py-3">
                            @can('review', $correction)
                                @if ($correction->status === 'pending')
                                    <div class="flex flex-wrap gap-2">
                                        <form method="POST" action="{{ route('hrms.attendance.corrections.approve', $correction) }}" class="inline">
                                            @csrf
                                            <x-ui.button type="submit" variant="primary" size="sm">{{ __('Approve') }}</x-ui.button>
                                        </form>
                                        <form method="POST" action="{{ route('hrms.attendance.corrections.reject', $correction) }}" class="inline">
                                            @csrf
                                            <x-ui.button type="submit" variant="danger" size="sm">{{ __('Reject') }}</x-ui.button>
                                        </form>
                                    </div>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </x-tables.table>
            <div class="mt-4">{{ $corrections->links() }}</div>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
