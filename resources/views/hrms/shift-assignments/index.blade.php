@php
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [
        __('Employee'),
        __('Shift'),
        __('From'),
        __('Until'),
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Shift Assignments')"
        :subtitle="__('Assign work shifts to employees')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Shifts'), 'href' => route('hrms.shifts.index')],
                ['label' => __('Shift Assignments'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @can('create', App\Models\HrmsShift::class)
            <x-ui.card class="mb-6">
                <x-entity.section :title="__('Assign shift')">
                    <form method="POST" action="{{ route('hrms.shift-assignments.store') }}" class="grid grid-cols-1 gap-3 md:grid-cols-5">
                        @csrf
                        <x-forms.field :label="__('Employee')" name="employee_id">
                            <x-forms.select name="employee_id" required>
                                <option value="">{{ __('Employee') }}</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->full_name }}</option>
                                @endforeach
                            </x-forms.select>
                        </x-forms.field>
                        <x-forms.field :label="__('Shift')" name="shift_id">
                            <x-forms.select name="shift_id" required>
                                <option value="">{{ __('Shift') }}</option>
                                @foreach ($shifts as $shift)
                                    <option value="{{ $shift->id }}">{{ $shift->name }}</option>
                                @endforeach
                            </x-forms.select>
                        </x-forms.field>
                        <x-forms.field :label="__('Effective from')" name="effective_from">
                            <x-forms.input type="date" name="effective_from" required />
                        </x-forms.field>
                        <x-forms.field :label="__('Effective to')" name="effective_to">
                            <x-forms.input type="date" name="effective_to" />
                        </x-forms.field>
                        <div class="flex items-end">
                            <x-ui.button type="submit" variant="primary" size="sm">{{ __('Assign Shift') }}</x-ui.button>
                        </div>
                    </form>
                </x-entity.section>
            </x-ui.card>
        @endcan

        @if ($assignments->isEmpty())
            <x-ui.card>
                <x-ui.empty-state-preset variant="attendance" />
            </x-ui.card>
        @else
            <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
                @foreach ($assignments as $assignment)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3 text-sm text-ink-heading">{{ $assignment->employee?->full_name }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $assignment->shift?->name }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $assignment->effective_from?->format('Y-m-d') }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $assignment->effective_to?->format('Y-m-d') ?? '—' }}</td>
                    </tr>
                @endforeach
            </x-tables.table>
            <div class="mt-4">{{ $assignments->links() }}</div>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
