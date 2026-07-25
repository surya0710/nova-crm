@php
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [__('Number'), __('Employee'), __('Amount'), __('Outstanding'), __('Status'), ''];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Salary Advances')" :subtitle="__('Request and approve salary advances')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Payroll'), 'href' => route('hrms.payroll.index')],
                ['label' => __('Advances'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @can('create', \App\Models\SalaryAdvance::class)
            <x-ui.card class="mb-4">
                <form method="POST" action="{{ route('hrms.payroll.advances.store') }}" class="grid grid-cols-1 gap-3 md:grid-cols-5">
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
                    <x-forms.field :label="__('Monthly recovery')" name="monthly_recovery">
                        <x-forms.input type="number" name="monthly_recovery" step="0.01" min="0.01" required />
                    </x-forms.field>
                    <x-forms.field :label="__('Reason')" name="reason">
                        <x-forms.input type="text" name="reason" />
                    </x-forms.field>
                    <div class="flex items-end">
                        <x-ui.button type="submit" variant="primary" size="sm">{{ __('Request Advance') }}</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        @endcan

        @if ($advances->isEmpty())
            <x-ui.card><x-ui.empty-state-preset variant="payroll" /></x-ui.card>
        @else
            <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
                @foreach ($advances as $advance)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3 text-sm text-ink-heading">{{ $advance->advance_number }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $advance->employee?->full_name }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ number_format((float) $advance->amount, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ number_format((float) $advance->outstanding_balance, 2) }}</td>
                        <td class="px-4 py-3">
                            <x-ui.badge variant="neutral">{{ $advance->status }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-3">
                            @if ($advance->isPending())
                                @can('approve', $advance)
                                    <form method="POST" action="{{ route('hrms.payroll.advances.approve', $advance) }}" class="inline">@csrf
                                        <x-ui.button type="submit" variant="primary" size="sm">{{ __('Approve') }}</x-ui.button>
                                    </form>
                                @endcan
                                @can('reject', $advance)
                                    <form method="POST" action="{{ route('hrms.payroll.advances.reject', $advance) }}" class="mt-1 inline-flex items-center gap-1">
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

        @if ($advances->hasPages())
            <x-slot:pagination>{{ $advances->links() }}</x-slot:pagination>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
