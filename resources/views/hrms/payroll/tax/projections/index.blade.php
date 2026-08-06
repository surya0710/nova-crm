<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Tax Projections')" :subtitle="__('Projected annual tax and monthly TDS')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Payroll'), 'href' => route('hrms.payroll.index')],
                ['label' => __('Income Tax'), 'href' => route('hrms.payroll.tax.index')],
                ['label' => __('Projections'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @can('calculate', \App\Models\TaxProjection::class)
            <x-ui.card class="mb-6">
                <form method="POST" action="{{ route('hrms.payroll.tax.projections.calculate') }}" class="flex flex-wrap items-end gap-3">
                    @csrf
                    <x-forms.field :label="__('Employee')" name="employee_id" required class="min-w-[16rem]">
                        <x-forms.select name="employee_id" required>
                            <option value="">{{ __('Select') }}</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}" @selected(old('employee_id') == $employee->id)>{{ $employee->full_name }}</option>
                            @endforeach
                        </x-forms.select>
                    </x-forms.field>
                    <x-ui.button type="submit" variant="primary" size="sm">{{ __('Calculate Projection') }}</x-ui.button>
                </form>
            </x-ui.card>
        @endcan

        <x-ui.card :padding="false">
            <x-tables.table :columns="[__('Employee'), __('Regime'), __('Projected Gross'), __('Taxable'), __('Annual Tax'), __('Monthly TDS'), __('Calculated')]">
                @forelse ($projections as $projection)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3 text-sm font-medium text-ink-heading">{{ $projection->employee?->full_name }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $projection->regime }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ number_format((float) $projection->projected_gross, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ number_format((float) $projection->projected_taxable, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ number_format((float) $projection->annual_tax_liability, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ number_format((float) $projection->monthly_tds, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $projection->calculated_at?->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8"><x-ui.empty-state-preset variant="payroll" /></td></tr>
                @endforelse
            </x-tables.table>
            <div class="border-t border-line px-4 py-3">{{ $projections->links() }}</div>
        </x-ui.card>
    </x-layouts.entity-listing>
</x-app-layout>
