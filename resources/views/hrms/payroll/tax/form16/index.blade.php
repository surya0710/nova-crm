<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Form 16')" :subtitle="__('Generate annual tax certificates')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Payroll'), 'href' => route('hrms.payroll.index')],
                ['label' => __('Income Tax'), 'href' => route('hrms.payroll.tax.index')],
                ['label' => __('Form 16'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @can('generate', \App\Models\Form16Record::class)
            <x-ui.card class="mb-6">
                <form method="POST" action="{{ route('hrms.payroll.tax.form16.store') }}" class="grid grid-cols-1 gap-3 md:grid-cols-3">
                    @csrf
                    <x-forms.field :label="__('Employee')" name="employee_id" required>
                        <x-forms.select name="employee_id" required>
                            <option value="">{{ __('Select') }}</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}" @selected(old('employee_id') == $employee->id)>{{ $employee->full_name }}</option>
                            @endforeach
                        </x-forms.select>
                    </x-forms.field>
                    <x-forms.field :label="__('Financial Year')" name="tax_financial_year_id" required>
                        <x-forms.select name="tax_financial_year_id" required>
                            @foreach ($financialYears as $fy)
                                <option value="{{ $fy->id }}" @selected(old('tax_financial_year_id', $financialYear->id ?? null) == $fy->id)>{{ $fy->label }}</option>
                            @endforeach
                        </x-forms.select>
                    </x-forms.field>
                    <div class="flex items-end">
                        <x-ui.button type="submit" variant="primary" size="sm">{{ __('Generate Form 16') }}</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        @endcan

        <x-ui.card :padding="false">
            <x-tables.table :columns="[__('Form Number'), __('Employee'), __('FY'), __('Status'), __('Generated'), __('By')]">
                @forelse ($records as $record)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3 text-sm font-medium text-ink-heading">{{ $record->form_number }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $record->employee?->full_name }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $record->financialYear?->code }}</td>
                        <td class="px-4 py-3"><x-ui.badge variant="neutral">{{ $record->status }}</x-ui.badge></td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $record->generated_at?->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $record->generatedBy?->name }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8"><x-ui.empty-state-preset variant="payroll" /></td></tr>
                @endforelse
            </x-tables.table>
            <div class="border-t border-line px-4 py-3">{{ $records->links() }}</div>
        </x-ui.card>
    </x-layouts.entity-listing>
</x-app-layout>
