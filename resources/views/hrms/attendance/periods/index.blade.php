@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <x-ui.page-header :title="__('Attendance Periods')">
            <x-slot:actions>
                @can('lock', App\Models\AttendancePeriod::class)
                    <x-ui.button :href="route('hrms.attendance.periods.create')" size="sm">{{ __('Create period') }}</x-ui.button>
                @endcan
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.card>
            <form method="GET" action="{{ route('hrms.attendance.periods.index') }}" class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-3">
                <x-forms.field :label="__('Status')" name="status">
                    <x-forms.select name="status">
                        <option value="">{{ __('All') }}</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <div class="flex items-end">
                    <x-ui.button type="submit" size="sm">{{ __('Filter') }}</x-ui.button>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-edge text-left text-ink-muted">
                            <th class="px-3 py-2">{{ __('Name') }}</th>
                            <th class="px-3 py-2">{{ __('Range') }}</th>
                            <th class="px-3 py-2">{{ __('Status') }}</th>
                            <th class="px-3 py-2">{{ __('Payroll Period') }}</th>
                            <th class="px-3 py-2">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($periods as $period)
                            <tr class="border-b border-edge/60">
                                <td class="px-3 py-2">{{ $period->name }}</td>
                                <td class="px-3 py-2">{{ $period->start_date->toDateString() }} → {{ $period->end_date->toDateString() }}</td>
                                <td class="px-3 py-2">{{ $period->statusLabel() }}</td>
                                <td class="px-3 py-2">{{ $period->payrollPeriod?->name ?? '—' }}</td>
                                <td class="px-3 py-2">
                                    <x-ui.button :href="route('hrms.attendance.periods.show', $period)" variant="secondary" size="sm">{{ __('Open') }}</x-ui.button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-6 text-ink-muted">{{ __('No attendance periods yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $periods->links() }}</div>
        </x-ui.card>
    </div>
@endsection
