<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Resource planner')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Resource planner'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>


    <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-4 sm:p-5 mb-6">
        <form method="GET" action="{{ route('resources.planner') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        @php
            $over = collect($team)->where('status', 'overallocated')->count();
            $under = collect($team)->where('status', 'underutilized')->count();
            $optimal = collect($team)->where('status', 'optimal')->count();
        @endphp
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Overallocated') }}</p>
            <p class="mt-2 text-2xl font-semibold text-red-600">{{ $over }}</p>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Optimal') }}</p>
            <p class="mt-2 text-2xl font-semibold text-emerald-600">{{ $optimal }}</p>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Underutilized') }}</p>
            <p class="mt-2 text-2xl font-semibold text-amber-600">{{ $under }}</p>
        </div>
    </div>

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200">
            <h2 class="text-sm font-semibold text-slate-900">{{ __('Allocations in range') }}</h2>
        </div>
        @if ($allocations->isEmpty())
            <x-ui.card>
                <x-ui.empty-state-preset variant="resources" />
            </x-ui.card>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Employee') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Project / Type') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('%') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Dates') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($allocations as $allocation)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('resources.allocations.show', $allocation) }}" class="font-medium text-primary-600 hover:text-primary-700">
                                        {{ $allocation->employee?->full_name }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    {{ $allocation->project?->name ?? $allocation->allocation_type_label }}
                                </td>
                                <td class="px-4 py-3 text-slate-700">{{ $allocation->allocation_percentage }}%</td>
                                <td class="px-4 py-3 text-slate-500">
                                    {{ $allocation->planned_start_date->toDateString() }} → {{ $allocation->planned_end_date->toDateString() }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
