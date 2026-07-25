@php
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [__('Number'), __('Employee'), __('Principal'), __('Outstanding'), __('Recovery/mo'), __('Status'), ''];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Employee Loans')" :subtitle="__('Track loan disbursements and recoveries')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Payroll'), 'href' => route('hrms.payroll.index')],
                ['label' => __('Loans'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @can('create', \App\Models\EmployeeLoan::class)
            <x-ui.card class="mb-4">
                <form method="POST" action="{{ route('hrms.payroll.loans.store') }}" class="grid grid-cols-1 gap-3 md:grid-cols-3 lg:grid-cols-6">
                    @csrf
                    <x-forms.field :label="__('Employee')" name="employee_id">
                        <x-forms.select name="employee_id" required>
                            <option value="">{{ __('Select…') }}</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->full_name ?? $employee->employee_code }}</option>
                            @endforeach
                        </x-forms.select>
                    </x-forms.field>
                    <x-forms.field :label="__('Principal')" name="principal_amount">
                        <x-forms.input type="number" name="principal_amount" step="0.01" min="0.01" required />
                    </x-forms.field>
                    <x-forms.field :label="__('Monthly recovery')" name="monthly_recovery">
                        <x-forms.input type="number" name="monthly_recovery" step="0.01" min="0.01" required />
                    </x-forms.field>
                    <x-forms.field :label="__('Type')" name="loan_type">
                        <x-forms.input type="text" name="loan_type" value="general" />
                    </x-forms.field>
                    <x-forms.field :label="__('Disbursed on')" name="disbursed_on">
                        <x-forms.input type="date" name="disbursed_on" />
                    </x-forms.field>
                    <div class="flex items-end">
                        <x-ui.button type="submit" variant="primary" size="sm">{{ __('Create Loan') }}</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        @endcan

        @if ($loans->isEmpty())
            <x-ui.card><x-ui.empty-state-preset variant="payroll" /></x-ui.card>
        @else
            <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
                @foreach ($loans as $loan)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3 text-sm text-ink-heading">{{ $loan->loan_number }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $loan->employee?->full_name }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ number_format((float) $loan->principal_amount, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ number_format((float) $loan->outstanding_balance, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ number_format((float) $loan->monthly_recovery, 2) }}</td>
                        <td class="px-4 py-3">
                            <x-ui.badge variant="neutral">{{ $loan->status }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-3">
                            @can('close', $loan)
                                @if ($loan->isActive())
                                    <form method="POST" action="{{ route('hrms.payroll.loans.close', $loan) }}" class="flex items-center gap-2">
                                        @csrf
                                        <x-forms.input type="text" name="reason" placeholder="{{ __('Closure reason') }}" class="w-32 text-xs" />
                                        <x-ui.button type="submit" variant="ghost" size="sm">{{ __('Close') }}</x-ui.button>
                                    </form>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </x-tables.table>
        @endif

        @if ($loans->hasPages())
            <x-slot:pagination>{{ $loans->links() }}</x-slot:pagination>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
