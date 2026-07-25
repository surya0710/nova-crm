@php
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [
        __('Date'),
        __('Clock In'),
        __('Clock Out'),
        __('Status'),
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('My Attendance')"
        :subtitle="__('Clock in/out and view your attendance history')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('My HR'), 'href' => route('ess.dashboard')],
                ['label' => __('Attendance'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @include('ess.partials.nav')

        <x-ui.card class="mb-6">
            <div class="flex flex-wrap gap-3">
                @if (!$todayRecord || !$todayRecord->clock_in_at)
                    <form method="POST" action="{{ route('ess.attendance.clock-in') }}">
                        @csrf
                        <x-ui.button type="submit" variant="primary" size="sm">{{ __('Clock In') }}</x-ui.button>
                    </form>
                @endif
                @if ($todayRecord && $todayRecord->clock_in_at && !$todayRecord->clock_out_at)
                    <form method="POST" action="{{ route('ess.attendance.clock-out') }}">
                        @csrf
                        <x-ui.button type="submit" variant="primary" size="sm">{{ __('Clock Out') }}</x-ui.button>
                    </form>
                @endif
            </div>
        </x-ui.card>

        <x-entity.section :title="__('Attendance History')">
            @if ($records->isEmpty())
                <x-ui.empty-state-preset variant="attendance" />
            @else
                <x-tables.table :columns="$columns" :dense="$density === 'compact'">
                    @foreach ($records as $record)
                        <tr class="hover:bg-surface-muted/60 transition">
                            <td class="px-4 py-3 text-sm text-ink-muted">{{ $record->attendance_date->format('M j, Y') }}</td>
                            <td class="px-4 py-3 text-sm text-ink-muted">{{ $record->clock_in_at?->format('H:i') ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-ink-muted">{{ $record->clock_out_at?->format('H:i') ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <x-ui.badge variant="neutral">{{ $record->status }}</x-ui.badge>
                            </td>
                        </tr>
                    @endforeach
                </x-tables.table>
                <div class="mt-4">{{ $records->links() }}</div>
            @endif
        </x-entity.section>

        <x-entity.section :title="__('Correction Requests')" class="mt-6">
            @forelse ($corrections as $correction)
                <div class="text-sm py-2 border-b border-line last:border-0 text-ink-heading">
                    {{ $correction->attendanceRecord->attendance_date->format('M j, Y') }} · {{ $correction->status }}
                </div>
            @empty
                <p class="text-sm text-ink-muted">{{ __('No correction requests.') }}</p>
            @endforelse
        </x-entity.section>
    </x-layouts.entity-listing>
</x-app-layout>
