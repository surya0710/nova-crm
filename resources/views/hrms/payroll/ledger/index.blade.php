@php
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [__('Account'), __('Type'), __('Amount'), __('Employee'), __('Run'), __('Generated')];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Payroll Ledger')" :subtitle="__('Generate and browse ledger entries')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Payroll'), 'href' => route('hrms.payroll.index')],
                ['label' => __('Ledger'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @can('generate', \App\Models\PayrollLedgerEntry::class)
            <x-ui.card class="mb-4">
                <form method="POST" action="{{ route('hrms.payroll.ledger.generate') }}" class="grid grid-cols-1 gap-3 md:grid-cols-3">
                    @csrf
                    <x-forms.field :label="__('Published payroll run')" name="payroll_run_id" class="md:col-span-2">
                        <x-forms.select name="payroll_run_id" required>
                            <option value="">{{ __('Select run…') }}</option>
                            @foreach ($publishedRuns as $run)
                                <option value="{{ $run->id }}" @selected(($filters['payroll_run_id'] ?? null) == $run->id)>{{ $run->period?->name }} (#{{ $run->id }})</option>
                            @endforeach
                        </x-forms.select>
                    </x-forms.field>
                    <div class="flex items-end">
                        <x-ui.button type="submit" variant="primary" size="sm">{{ __('Generate Ledger') }}</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        @endcan

        <x-slot:filters>
            <form method="GET" class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <x-forms.field :label="__('Filter by run')" name="payroll_run_id">
                    <x-forms.select name="payroll_run_id">
                        <option value="">{{ __('All') }}</option>
                        @foreach ($publishedRuns as $run)
                            <option value="{{ $run->id }}" @selected(($filters['payroll_run_id'] ?? null) == $run->id)>{{ $run->period?->name }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <div class="flex items-end">
                    <x-ui.button type="submit" variant="primary" size="sm">{{ __('Filter') }}</x-ui.button>
                </div>
            </form>
        </x-slot:filters>

        @if ($entries->isEmpty())
            <x-ui.card><x-ui.empty-state-preset variant="payroll" /></x-ui.card>
        @else
            <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
                @foreach ($entries as $entry)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3 text-sm"><span class="font-mono text-xs">{{ $entry->account_code }}</span> {{ $entry->account_name }}</td>
                        <td class="px-4 py-3 text-sm capitalize text-ink-muted">{{ $entry->entry_type }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ number_format((float) $entry->amount, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $entry->employee?->full_name ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $entry->payrollRun?->period?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $entry->generated_at?->toDateTimeString() }}</td>
                    </tr>
                @endforeach
            </x-tables.table>
        @endif

        @if ($entries->hasPages())
            <x-slot:pagination>{{ $entries->links() }}</x-slot:pagination>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
