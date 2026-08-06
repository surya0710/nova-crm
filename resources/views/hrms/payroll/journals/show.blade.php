@php
    $density = $shellNav['density'] ?? 'comfortable';
    $lineColumns = [__('Account'), __('Type'), __('Amount'), __('Employee'), __('Description')];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="__('Journal')"
        :subtitle="$journal->journal_number"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Payroll'), 'href' => route('hrms.payroll.index')],
                ['label' => __('Journals'), 'href' => route('hrms.payroll.journals.index')],
                ['label' => $journal->journal_number, 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button :href="route('hrms.payroll.journals.index')" variant="secondary" size="sm">{{ __('Back') }}</x-ui.button>
        </x-slot:actions>

        <x-slot:tabs>
            <x-ui.badge variant="neutral">{{ $journal->status }}@if ($journal->is_reversal) ({{ __('reversal') }})@endif</x-ui.badge>
        </x-slot:tabs>

        <x-entity.section :title="__('Journal Details')">
            <x-entity.definition-list>
                <x-entity.definition-item :label="__('Date')">{{ $journal->journal_date?->toDateString() }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Run')">{{ $journal->payrollRun?->period?->name ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Status')">{{ $journal->status }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Created by')">{{ $journal->createdBy?->name ?? '—' }}</x-entity.definition-item>
            </x-entity.definition-list>
            <p class="mt-3 text-sm text-ink-muted">{{ $journal->description }}</p>
            @if ($journal->reversesJournal)
                <p class="mt-2 text-sm text-ink-muted">{{ __('Reverses') }}: {{ $journal->reversesJournal->journal_number }}</p>
            @endif
        </x-entity.section>

        <x-entity.section :title="__('Journal Lines')">
            <x-tables.table :columns="$lineColumns" :dense="$density === 'compact'">
                @foreach ($journal->lines as $line)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3 text-sm"><span class="font-mono text-xs">{{ $line->account_code }}</span> {{ $line->account_name }}</td>
                        <td class="px-4 py-3 text-sm capitalize text-ink-muted">{{ $line->entry_type }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ number_format((float) $line->amount, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $line->employee?->full_name ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $line->description }}</td>
                    </tr>
                @endforeach
            </x-tables.table>
            <div class="mt-3 flex justify-between border-t border-line pt-3 text-sm font-medium text-ink-heading">
                <span>{{ __('Totals') }}</span>
                <span>{{ number_format((float) $journal->total_debit, 2) }} / {{ number_format((float) $journal->total_credit, 2) }}</span>
            </div>
        </x-entity.section>
    </x-layouts.entity-detail>
</x-app-layout>
