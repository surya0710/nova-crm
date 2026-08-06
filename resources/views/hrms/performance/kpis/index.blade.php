<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('KPI Library')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Performance'), 'href' => route('hrms.performance.index')],
                ['label' => __('KPI Library'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @can('create', \App\Models\Kpi::class)
    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4 mb-6">
        <form method="POST" action="{{ route('hrms.performance.kpis.store') }}" class="grid grid-cols-1 md:grid-cols-3 gap-3">
            @csrf
            <x-forms.input name="name" placeholder="{{ __('Name') }}" required  />
            <x-forms.input name="code" placeholder="{{ __('Code') }}" required  />
            <x-forms.input name="unit" placeholder="{{ __('Unit') }}"  />
            <select name="measurement_type" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                @foreach ($measurementTypes as $key => $label)
                    <option value="{{ $key }}" @selected($key === 'numeric')>{{ $label }}</option>
                @endforeach
            </select>
            <x-forms.input name="default_target" type="number" step="0.01" placeholder="{{ __('Default Target') }}"  />
            <x-forms.input name="description" placeholder="{{ __('Description') }}"  />
            <x-ui.button type="submit" variant="primary" size="sm">{{ __('Add KPI') }}</x-ui.button>
        </form>
    </div>
    @endcan
    <div class="rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-3 text-left">{{ __('Name') }}</th>
                    <th class="p-3 text-left">{{ __('Code') }}</th>
                    <th class="p-3 text-left">{{ __('Unit') }}</th>
                    <th class="p-3 text-left">{{ __('Measurement') }}</th>
                    <th class="p-3 text-left">{{ __('Target') }}</th>
                    <th class="p-3 text-left">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($kpis as $kpi)
                <tr class="border-t">
                    <td class="p-3">{{ $kpi->name }}</td>
                    <td class="p-3">{{ $kpi->code }}</td>
                    <td class="p-3">{{ $kpi->unit ?? '—' }}</td>
                    <td class="p-3">{{ $measurementTypes[$kpi->measurement_type] ?? $kpi->measurement_type }}</td>
                    <td class="p-3">{{ $kpi->default_target ?? '—' }}</td>
                    <td class="p-3">
                        @can('delete', $kpi)
                        <form method="POST" action="{{ route('hrms.performance.kpis.destroy', $kpi) }}">@csrf @method('DELETE') <button class="text-red-600">{{ __('Delete') }}</button></form>
                        @endcan
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="p-4">{{ $kpis->links() }}</div>
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
