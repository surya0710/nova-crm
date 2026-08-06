@php
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [
        __('Payslip'),
        __('Period'),
        __('Net'),
        __('Actions'),
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('My Payroll')"
        :subtitle="__('View and download your published payslips')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('My HR'), 'href' => route('ess.dashboard')],
                ['label' => __('Payroll'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @include('ess.partials.nav')

        <x-slot:filters>
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <x-forms.field :label="__('Year')" name="year" class="mb-0">
                    <x-forms.input type="number" name="year" :value="$filters['year'] ?? ''" placeholder="{{ __('Year') }}" min="2000" max="2100" />
                </x-forms.field>
                <x-forms.field :label="__('Month')" name="month" class="mb-0">
                    <x-forms.input type="number" name="month" :value="$filters['month'] ?? ''" placeholder="{{ __('Month') }}" min="1" max="12" />
                </x-forms.field>
                <x-ui.button type="submit" variant="primary" size="sm">{{ __('Filter') }}</x-ui.button>
            </form>
        </x-slot:filters>

        @if ($payslips->isEmpty())
            <x-ui.card>
                <x-ui.empty-state-preset variant="documents" />
            </x-ui.card>
        @else
            <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
                @foreach ($payslips as $payslip)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3 text-sm font-medium text-ink-heading">{{ $payslip->payslip_number }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $payslip->payrollRun?->period?->name }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ number_format((float) $payslip->net_salary, 2) }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-2">
                                <x-ui.button :href="route('ess.payroll.show', $payslip)" variant="ghost" size="sm">{{ __('View') }}</x-ui.button>
                                <x-ui.button :href="route('ess.payroll.download', $payslip)" variant="ghost" size="sm">{{ __('PDF') }}</x-ui.button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-tables.table>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
