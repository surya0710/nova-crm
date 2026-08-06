<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Payroll Adjustments')" :subtitle="__('Bonus, incentive, penalty, arrears, and misc adjustments')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Payroll'), 'href' => route('hrms.payroll.index')],
                ['label' => __('Adjustments'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @can('create', \App\Models\PayrollAdjustment::class)
            <x-ui.card class="mb-6">
                <form method="POST" action="{{ route('hrms.payroll.adjustments.store') }}" class="grid grid-cols-1 gap-3 md:grid-cols-3">
                    @csrf
                    <x-forms.field :label="__('Employee')" name="employee_id" required>
                        <x-forms.select name="employee_id" required>
                            <option value="">{{ __('Select') }}</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}" @selected(old('employee_id') == $employee->id)>{{ $employee->full_name }}</option>
                            @endforeach
                        </x-forms.select>
                    </x-forms.field>
                    <x-forms.field :label="__('Period')" name="payroll_period_id">
                        <x-forms.select name="payroll_period_id">
                            <option value="">{{ __('Any / effective date') }}</option>
                            @foreach ($periods as $period)
                                <option value="{{ $period->id }}" @selected(old('payroll_period_id') == $period->id)>{{ $period->name }}</option>
                            @endforeach
                        </x-forms.select>
                    </x-forms.field>
                    <x-forms.field :label="__('Type')" name="adjustment_type" required>
                        <x-forms.select name="adjustment_type" required>
                            @foreach ($types as $value => $label)
                                <option value="{{ $value }}" @selected(old('adjustment_type') === $value)>{{ __($label) }}</option>
                            @endforeach
                        </x-forms.select>
                    </x-forms.field>
                    <x-forms.field :label="__('Title')" name="title" required>
                        <x-forms.input name="title" :value="old('title')" required />
                    </x-forms.field>
                    <x-forms.field :label="__('Amount')" name="amount" required>
                        <x-forms.input name="amount" type="number" step="0.01" :value="old('amount')" required />
                    </x-forms.field>
                    <x-forms.field :label="__('Effective Date')" name="effective_date">
                        <x-forms.input name="effective_date" type="date" :value="old('effective_date')" />
                    </x-forms.field>
                    <div class="md:col-span-3">
                        <x-ui.button type="submit" variant="primary" size="sm">{{ __('Create Adjustment') }}</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        @endcan

        <x-ui.card :padding="false">
            <x-tables.table :columns="[__('Number'), __('Employee'), __('Type'), __('Amount'), __('Status'), __('Actions')]">
                @forelse ($adjustments as $adjustment)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3 text-sm font-medium text-ink-heading">{{ $adjustment->adjustment_number }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $adjustment->employee?->full_name }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $adjustment->adjustment_type }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ number_format((float) $adjustment->amount, 2) }}</td>
                        <td class="px-4 py-3"><x-ui.badge variant="neutral">{{ $adjustment->status }}</x-ui.badge></td>
                        <td class="px-4 py-3 text-sm">
                            @if ($adjustment->isDraft())
                                @can('approve', $adjustment)
                                    <form method="POST" action="{{ route('hrms.payroll.adjustments.approve', $adjustment) }}" class="inline">@csrf
                                        <x-ui.button type="submit" variant="primary" size="sm">{{ __('Approve') }}</x-ui.button>
                                    </form>
                                    <form method="POST" action="{{ route('hrms.payroll.adjustments.reject', $adjustment) }}" class="inline">@csrf
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
            <div class="p-4">{{ $adjustments->links() }}</div>
        </x-ui.card>
    </x-layouts.entity-listing>
</x-app-layout>
