@php
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [
        __('Employee'),
        __('Dates'),
        __('Status'),
        __('Submitted'),
        __('Reason'),
        __('Actions'),
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('WFH Requests')"
        :subtitle="__('Daily and multi-day work-from-home requests and approvals')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('WFH Requests'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="mb-4 flex flex-wrap gap-2">
            <a href="{{ route('hrms.wfh.requests.index') }}" class="rounded-md px-3 py-1.5 text-sm {{ $filterStatus === '' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700' }}">{{ __('All') }}</a>
            @foreach ($statuses as $value => $label)
                <a href="{{ route('hrms.wfh.requests.index', ['status' => $value]) }}" class="rounded-md px-3 py-1.5 text-sm {{ $filterStatus === $value ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700' }}">{{ __($label) }}</a>
            @endforeach
            @can('viewAny', \App\Models\WfhRequest::class)
                <a href="{{ route('hrms.wfh.requests.approval-queue') }}" class="rounded-md bg-indigo-50 px-3 py-1.5 text-sm text-indigo-700">{{ __('Approval queue') }}</a>
            @endcan
        </div>

        @can('create', \App\Models\WfhRequest::class)
            <x-ui.card class="mb-6">
                <x-entity.section :title="__('Create WFH request')">
                    <form method="POST" action="{{ route('hrms.wfh.requests.store') }}" class="grid grid-cols-1 gap-3 md:grid-cols-5">
                        @csrf
                        <x-forms.field :label="__('Employee')" name="employee_id">
                            <x-forms.select name="employee_id" required>
                                <option value="">{{ __('Select employee') }}</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->full_name ?? trim($employee->first_name.' '.$employee->last_name) }}</option>
                                @endforeach
                            </x-forms.select>
                        </x-forms.field>
                        <x-forms.field :label="__('Start date')" name="start_date">
                            <x-forms.input name="start_date" type="date" required />
                        </x-forms.field>
                        <x-forms.field :label="__('End date')" name="end_date">
                            <x-forms.input name="end_date" type="date" />
                        </x-forms.field>
                        <x-forms.field :label="__('Reason')" name="reason">
                            <x-forms.input name="reason" />
                        </x-forms.field>
                        <div class="flex items-end">
                            <x-ui.button type="submit" variant="primary" size="sm">{{ __('Submit') }}</x-ui.button>
                        </div>
                    </form>
                </x-entity.section>
            </x-ui.card>
        @endcan

        <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
            @forelse ($requests as $wfhRequest)
                <tr class="hover:bg-surface-muted/60 transition">
                    <td class="px-4 py-3 text-sm font-medium text-ink-heading">
                        {{ $wfhRequest->employee?->full_name ?? trim(($wfhRequest->employee?->first_name ?? '').' '.($wfhRequest->employee?->last_name ?? '')) }}
                    </td>
                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $wfhRequest->dateLabel() }}</td>
                    <td class="px-4 py-3 text-sm text-ink-muted">{{ __(config('hrms.wfh_request_statuses.'.$wfhRequest->status, $wfhRequest->status)) }}</td>
                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $wfhRequest->submitted_at?->format('M j, Y g:i A') ?? '—' }}</td>
                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $wfhRequest->reason ?: '—' }}</td>
                    <td class="px-4 py-3 text-sm">
                        <a href="{{ route('hrms.wfh.requests.show', $wfhRequest) }}" class="text-indigo-600 hover:underline">{{ __('View') }}</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}" class="px-4 py-8 text-center text-sm text-ink-muted">{{ __('No WFH requests yet.') }}</td>
                </tr>
            @endforelse
        </x-tables.table>

        <div class="mt-4">{{ $requests->links() }}</div>
    </x-layouts.entity-listing>
</x-app-layout>
