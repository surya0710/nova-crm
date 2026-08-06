@php
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [__('Employee'), __('Type'), __('Last Day'), __('Status'), __('Actions')];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Exit Processes')" :subtitle="__('Manage employee offboarding workflows')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Exit Processes'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-ui.card class="mb-4">
            <form method="POST" action="{{ route('hrms.exit-processes.store') }}" class="grid grid-cols-1 gap-3 md:grid-cols-4">
                @csrf
                <x-forms.field :label="__('Employee')" name="employee_id">
                    <x-forms.select name="employee_id" required>
                        <option value="">{{ __('Employee') }}</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->full_name }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <x-forms.field :label="__('Exit Type')" name="exit_type">
                    <x-forms.select name="exit_type" required>
                        @foreach ($exitTypes as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <x-forms.field :label="__('Last Working Day')" name="last_working_day">
                    <x-forms.input type="date" name="last_working_day" required />
                </x-forms.field>
                <div class="flex items-end">
                    <x-ui.button type="submit" variant="primary" size="sm">{{ __('Start Exit') }}</x-ui.button>
                </div>
            </form>
        </x-ui.card>

        @if ($exitProcesses->isEmpty())
            <x-ui.card>
                <x-ui.empty-state-preset variant="generic" :title="__('No exit processes yet.')" />
            </x-ui.card>
        @else
            <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
                @foreach ($exitProcesses as $exitProcess)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3 text-sm font-medium text-ink-heading">{{ $exitProcess->employee->full_name }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $exitTypes[$exitProcess->exit_type] ?? $exitProcess->exit_type }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $exitProcess->last_working_day->format('M j, Y') }}</td>
                        <td class="px-4 py-3">
                            <x-ui.badge variant="neutral">{{ $statuses[$exitProcess->status] ?? $exitProcess->status }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-3">
                            <x-ui.button :href="route('hrms.exit-processes.show', $exitProcess)" variant="link" size="sm">{{ __('View') }}</x-ui.button>
                        </td>
                    </tr>
                @endforeach
            </x-tables.table>
        @endif

        @if ($exitProcesses->hasPages())
            <x-slot:pagination>{{ $exitProcesses->links() }}</x-slot:pagination>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
