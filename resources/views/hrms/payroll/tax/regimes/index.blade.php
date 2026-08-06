<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Tax Regimes')" :subtitle="__('Employee income tax regime selection for :fy', ['fy' => $financialYear->label ?? __('current FY')])">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Payroll'), 'href' => route('hrms.payroll.index')],
                ['label' => __('Income Tax'), 'href' => route('hrms.payroll.tax.index')],
                ['label' => __('Regimes'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @can('select', \App\Models\EmployeeTaxRegime::class)
            <x-ui.card class="mb-6">
                <form method="POST" action="{{ route('hrms.payroll.tax.regimes.store') }}" class="grid grid-cols-1 gap-3 md:grid-cols-4">
                    @csrf
                    <x-forms.field :label="__('Employee')" name="employee_id" required>
                        <x-forms.select name="employee_id" required>
                            <option value="">{{ __('Select') }}</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}" @selected(old('employee_id') == $employee->id)>{{ $employee->full_name }}</option>
                            @endforeach
                        </x-forms.select>
                    </x-forms.field>
                    <x-forms.field :label="__('Regime')" name="regime" required>
                        <x-forms.select name="regime" required>
                            @foreach ($regimes as $value => $label)
                                <option value="{{ $value }}" @selected(old('regime') === $value)>{{ __($label) }}</option>
                            @endforeach
                        </x-forms.select>
                    </x-forms.field>
                    <x-forms.field :label="__('Effective From')" name="effective_from" required>
                        <x-forms.input name="effective_from" type="date" :value="old('effective_from', $financialYear->start_date?->format('Y-m-d'))" required />
                    </x-forms.field>
                    <x-forms.field :label="__('Notes')" name="notes">
                        <x-forms.input name="notes" :value="old('notes')" />
                    </x-forms.field>
                    <div class="md:col-span-4">
                        <x-ui.button type="submit" variant="primary" size="sm">{{ __('Select Regime') }}</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        @endcan

        <x-ui.card :padding="false">
            <x-tables.table :columns="[__('Employee'), __('Current Regime'), __('Effective From')]">
                @forelse ($employees as $employee)
                    @php $regime = $activeRegimes[$employee->id] ?? null; @endphp
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3 text-sm font-medium text-ink-heading">{{ $employee->full_name }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $regime?->regime ? __($regimes[$regime->regime] ?? $regime->regime) : __('Not set') }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $regime?->effective_from?->format('Y-m-d') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-8"><x-ui.empty-state-preset variant="payroll" /></td></tr>
                @endforelse
            </x-tables.table>
            <div class="border-t border-line px-4 py-3">{{ $employees->links() }}</div>
        </x-ui.card>
    </x-layouts.entity-listing>
</x-app-layout>
