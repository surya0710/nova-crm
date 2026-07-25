@php
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [
        __('Name'),
        __('Date'),
        __('Scope'),
        __('Recurring'),
        __('Actions'),
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Holiday Calendar')"
        :subtitle="__('Organization and branch holiday schedules')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Holiday Calendar'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @can('create', \App\Models\Holiday::class)
            <x-ui.card class="mb-6">
                <x-entity.section :title="__('Add holiday')">
                    <form method="POST" action="{{ route('hrms.holidays.store') }}" class="grid grid-cols-1 gap-3 md:grid-cols-5">
                        @csrf
                        <x-forms.field :label="__('Holiday Name')" name="name">
                            <x-forms.input name="name" placeholder="{{ __('Holiday Name') }}" required />
                        </x-forms.field>
                        <x-forms.field :label="__('Date')" name="holiday_date">
                            <x-forms.input name="holiday_date" type="date" required />
                        </x-forms.field>
                        <x-forms.field :label="__('Branch')" name="branch_id">
                            <x-forms.select name="branch_id">
                                <option value="">{{ __('Organization-wide') }}</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </x-forms.select>
                        </x-forms.field>
                        <x-forms.checkbox name="is_recurring" value="1" :label="__('Recurring')" />
                        <div class="flex items-end">
                            <x-ui.button type="submit" variant="primary" size="sm">{{ __('Add Holiday') }}</x-ui.button>
                        </div>
                    </form>
                </x-entity.section>
            </x-ui.card>
        @endcan

        <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
            @foreach ($holidays as $holiday)
                <tr class="hover:bg-surface-muted/60 transition">
                    <td class="px-4 py-3 text-sm font-medium text-ink-heading">{{ $holiday->name }}</td>
                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $holiday->holiday_date->format('M j, Y') }}</td>
                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $holiday->branch?->name ?? __('Organization-wide') }}</td>
                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $holiday->is_recurring ? __('Yes') : __('No') }}</td>
                    <td class="px-4 py-3">
                        @can('delete', $holiday)
                            <form method="POST" action="{{ route('hrms.holidays.destroy', $holiday) }}">
                                @csrf
                                @method('DELETE')
                                <x-ui.button type="submit" variant="ghost" size="sm">{{ __('Delete') }}</x-ui.button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @endforeach
        </x-tables.table>
        <div class="mt-4">{{ $holidays->links() }}</div>
    </x-layouts.entity-listing>
</x-app-layout>
