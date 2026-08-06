<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Investment Declarations')" :subtitle="__('Employee tax saving declarations')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Payroll'), 'href' => route('hrms.payroll.index')],
                ['label' => __('Income Tax'), 'href' => route('hrms.payroll.tax.index')],
                ['label' => __('Declarations'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @can('create', \App\Models\TaxDeclaration::class)
            <x-ui.card class="mb-6">
                <form method="POST" action="{{ route('hrms.payroll.tax.declarations.store') }}" class="grid grid-cols-1 gap-3 md:grid-cols-3">
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
                    <x-forms.field :label="__('Category')" name="items[0][category]" required>
                        <x-forms.select name="items[0][category]" required>
                            @foreach ($categories as $key => $meta)
                                <option value="{{ $key }}" @selected(old('items.0.category') === $key)>{{ __($meta['label'] ?? $key) }}</option>
                            @endforeach
                        </x-forms.select>
                    </x-forms.field>
                    <x-forms.field :label="__('Label')" name="items[0][label]" required>
                        <x-forms.input name="items[0][label]" :value="old('items.0.label')" required />
                    </x-forms.field>
                    <x-forms.field :label="__('Declared Amount')" name="items[0][declared_amount]" required>
                        <x-forms.input name="items[0][declared_amount]" type="number" step="0.01" :value="old('items.0.declared_amount')" required />
                    </x-forms.field>
                    <x-forms.field :label="__('Section')" name="items[0][section]">
                        <x-forms.input name="items[0][section]" :value="old('items.0.section')" />
                    </x-forms.field>
                    <div class="md:col-span-3">
                        <x-ui.button type="submit" variant="primary" size="sm">{{ __('Create Declaration') }}</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        @endcan

        <x-ui.card :padding="false">
            <x-tables.table :columns="[__('Number'), __('Employee'), __('FY'), __('Total'), __('Status'), __('Actions')]">
                @forelse ($declarations as $declaration)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3 text-sm font-medium text-ink-heading">{{ $declaration->declaration_number }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $declaration->employee?->full_name }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $declaration->financialYear?->code }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ number_format((float) $declaration->declared_total, 2) }}</td>
                        <td class="px-4 py-3"><x-ui.badge variant="neutral">{{ $declaration->status }}</x-ui.badge></td>
                        <td class="px-4 py-3 text-sm space-x-1">
                            @if ($declaration->canSubmit())
                                @can('submit', $declaration)
                                    <form method="POST" action="{{ route('hrms.payroll.tax.declarations.submit', $declaration) }}" class="inline">@csrf
                                        <x-ui.button type="submit" variant="primary" size="sm">{{ __('Submit') }}</x-ui.button>
                                    </form>
                                @endcan
                            @endif
                            @if ($declaration->canVerify())
                                @can('verify', $declaration)
                                    <form method="POST" action="{{ route('hrms.payroll.tax.declarations.verify', $declaration) }}" class="inline">@csrf
                                        <x-ui.button type="submit" variant="primary" size="sm">{{ __('Verify') }}</x-ui.button>
                                    </form>
                                    <form method="POST" action="{{ route('hrms.payroll.tax.declarations.reject', $declaration) }}" class="inline">@csrf
                                        <input type="hidden" name="reason" value="{{ __('Rejected from listing') }}">
                                        <x-ui.button type="submit" variant="secondary" size="sm">{{ __('Reject') }}</x-ui.button>
                                    </form>
                                @endcan
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8"><x-ui.empty-state-preset variant="payroll" /></td></tr>
                @endforelse
            </x-tables.table>
            <div class="border-t border-line px-4 py-3">{{ $declarations->links() }}</div>
        </x-ui.card>
    </x-layouts.entity-listing>
</x-app-layout>
