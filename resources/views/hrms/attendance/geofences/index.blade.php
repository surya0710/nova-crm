@php
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [
        __('Name'),
        __('Branch'),
        __('Coordinates'),
        __('Radius'),
        __('Effective'),
        __('Status'),
        __('Actions'),
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Attendance Geofences')"
        :subtitle="__('Organization and branch attendance geofence boundaries')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('attendance.label'), 'href' => route('hrms.attendance.index')],
                ['label' => __('Geofences'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @can('create', \App\Models\AttendanceGeofence::class)
            <x-ui.card class="mb-6">
                <x-entity.section :title="__('Add geofence')">
                    <form method="POST" action="{{ route('hrms.attendance.geofences.store') }}" class="grid grid-cols-1 gap-3 md:grid-cols-3">
                        @csrf
                        <x-forms.field :label="__('Name')" name="name">
                            <x-forms.input name="name" required />
                        </x-forms.field>
                        <x-forms.field :label="__('Branch')" name="branch_id">
                            <x-forms.select name="branch_id">
                                <option value="">{{ __('Organization-wide') }}</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </x-forms.select>
                        </x-forms.field>
                        <x-forms.field :label="__('Radius (meters)')" name="radius_meters">
                            <x-forms.input name="radius_meters" type="number" min="10" max="50000" value="100" required />
                        </x-forms.field>
                        <x-forms.field :label="__('Latitude')" name="latitude">
                            <x-forms.input name="latitude" type="number" step="any" required />
                        </x-forms.field>
                        <x-forms.field :label="__('Longitude')" name="longitude">
                            <x-forms.input name="longitude" type="number" step="any" required />
                        </x-forms.field>
                        <x-forms.checkbox name="is_active" value="1" checked :label="__('Active')" />
                        <x-forms.field :label="__('Effective from')" name="effective_from">
                            <x-forms.input name="effective_from" type="date" />
                        </x-forms.field>
                        <x-forms.field :label="__('Effective to')" name="effective_to">
                            <x-forms.input name="effective_to" type="date" />
                        </x-forms.field>
                        <div class="flex items-end">
                            <x-ui.button type="submit" variant="primary" size="sm">{{ __('Add Geofence') }}</x-ui.button>
                        </div>
                    </form>
                </x-entity.section>
            </x-ui.card>
        @endcan

        <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
            @foreach ($geofences as $geofence)
                <tr class="hover:bg-surface-muted/60 transition">
                    <td class="px-4 py-3 text-sm font-medium text-ink-heading">{{ $geofence->name }}</td>
                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $geofence->branch?->name ?? __('Organization-wide') }}</td>
                    <td class="px-4 py-3 text-sm text-ink-muted">{{ number_format($geofence->latitude, 5) }}, {{ number_format($geofence->longitude, 5) }}</td>
                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $geofence->radius_meters }} m</td>
                    <td class="px-4 py-3 text-sm text-ink-muted">
                        {{ $geofence->effective_from?->format('M j, Y') ?? '—' }}
                        →
                        {{ $geofence->effective_to?->format('M j, Y') ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $geofence->is_active ? __('Active') : __('Inactive') }}</td>
                    <td class="px-4 py-3">
                        @can('delete', $geofence)
                            <form method="POST" action="{{ route('hrms.attendance.geofences.destroy', $geofence) }}">
                                @csrf
                                @method('DELETE')
                                <x-ui.button type="submit" variant="ghost" size="sm">{{ __('Delete') }}</x-ui.button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @endforeach
        </x-tables.table>
        <div class="mt-4">{{ $geofences->links() }}</div>
    </x-layouts.entity-listing>
</x-app-layout>
