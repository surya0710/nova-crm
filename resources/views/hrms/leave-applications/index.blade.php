@php
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [
        __('Employee'),
        __('Type'),
        __('Dates'),
        __('Days'),
        __('Status'),
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Leave Applications')"
        :subtitle="__('Track and manage employee leave requests')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Leave Applications'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @can('create', \App\Models\LeaveApplication::class)
            <x-ui.card class="mb-6">
                <x-entity.section :title="__('Apply leave')">
                    <form method="POST" action="{{ route('hrms.leave-applications.store') }}" class="grid grid-cols-1 gap-3 md:grid-cols-4">
                        @csrf
                        <x-forms.field :label="__('Employee')" name="employee_id">
                            <x-forms.select name="employee_id" required>
                                <option value="">{{ __('Employee') }}</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }}</option>
                                @endforeach
                            </x-forms.select>
                        </x-forms.field>
                        <x-forms.field :label="__('Leave Type')" name="leave_type_id">
                            <x-forms.select name="leave_type_id" required>
                                <option value="">{{ __('Leave Type') }}</option>
                                @foreach ($leaveTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </x-forms.select>
                        </x-forms.field>
                        <x-forms.field :label="__('Start date')" name="start_date">
                            <x-forms.input name="start_date" type="date" required />
                        </x-forms.field>
                        <x-forms.field :label="__('End date')" name="end_date">
                            <x-forms.input name="end_date" type="date" required />
                        </x-forms.field>
                        <x-forms.checkbox name="is_half_day" value="1" :label="__('Half Day')" />
                        <x-forms.field :label="__('Half Day Period')" name="half_day_period">
                            <x-forms.select name="half_day_period">
                                <option value="">{{ __('Half Day Period') }}</option>
                                @foreach (config('hrms.half_day_periods', []) as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </x-forms.select>
                        </x-forms.field>
                        <x-forms.field :label="__('Reason')" name="reason" class="md:col-span-2">
                            <x-forms.input name="reason" placeholder="{{ __('Reason') }}" />
                        </x-forms.field>
                        <div class="md:col-span-4">
                            <x-ui.button type="submit" variant="primary" size="sm">{{ __('Apply Leave') }}</x-ui.button>
                        </div>
                    </form>
                </x-entity.section>
            </x-ui.card>
        @endcan

        <x-slot:filters>
            <div class="flex flex-wrap gap-3 text-sm">
                <a href="{{ route('hrms.leave-applications.index') }}" class="{{ $filterStatus === '' ? 'font-semibold text-primary-700' : 'text-ink-muted hover:text-ink-heading' }}">{{ __('All') }}</a>
                @foreach ($statuses as $key => $label)
                    <a href="{{ route('hrms.leave-applications.index', ['status' => $key]) }}" class="{{ $filterStatus === $key ? 'font-semibold text-primary-700' : 'text-ink-muted hover:text-ink-heading' }}">{{ $label }}</a>
                @endforeach
            </div>
        </x-slot:filters>

        @if ($applications->isEmpty())
            <x-ui.card>
                <x-ui.empty-state-preset variant="leave" />
            </x-ui.card>
        @else
            <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
                @foreach ($applications as $application)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3">
                            <a href="{{ route('hrms.leave-applications.show', $application) }}" class="text-sm font-medium text-primary-700 hover:text-primary-800">
                                {{ $application->employee->first_name }} {{ $application->employee->last_name }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $application->leaveType->name }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $application->start_date->format('M j') }} – {{ $application->end_date->format('M j, Y') }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $application->days }}</td>
                        <td class="px-4 py-3">
                            <x-ui.badge variant="neutral">{{ $statuses[$application->status] ?? $application->status }}</x-ui.badge>
                        </td>
                    </tr>
                @endforeach
            </x-tables.table>
            <div class="mt-4">{{ $applications->links() }}</div>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
