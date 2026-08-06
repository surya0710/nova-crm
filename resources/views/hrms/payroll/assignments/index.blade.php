@php
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [__('Employee'), __('Structure'), __('Effective From'), __('Effective Until'), __('Annual CTC')];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Employee Salary Assignments')" :subtitle="__('Assign salary structures to employees')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Payroll'), 'href' => route('hrms.payroll.index')],
                ['label' => __('Assignments'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @can('create', \App\Models\EmployeeSalaryAssignment::class)
            <x-ui.card class="mb-4">
                <form method="POST" action="{{ route('hrms.payroll.assignments.store') }}" class="grid grid-cols-1 gap-3 md:grid-cols-5">
                    @csrf
                    <x-forms.field :label="__('Employee')" name="employee_id">
                        <x-forms.select name="employee_id" required>
                            <option value="">{{ __('Employee') }}</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}" @selected(old('employee_id') == $employee->id)>{{ $employee->full_name }} ({{ $employee->employee_code }})</option>
                            @endforeach
                        </x-forms.select>
                    </x-forms.field>
                    <x-forms.field :label="__('Salary Structure')" name="salary_structure_id">
                        <x-forms.select name="salary_structure_id" required>
                            <option value="">{{ __('Salary Structure') }}</option>
                            @foreach ($structures as $structure)
                                <option value="{{ $structure->id }}" @selected(old('salary_structure_id') == $structure->id)>{{ $structure->name }}</option>
                            @endforeach
                        </x-forms.select>
                    </x-forms.field>
                    <x-forms.field :label="__('Effective From')" name="effective_from">
                        <x-forms.input name="effective_from" type="date" :value="old('effective_from', now()->toDateString())" required />
                    </x-forms.field>
                    <x-forms.field :label="__('Annual CTC')" name="annual_ctc">
                        <x-forms.input name="annual_ctc" type="number" step="0.01" placeholder="{{ __('Annual CTC') }}" :value="old('annual_ctc')" />
                    </x-forms.field>
                    <div class="flex items-end">
                        <x-ui.button type="submit" variant="primary" size="sm">{{ __('Assign') }}</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        @endcan

        @if ($assignments->isEmpty())
            <x-ui.card><x-ui.empty-state-preset variant="payroll" /></x-ui.card>
        @else
            <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
                @foreach ($assignments as $assignment)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3 text-sm text-ink-heading">{{ $assignment->employee?->full_name }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $assignment->salaryStructure?->name }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $assignment->effective_from?->toDateString() }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $assignment->effective_until?->toDateString() ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $assignment->annual_ctc ?? '—' }}</td>
                    </tr>
                @endforeach
            </x-tables.table>
        @endif

        @if ($assignments->hasPages())
            <x-slot:pagination>{{ $assignments->links() }}</x-slot:pagination>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
