@php
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [__('Number'), __('Employee'), __('Period'), __('Net'), __('Generated'), __('Email')];
    $currentYear = (int) now()->year;
    $currentMonth = (int) now()->month;
    $prev = now()->subMonth();
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Payslips')" :subtitle="__('Browse and filter employee payslips')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Payroll'), 'href' => route('hrms.payroll.index')],
                ['label' => __('Payslips'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:filters>
            <form method="GET" class="space-y-3" x-data="{ year: '{{ $filters['year'] ?? '' }}', month: '{{ $filters['month'] ?? '' }}' }">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                    <x-forms.field :label="__('Period')" name="period_id">
                        <x-forms.select name="period_id">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($periods as $period)
                                <option value="{{ $period->id }}" @selected(($filters['period_id'] ?? null) == $period->id)>{{ $period->name }}</option>
                            @endforeach
                        </x-forms.select>
                    </x-forms.field>
                    <x-forms.field :label="__('Year')" name="year">
                        <x-forms.select name="year" x-model="year">
                            <option value="">{{ __('All years') }}</option>
                            @for ($y = $currentYear; $y >= $currentYear - 10; $y--)
                                <option value="{{ $y }}" @selected(($filters['year'] ?? null) == $y)>{{ $y }}</option>
                            @endfor
                        </x-forms.select>
                    </x-forms.field>
                    <x-forms.field :label="__('Month')" name="month">
                        <x-forms.select name="month" x-model="month">
                            <option value="">{{ __('All months') }}</option>
                            @foreach (range(1, 12) as $m)
                                <option value="{{ $m }}" @selected(($filters['month'] ?? null) == $m)>{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
                            @endforeach
                        </x-forms.select>
                    </x-forms.field>
                    <div class="flex items-end">
                        <x-ui.button type="submit" variant="primary" size="sm">{{ __('Filter') }}</x-ui.button>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <x-ui.button :href="request()->url().'?'.http_build_query(array_filter(['period_id' => $filters['period_id'] ?? null, 'year' => $currentYear, 'month' => $currentMonth]))" variant="secondary" size="sm">{{ __('Current Month') }}</x-ui.button>
                    <x-ui.button :href="request()->url().'?'.http_build_query(array_filter(['period_id' => $filters['period_id'] ?? null, 'year' => $prev->year, 'month' => $prev->month]))" variant="secondary" size="sm">{{ __('Previous Month') }}</x-ui.button>
                    <x-ui.button :href="request()->url()" variant="ghost" size="sm">{{ __('Clear') }}</x-ui.button>
                </div>
            </form>
        </x-slot:filters>

        @if ($payslips->isEmpty())
            <x-ui.card><x-ui.empty-state-preset variant="payroll" /></x-ui.card>
        @else
            <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
                @foreach ($payslips as $payslip)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3">
                            <a href="{{ route('hrms.payroll.payslips.show', $payslip) }}" class="text-sm font-medium text-primary-700 hover:text-primary-800">{{ $payslip->payslip_number }}</a>
                        </td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $payslip->employee?->full_name }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $payslip->payrollRun?->period?->name }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ number_format((float) $payslip->net_salary, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $payslip->generated_at?->toDateTimeString() }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $payslip->emailed_at ? __('Sent') : __('—') }}</td>
                    </tr>
                @endforeach
            </x-tables.table>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
