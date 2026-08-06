@php
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [__('Number'), __('Date'), __('Run'), __('Debit'), __('Credit'), __('Status')];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Payroll Journals')" :subtitle="__('Browse payroll journal entries')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Payroll'), 'href' => route('hrms.payroll.index')],
                ['label' => __('Journals'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:filters>
            <form method="GET" class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <x-forms.field :label="__('Payroll run ID')" name="payroll_run_id">
                    <x-forms.input type="number" name="payroll_run_id" :value="$filters['payroll_run_id'] ?? ''" min="1" />
                </x-forms.field>
                <div class="flex items-end">
                    <x-ui.button type="submit" variant="primary" size="sm">{{ __('Filter') }}</x-ui.button>
                </div>
            </form>
        </x-slot:filters>

        @if ($journals->isEmpty())
            <x-ui.card><x-ui.empty-state-preset variant="payroll" /></x-ui.card>
        @else
            <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
                @foreach ($journals as $journal)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3">
                            <a href="{{ route('hrms.payroll.journals.show', $journal) }}" class="text-sm font-medium text-primary-700 hover:text-primary-800">{{ $journal->journal_number }}</a>
                        </td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $journal->journal_date?->toDateString() }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $journal->payrollRun?->period?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ number_format((float) $journal->total_debit, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ number_format((float) $journal->total_credit, 2) }}</td>
                        <td class="px-4 py-3">
                            <x-ui.badge variant="neutral">{{ $journal->status }}@if ($journal->is_reversal) ({{ __('reversal') }})@endif</x-ui.badge>
                        </td>
                    </tr>
                @endforeach
            </x-tables.table>
        @endif

        @if ($journals->hasPages())
            <x-slot:pagination>{{ $journals->links() }}</x-slot:pagination>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
