<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Workload')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Workload'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>


    <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-4 sm:p-5 mb-6">
        <form method="GET" action="{{ route('resources.employees.workload', $employee) }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">{{ __('From') }}</label>
                <x-text-input type="date" name="from" :value="$from->toDateString()" class="w-full" />
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">{{ __('To') }}</label>
                <x-text-input type="date" name="to" :value="$to->toDateString()" class="w-full" />
            </div>
            <div class="flex items-end">
                <x-primary-button type="submit">{{ __('Apply') }}</x-primary-button>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-4">
            <p class="text-xs text-slate-500">{{ __('Capacity') }}</p>
            <p class="mt-1 text-xl font-semibold text-slate-900">{{ number_format((float) $workload['capacity'], 1) }}h</p>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-4">
            <p class="text-xs text-slate-500">{{ __('Available') }}</p>
            <p class="mt-1 text-xl font-semibold text-slate-900">{{ number_format((float) $workload['available'], 1) }}h</p>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-4">
            <p class="text-xs text-slate-500">{{ __('Allocated') }}</p>
            <p class="mt-1 text-xl font-semibold text-slate-900">{{ number_format((float) $workload['allocated'], 1) }}h</p>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-4">
            <p class="text-xs text-slate-500">{{ __('Utilization') }}</p>
            <p class="mt-1 text-xl font-semibold text-slate-900">{{ number_format((float) $workload['utilization'], 1) }}%</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200">
                <h2 class="text-sm font-semibold text-slate-900">{{ __('Allocations') }}</h2>
            </div>
            @if ($allocations->isEmpty())
                <div class="p-8 text-center text-sm text-slate-500">{{ __('No allocations in range.') }}</div>
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach ($allocations as $allocation)
                        <li class="px-5 py-3 text-sm">
                            <a href="{{ route('resources.allocations.show', $allocation) }}" class="font-medium text-primary-600 hover:text-primary-700">
                                {{ $allocation->project?->name ?? $allocation->allocation_type_label }}
                            </a>
                            <p class="text-slate-500">{{ $allocation->allocation_percentage }}% · {{ $allocation->planned_start_date->toDateString() }} → {{ $allocation->planned_end_date->toDateString() }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200">
                <h2 class="text-sm font-semibold text-slate-900">{{ __('Daily breakdown') }}</h2>
            </div>
            <div class="overflow-x-auto max-h-96">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 sticky top-0">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium text-slate-600">{{ __('Date') }}</th>
                            <th class="px-4 py-2 text-left font-medium text-slate-600">{{ __('Allocated') }}</th>
                            <th class="px-4 py-2 text-left font-medium text-slate-600">{{ __('Util.') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($workload['days'] ?? [] as $day)
                            <tr>
                                <td class="px-4 py-2 text-slate-700">{{ $day['date'] }}</td>
                                <td class="px-4 py-2 text-slate-700">{{ number_format((float) $day['allocated_hours'], 1) }}h ({{ $day['allocation_percentage'] }}%)</td>
                                <td class="px-4 py-2 text-slate-700">{{ number_format((float) $day['utilization'], 0) }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
