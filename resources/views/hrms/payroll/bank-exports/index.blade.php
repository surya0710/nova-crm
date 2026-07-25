@php
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [__('Number'), __('Run'), __('Format'), __('Employees'), __('Total'), __('Exported'), ''];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Bank Exports')" :subtitle="__('Generate bank transfer files from published runs')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Payroll'), 'href' => route('hrms.payroll.index')],
                ['label' => __('Bank Exports'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @can('create', \App\Models\PayrollBankExport::class)
            <x-ui.card class="mb-4">
                <form method="POST" action="{{ route('hrms.payroll.bank-exports.store') }}" class="grid grid-cols-1 gap-3 md:grid-cols-4">
                    @csrf
                    <x-forms.field :label="__('Published payroll run')" name="payroll_run_id" class="md:col-span-2">
                        <x-forms.select name="payroll_run_id" required>
                            <option value="">{{ __('Select run…') }}</option>
                            @foreach ($publishedRuns as $run)
                                <option value="{{ $run->id }}" @selected(($filters['payroll_run_id'] ?? null) == $run->id)>{{ $run->period?->name }} (#{{ $run->id }})</option>
                            @endforeach
                        </x-forms.select>
                    </x-forms.field>
                    <x-forms.field :label="__('Format')" name="format">
                        <x-forms.select name="format" required>
                            <option value="csv">CSV</option>
                            <option value="xlsx">XLSX</option>
                        </x-forms.select>
                    </x-forms.field>
                    <div class="flex items-end">
                        <x-ui.button type="submit" variant="primary" size="sm">{{ __('Generate Export') }}</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        @endcan

        @if ($exports->isEmpty())
            <x-ui.card><x-ui.empty-state-preset variant="payroll" /></x-ui.card>
        @else
            <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
                @foreach ($exports as $export)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3 text-sm text-ink-heading">{{ $export->export_number }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $export->payrollRun?->period?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm uppercase text-ink-muted">{{ $export->format }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $export->employee_count }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ number_format((float) $export->total_amount, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $export->exported_at?->toDateTimeString() }}</td>
                        <td class="px-4 py-3">
                            @if ($export->fileExists())
                                <x-ui.button :href="route('hrms.payroll.bank-exports.download', $export)" variant="link" size="sm">{{ __('Download') }}</x-ui.button>
                            @else
                                <span class="text-sm text-ink-muted">{{ __('Unavailable') }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-tables.table>
        @endif

        @if ($exports->hasPages())
            <x-slot:pagination>{{ $exports->links() }}</x-slot:pagination>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
