@php
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [
        __('Name'),
        __('Code'),
        __('Allocation'),
        __('Half Day'),
        __('HR Approval'),
        __('Actions'),
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Leave Types')"
        :subtitle="__('Configure leave categories and policies')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Leave Types'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @can('create', \App\Models\LeaveType::class)
            <x-ui.card class="mb-6">
                <x-entity.section :title="__('Add leave type')">
                    <form method="POST" action="{{ route('hrms.leave-types.store') }}" class="grid grid-cols-1 gap-3 md:grid-cols-6">
                        @csrf
                        <x-forms.field :label="__('Name')" name="name">
                            <x-forms.input name="name" placeholder="{{ __('Name') }}" required />
                        </x-forms.field>
                        <x-forms.field :label="__('Code')" name="code">
                            <x-forms.input name="code" placeholder="{{ __('Code') }}" required />
                        </x-forms.field>
                        <x-forms.field :label="__('Allocation')" name="allocation_days">
                            <x-forms.input name="allocation_days" type="number" placeholder="{{ __('Allocation') }}" />
                        </x-forms.field>
                        <x-forms.field :label="__('Max Consecutive')" name="max_consecutive_days">
                            <x-forms.input name="max_consecutive_days" type="number" placeholder="{{ __('Max Consecutive') }}" />
                        </x-forms.field>
                        <x-forms.checkbox name="allow_half_day" value="1" :label="__('Half Day')" checked />
                        <div class="flex items-end">
                            <x-ui.button type="submit" variant="primary" size="sm">{{ __('Add Leave Type') }}</x-ui.button>
                        </div>
                    </form>
                </x-entity.section>
            </x-ui.card>
        @endcan

        <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
            @foreach ($leaveTypes as $leaveType)
                <tr class="hover:bg-surface-muted/60 transition">
                    <td class="px-4 py-3 text-sm font-medium text-ink-heading">{{ $leaveType->name }}</td>
                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $leaveType->code }}</td>
                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $leaveType->allocation_days ?? $leaveType->max_days_per_year ?? '—' }}</td>
                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $leaveType->allow_half_day ? __('Yes') : __('No') }}</td>
                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $leaveType->requires_hr_approval ? __('Yes') : __('No') }}</td>
                    <td class="px-4 py-3">
                        @can('delete', $leaveType)
                            <form method="POST" action="{{ route('hrms.leave-types.destroy', $leaveType) }}">
                                @csrf
                                @method('DELETE')
                                <x-ui.button type="submit" variant="ghost" size="sm">{{ __('Delete') }}</x-ui.button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @endforeach
        </x-tables.table>
        <div class="mt-4">{{ $leaveTypes->links() }}</div>
    </x-layouts.entity-listing>
</x-app-layout>
