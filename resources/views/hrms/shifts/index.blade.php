@php
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [
        __('Name'),
        __('Hours'),
        __('Break'),
        __('Grace'),
        __('OT'),
        __('Status'),
        __('Actions'),
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Shift Management')"
        :subtitle="__('Configured under Organization Settings. Assign shifts to employees from Shift Assignments.')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Shifts'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @can('create', App\Models\HrmsShift::class)
            <x-ui.card class="mb-6">
                <x-entity.section :title="__('Add shift')">
                    <form method="POST" action="{{ route('hrms.shifts.store') }}" class="grid grid-cols-1 gap-3 md:grid-cols-3">
                        @csrf
                        <x-forms.field :label="__('Name')" name="name">
                            <x-forms.input name="name" placeholder="{{ __('Name') }}" required />
                        </x-forms.field>
                        <x-forms.field :label="__('Code')" name="code">
                            <x-forms.input name="code" placeholder="{{ __('Code') }}" />
                        </x-forms.field>
                        <x-forms.field :label="__('Start time')" name="start_time">
                            <x-forms.input type="time" name="start_time" required />
                        </x-forms.field>
                        <x-forms.field :label="__('End time')" name="end_time">
                            <x-forms.input type="time" name="end_time" required />
                        </x-forms.field>
                        <x-forms.field :label="__('Break (min)')" name="break_minutes">
                            <x-forms.input type="number" name="break_minutes" placeholder="{{ __('Break (min)') }}" value="60" />
                        </x-forms.field>
                        <x-forms.field :label="__('Grace (min)')" name="grace_period_minutes">
                            <x-forms.input type="number" name="grace_period_minutes" placeholder="{{ __('Grace (min)') }}" value="15" />
                        </x-forms.field>
                        <x-forms.field :label="__('Working Hours')" name="working_hours">
                            <x-forms.input type="number" name="working_hours" placeholder="{{ __('Working Hours') }}" step="0.25" />
                        </x-forms.field>
                        <x-forms.field :label="__('OT Threshold (min)')" name="overtime_threshold_minutes">
                            <x-forms.input type="number" name="overtime_threshold_minutes" placeholder="{{ __('OT Threshold (min)') }}" />
                        </x-forms.field>
                        <x-forms.checkbox name="is_default" value="1" :label="__('Default Shift')" />
                        <x-forms.checkbox name="is_active" value="1" :label="__('Active')" checked />
                        <div class="md:col-span-3">
                            <x-ui.button type="submit" variant="primary" size="sm">{{ __('Add Shift') }}</x-ui.button>
                        </div>
                    </form>
                </x-entity.section>
            </x-ui.card>
        @endcan

        @if ($shifts->isEmpty())
            <x-ui.card>
                <x-ui.empty-state-preset variant="attendance" />
            </x-ui.card>
        @else
            <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
                @foreach ($shifts as $shift)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3 text-sm font-medium text-ink-heading">
                            {{ $shift->name }}
                            @if ($shift->is_default)
                                <x-ui.badge variant="primary" class="ml-1">{{ __('Default') }}</x-ui.badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ substr((string) $shift->start_time, 0, 5) }} – {{ substr((string) $shift->end_time, 0, 5) }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $shift->break_minutes ?? 0 }} min</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $shift->grace_period_minutes ?? 0 }} min</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $shift->overtime_threshold_minutes ? $shift->overtime_threshold_minutes.' min' : '—' }}</td>
                        <td class="px-4 py-3">
                            <x-ui.badge :variant="$shift->is_active ? 'success' : 'neutral'">{{ $shift->is_active ? __('Active') : __('Inactive') }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-3">
                            @can('delete', $shift)
                                <form method="POST" action="{{ route('hrms.shifts.destroy', $shift) }}">
                                    @csrf
                                    @method('DELETE')
                                    <x-ui.button type="submit" variant="ghost" size="sm">{{ __('Delete') }}</x-ui.button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </x-tables.table>
            <div class="mt-4">{{ $shifts->links() }}</div>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
