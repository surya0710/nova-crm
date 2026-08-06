@php
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [__('Employee'), __('Period'), __('Gross'), __('Net'), __('Payable Days')];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Payroll Results')" :subtitle="__('View calculated payroll results')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Payroll'), 'href' => route('hrms.payroll.index')],
                ['label' => __('Results'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @if ($results->isEmpty())
            <x-ui.card><x-ui.empty-state-preset variant="payroll" /></x-ui.card>
        @else
            <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
                @foreach ($results as $result)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3">
                            <a href="{{ route('hrms.payroll.results.show', $result) }}" class="text-sm font-medium text-primary-700 hover:text-primary-800">{{ $result->employee?->full_name }}</a>
                        </td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $result->payrollRun?->period?->name }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ number_format((float) $result->gross_salary, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ number_format((float) $result->net_salary, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $result->payable_days }}</td>
                    </tr>
                @endforeach
            </x-tables.table>
        @endif

        @if ($results->hasPages())
            <x-slot:pagination>{{ $results->links() }}</x-slot:pagination>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
