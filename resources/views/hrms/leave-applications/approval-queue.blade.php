@php
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [
        __('Employee'),
        __('Type'),
        __('Dates'),
        __('Days'),
        __('Submitted'),
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Approval Queue')"
        :subtitle="__('Leave applications awaiting your approval')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Leave Applications'), 'href' => route('hrms.leave-applications.index')],
                ['label' => __('Approval Queue'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

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
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $application->submitted_at?->format('M j, Y') ?? '—' }}</td>
                    </tr>
                @endforeach
            </x-tables.table>
            <div class="mt-4">{{ $applications->links() }}</div>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
