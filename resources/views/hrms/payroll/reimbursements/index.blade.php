@php
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [__('Claim'), __('Employee'), __('Category'), __('Amount'), __('Status'), ''];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Expense Reimbursements')" :subtitle="__('Submit and approve reimbursement claims')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Payroll'), 'href' => route('hrms.payroll.index')],
                ['label' => __('Reimbursements'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @can('create', \App\Models\ExpenseReimbursement::class)
            <x-ui.card class="mb-4">
                <form method="POST" action="{{ route('hrms.payroll.reimbursements.store') }}" class="grid grid-cols-1 gap-3 md:grid-cols-5">
                    @csrf
                    <x-forms.field :label="__('Employee')" name="employee_id">
                        <x-forms.select name="employee_id" required>
                            <option value="">{{ __('Select…') }}</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->full_name ?? $employee->employee_code }}</option>
                            @endforeach
                        </x-forms.select>
                    </x-forms.field>
                    <x-forms.field :label="__('Amount')" name="amount">
                        <x-forms.input type="number" name="amount" step="0.01" min="0.01" required />
                    </x-forms.field>
                    <x-forms.field :label="__('Category')" name="category">
                        <x-forms.input type="text" name="category" value="general" />
                    </x-forms.field>
                    <x-forms.field :label="__('Description')" name="description">
                        <x-forms.input type="text" name="description" />
                    </x-forms.field>
                    <div class="flex items-end gap-3">
                        <label class="inline-flex items-center gap-2 text-sm text-ink-heading">
                            <input type="checkbox" name="is_taxable" value="1" class="rounded border-line">
                            {{ __('Taxable') }}
                        </label>
                        <x-ui.button type="submit" variant="primary" size="sm">{{ __('Submit Claim') }}</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        @endcan

        @if ($reimbursements->isEmpty())
            <x-ui.card><x-ui.empty-state-preset variant="payroll" /></x-ui.card>
        @else
            <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
                @foreach ($reimbursements as $claim)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3 text-sm text-ink-heading">{{ $claim->claim_number }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $claim->employee?->full_name }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $claim->category }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ number_format((float) $claim->amount, 2) }}</td>
                        <td class="px-4 py-3">
                            <x-ui.badge variant="neutral">{{ $claim->status }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-3">
                            @if ($claim->status === 'pending')
                                @can('approve', $claim)
                                    <form method="POST" action="{{ route('hrms.payroll.reimbursements.approve', $claim) }}" class="inline">@csrf
                                        <x-ui.button type="submit" variant="primary" size="sm">{{ __('Approve') }}</x-ui.button>
                                    </form>
                                @endcan
                                @can('reject', $claim)
                                    <form method="POST" action="{{ route('hrms.payroll.reimbursements.reject', $claim) }}" class="mt-1 inline-flex items-center gap-1">
                                        @csrf
                                        <x-forms.input type="text" name="rejection_reason" placeholder="{{ __('Reason') }}" class="w-24 text-xs" />
                                        <x-ui.button type="submit" variant="ghost" size="sm" class="text-danger">{{ __('Reject') }}</x-ui.button>
                                    </form>
                                @endcan
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-tables.table>
        @endif

        @if ($reimbursements->hasPages())
            <x-slot:pagination>{{ $reimbursements->links() }}</x-slot:pagination>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
