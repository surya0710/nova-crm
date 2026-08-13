@php
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [__('Employee'), __('Dates'), __('Submitted'), __('Actions')];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('WFH Approval Queue')"
        :subtitle="__('Pending work-from-home requests')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('WFH Requests'), 'href' => route('hrms.wfh.requests.index')],
                ['label' => __('Approval Queue'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
            @forelse ($requests as $wfhRequest)
                <tr class="hover:bg-surface-muted/60 transition">
                    <td class="px-4 py-3 text-sm font-medium text-ink-heading">
                        {{ $wfhRequest->employee?->full_name ?? trim(($wfhRequest->employee?->first_name ?? '').' '.($wfhRequest->employee?->last_name ?? '')) }}
                    </td>
                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $wfhRequest->dateLabel() }}</td>
                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $wfhRequest->submitted_at?->format('M j, Y g:i A') }}</td>
                    <td class="px-4 py-3 text-sm">
                        <a href="{{ route('hrms.wfh.requests.show', $wfhRequest) }}" class="text-indigo-600 hover:underline">{{ __('Review') }}</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}" class="px-4 py-8 text-center text-sm text-ink-muted">{{ __('No pending WFH requests.') }}</td>
                </tr>
            @endforelse
        </x-tables.table>

        <div class="mt-4">{{ $requests->links() }}</div>
    </x-layouts.entity-listing>
</x-app-layout>
