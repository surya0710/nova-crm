<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Performance Cycles')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Performance'), 'href' => route('hrms.performance.index')],
                ['label' => __('Performance Cycles'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @if ($activeCycle)
        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4 mb-6 text-sm">
            {{ __('Active cycle:') }} <span class="font-medium">{{ $activeCycle->name }}</span>
            ({{ $activeCycle->start_date->toDateString() }} – {{ $activeCycle->end_date->toDateString() }})
        </div>
    @endif
    @can('create', \App\Models\PerformanceCycle::class)
    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4 mb-6">
        <form method="POST" action="{{ route('hrms.performance.cycles.store') }}" class="grid grid-cols-1 md:grid-cols-6 gap-3">
            @csrf
            <x-forms.input name="name" placeholder="{{ __('Name') }}" required  />
            <select name="cycle_type" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                @foreach ($cycleTypes as $value => $label)
                    <option value="{{ $value }}">{{ __($label) }}</option>
                @endforeach
            </select>
            <x-forms.input name="start_date" type="date" required  />
            <x-forms.input name="end_date" type="date" required  />
            <select name="status" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500">
                <option value="draft">{{ __('Draft') }}</option>
                <option value="scheduled">{{ __('Scheduled') }}</option>
            </select>
            <x-ui.button type="submit" variant="primary" size="sm">{{ __('Add Cycle') }}</x-ui.button>
        </form>
    </div>
    @endcan
    <div class="rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-3 text-left">{{ __('Name') }}</th>
                    <th class="p-3 text-left">{{ __('Type') }}</th>
                    <th class="p-3 text-left">{{ __('Status') }}</th>
                    <th class="p-3 text-left">{{ __('Dates') }}</th>
                    <th class="p-3 text-left">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($cycles as $cycle)
                <tr class="border-t">
                    <td class="p-3">{{ $cycle->name }}</td>
                    <td class="p-3">{{ $cycleTypes[$cycle->cycle_type] ?? $cycle->cycle_type }}</td>
                    <td class="p-3">{{ $cycleStatuses[$cycle->status] ?? $cycle->status }}</td>
                    <td class="p-3">{{ $cycle->start_date->toDateString() }} – {{ $cycle->end_date->toDateString() }}</td>
                    <td class="p-3 space-x-2">
                        @can('update', $cycle)
                            @if (in_array($cycle->status, ['draft', 'scheduled'], true))
                                <form method="POST" action="{{ route('hrms.performance.cycles.activate', $cycle) }}" class="inline">@csrf <button class="text-slate-700 underline">{{ __('Activate') }}</button></form>
                            @endif
                            @if ($cycle->status === 'active')
                                <form method="POST" action="{{ route('hrms.performance.cycles.close', $cycle) }}" class="inline">@csrf <button class="text-slate-700 underline">{{ __('Close') }}</button></form>
                            @endif
                            @if ($cycle->status === 'closed')
                                <form method="POST" action="{{ route('hrms.performance.cycles.archive', $cycle) }}" class="inline">@csrf <button class="text-slate-700 underline">{{ __('Archive') }}</button></form>
                            @endif
                        @endcan
                        @can('delete', $cycle)
                            @if (in_array($cycle->status, ['draft', 'scheduled'], true))
                                <form method="POST" action="{{ route('hrms.performance.cycles.destroy', $cycle) }}" class="inline">@csrf @method('DELETE') <button class="text-red-600">{{ __('Delete') }}</button></form>
                            @endif
                        @endcan
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="p-4">{{ $cycles->links() }}</div>
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
