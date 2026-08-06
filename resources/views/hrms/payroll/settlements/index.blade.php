@php
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [__('Number'), __('Employee'), __('Net settlement'), __('Status'), __('Completed')];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Final Settlements')" :subtitle="__('Generate and track employee final settlements')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Payroll'), 'href' => route('hrms.payroll.index')],
                ['label' => __('Settlements'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @can('create', \App\Models\EmployeeSettlement::class)
            <x-ui.card class="mb-4">
                <form method="POST" action="{{ route('hrms.payroll.settlements.store') }}" class="grid grid-cols-1 gap-3 md:grid-cols-3 lg:grid-cols-6">
                    @csrf
                    <x-forms.field :label="__('Employee')" name="employee_id">
                        <x-forms.select name="employee_id" required>
                            <option value="">{{ __('Select…') }}</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->full_name ?? $employee->employee_code }} ({{ $employee->status }})</option>
                            @endforeach
                        </x-forms.select>
                    </x-forms.field>
                    <x-forms.field :label="__('Pending salary')" name="pending_salary">
                        <x-forms.input type="number" name="pending_salary" step="0.01" min="0" />
                    </x-forms.field>
                    <x-forms.field :label="__('Leave encashment')" name="leave_encashment">
                        <x-forms.input type="number" name="leave_encashment" step="0.01" min="0" placeholder="{{ __('Auto if blank') }}" />
                    </x-forms.field>
                    <x-forms.field :label="__('Asset deductions')" name="asset_deductions">
                        <x-forms.input type="number" name="asset_deductions" step="0.01" min="0" />
                    </x-forms.field>
                    <x-forms.field :label="__('Statutory deductions')" name="statutory_deductions">
                        <x-forms.input type="number" name="statutory_deductions" step="0.01" min="0" />
                    </x-forms.field>
                    <div class="flex items-end">
                        <x-ui.button type="submit" variant="primary" size="sm">{{ __('Generate Settlement') }}</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        @endcan

        @if ($settlements->isEmpty())
            <x-ui.card><x-ui.empty-state-preset variant="payroll" /></x-ui.card>
        @else
            <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
                @foreach ($settlements as $settlement)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3">
                            <a href="{{ route('hrms.payroll.settlements.show', $settlement) }}" class="text-sm font-medium text-primary-700 hover:text-primary-800">{{ $settlement->settlement_number }}</a>
                        </td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $settlement->employee?->full_name }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ number_format((float) $settlement->net_settlement, 2) }}</td>
                        <td class="px-4 py-3">
                            <x-ui.badge variant="neutral">{{ $settlement->status }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $settlement->completed_at?->toDateTimeString() }}</td>
                    </tr>
                @endforeach
            </x-tables.table>
        @endif

        @if ($settlements->hasPages())
            <x-slot:pagination>{{ $settlements->links() }}</x-slot:pagination>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
