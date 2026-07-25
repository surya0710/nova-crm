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

    <x-layouts.workspace-home
        :title="__('Leave Dashboard')"
        :subtitle="__('Leave operations overview and recent activity')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Leave Dashboard'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:kpis>
            <x-ui.stat-card :label="__('Pending Approvals')" :value="$stats['pending_approvals']" />
            <x-ui.stat-card :label="__('Approved This Month')" :value="$stats['approved_this_month']" />
            <x-ui.stat-card :label="__('On Leave Today')" :value="$stats['on_leave_today']" />
            <x-ui.stat-card :label="__('Active Leave Types')" :value="$stats['leave_types']" />
        </x-slot:kpis>

        <x-workspace.widget :title="__('Recent Applications')">
            @if ($recentApplications->isEmpty())
                <x-ui.empty-state-preset variant="leave" class="!py-6" />
            @else
                <x-tables.table :columns="$columns" :dense="$density === 'compact'">
                    @foreach ($recentApplications as $application)
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
                                <x-ui.badge variant="neutral">{{ config('hrms.leave_statuses.'.$application->status, $application->status) }}</x-ui.badge>
                            </td>
                        </tr>
                    @endforeach
                </x-tables.table>
            @endif
        </x-workspace.widget>
    </x-layouts.workspace-home>
</x-app-layout>
