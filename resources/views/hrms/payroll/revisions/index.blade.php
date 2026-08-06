<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Salary Revisions')" :subtitle="__('History, comparison, and timeline')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Payroll'), 'href' => route('hrms.payroll.index')],
                ['label' => __('Revisions'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <form method="GET" class="mb-6 grid grid-cols-1 gap-3 md:grid-cols-3">
            <x-forms.field :label="__('Employee')" name="employee_id">
                <x-forms.select name="employee_id">
                    <option value="">{{ __('Select employee') }}</option>
                    @foreach ($employees as $row)
                        <option value="{{ $row->id }}" @selected(($employee?->id) === $row->id)>{{ $row->full_name }}</option>
                    @endforeach
                </x-forms.select>
            </x-forms.field>
            <div class="flex items-end">
                <x-ui.button type="submit" variant="primary" size="sm">{{ __('View History') }}</x-ui.button>
            </div>
        </form>

        @if ($comparison)
            <x-ui.card class="mb-6">
                <h3 class="text-sm font-semibold text-ink-heading">{{ __('Salary Comparison') }}</h3>
                <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-3 text-sm">
                    <div>
                        <p class="text-ink-muted">{{ __('Current CTC') }}</p>
                        <p class="font-medium">{{ number_format((float) $comparison['current']['annual_ctc'], 2) }}</p>
                    </div>
                    <div>
                        <p class="text-ink-muted">{{ __('Previous CTC') }}</p>
                        <p class="font-medium">{{ number_format((float) $comparison['previous']['annual_ctc'], 2) }}</p>
                    </div>
                    <div>
                        <p class="text-ink-muted">{{ __('Delta') }}</p>
                        <p class="font-medium">{{ number_format((float) $comparison['ctc_delta'], 2) }}</p>
                    </div>
                </div>
            </x-ui.card>
        @endif

        <x-ui.card :padding="false">
            <x-tables.table :columns="[__('Effective From'), __('Until'), __('Structure'), __('Annual CTC'), __('Assigned By')]">
                @forelse ($history as $row)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3 text-sm text-ink-heading">{{ $row->effective_from?->toDateString() }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $row->effective_until?->toDateString() ?? __('Open') }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $row->salaryStructure?->name }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ number_format((float) $row->annual_ctc, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $row->assignedBy?->name }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8"><x-ui.empty-state-preset variant="payroll" :title="__('Select an employee to view salary timeline.')" /></td></tr>
                @endforelse
            </x-tables.table>
        </x-ui.card>
    </x-layouts.entity-listing>
</x-app-layout>
