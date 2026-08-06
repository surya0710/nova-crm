@php
    $density = $shellNav['density'] ?? 'comfortable';
    $historyColumns = [
        __('Step'),
        __('Approver'),
        __('Status'),
        __('Remarks'),
        __('Acted At'),
    ];
    $employeeName = $application->employee->first_name.' '.$application->employee->last_name;
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="__('Leave Application')"
        :subtitle="$employeeName"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Leave Applications'), 'href' => route('hrms.leave-applications.index')],
                ['label' => $employeeName, 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:tabs>
            <x-ui.badge variant="neutral">{{ $statuses[$application->status] ?? $application->status }}</x-ui.badge>
        </x-slot:tabs>

        <x-entity.section :title="__('Application details')">
            <x-entity.definition-list>
                <x-entity.definition-item :label="__('Employee')">{{ $employeeName }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Leave Type')">{{ $application->leaveType->name }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Dates')">{{ $application->start_date->format('M j, Y') }} – {{ $application->end_date->format('M j, Y') }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Days')">
                    {{ $application->days }}
                    @if ($application->is_half_day)
                        ({{ config('hrms.half_day_periods.'.$application->half_day_period) }})
                    @endif
                </x-entity.definition-item>
                <x-entity.definition-item :label="__('Status')">{{ $statuses[$application->status] ?? $application->status }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Reason')" :span="2">{{ $application->reason ?? '—' }}</x-entity.definition-item>
            </x-entity.definition-list>
        </x-entity.section>

        <x-entity.section :title="__('Approval History')">
            <x-tables.table :columns="$historyColumns" :dense="$density === 'compact'">
                @foreach ($application->approvalSteps as $step)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $step->step_order }}</td>
                        <td class="px-4 py-3 text-sm text-ink-heading">{{ $step->approverEmployee?->first_name ?? ($step->approver_user_id ? __('HR') : '—') }}</td>
                        <td class="px-4 py-3">
                            <x-ui.badge variant="neutral">{{ $stepStatuses[$step->status] ?? $step->status }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $step->comments ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $step->acted_at?->format('M j, Y H:i') ?? '—' }}</td>
                    </tr>
                @endforeach
            </x-tables.table>
        </x-entity.section>

        <x-slot:aside>
            @can('approve', $application)
                @if ($application->status === 'pending')
                    <x-ui.card>
                        <x-entity.section :title="__('Approve')">
                            <form method="POST" action="{{ route('hrms.leave-applications.approve', $application) }}" class="space-y-3">
                                @csrf
                                <x-forms.field :label="__('Remarks')" name="remarks">
                                    <x-forms.textarea name="remarks" rows="2" placeholder="{{ __('Remarks') }}" />
                                </x-forms.field>
                                <x-ui.button type="submit" variant="primary" size="sm" class="w-full">{{ __('Approve') }}</x-ui.button>
                            </form>
                        </x-entity.section>
                    </x-ui.card>
                    <x-ui.card>
                        <x-entity.section :title="__('Reject')">
                            <form method="POST" action="{{ route('hrms.leave-applications.reject', $application) }}" class="space-y-3">
                                @csrf
                                <x-forms.field :label="__('Rejection remarks')" name="remarks">
                                    <x-forms.textarea name="remarks" rows="2" placeholder="{{ __('Rejection remarks') }}" />
                                </x-forms.field>
                                <x-ui.button type="submit" variant="danger" size="sm" class="w-full">{{ __('Reject') }}</x-ui.button>
                            </form>
                        </x-entity.section>
                    </x-ui.card>
                @endif
            @endcan

            @can('cancel', $application)
                @if ($application->status === 'approved')
                    <x-ui.card>
                        <x-entity.section :title="__('Cancel leave')">
                            <form method="POST" action="{{ route('hrms.leave-applications.cancel', $application) }}" class="space-y-3">
                                @csrf
                                <x-forms.field :label="__('Cancellation remarks')" name="remarks">
                                    <x-forms.textarea name="remarks" rows="2" placeholder="{{ __('Cancellation remarks') }}" />
                                </x-forms.field>
                                <x-ui.button type="submit" variant="secondary" size="sm" class="w-full">{{ __('Cancel Leave') }}</x-ui.button>
                            </form>
                        </x-entity.section>
                    </x-ui.card>
                @endif
            @endcan

            @can('delete', $application)
                @if (in_array($application->status, ['draft', 'pending']))
                    <form method="POST" action="{{ route('hrms.leave-applications.destroy', $application) }}">
                        @csrf
                        @method('DELETE')
                        <x-ui.button type="submit" variant="ghost" size="sm" class="w-full">{{ __('Withdraw') }}</x-ui.button>
                    </form>
                @endif
            @endcan
        </x-slot:aside>
    </x-layouts.entity-detail>
</x-app-layout>
