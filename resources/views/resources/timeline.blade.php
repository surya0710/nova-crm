<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Timeline')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Timeline'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>


    <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-4 sm:p-5 mb-6">
        <form method="GET" action="{{ route('resources.timeline') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <div>
                <x-text-input type="date" name="from" :value="$from->toDateString()" class="w-full" />
            </div>
            <div>
                <x-text-input type="date" name="to" :value="$to->toDateString()" class="w-full" />
            </div>
            <select name="employee_id" class="border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm">
                <option value="">{{ __('All employees') }}</option>
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}" @selected(($filters['employee_id'] ?? '') == $employee->id)>{{ $employee->full_name }}</option>
                @endforeach
            </select>
            <select name="project_id" class="border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm">
                <option value="">{{ __('All projects') }}</option>
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}" @selected(($filters['project_id'] ?? '') == $project->id)>{{ $project->name }}</option>
                @endforeach
            </select>
            <x-primary-button type="submit">{{ __('Filter') }}</x-primary-button>
        </form>
    </div>

    @if (! empty($timelines))
        <div class="space-y-4 mb-6">
            @foreach ($timelines as $timeline)
                <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">{{ $timeline['employee_name'] }}</h3>
                            <p class="text-xs text-slate-500">
                                {{ __('Free capacity') }}: {{ number_format($timeline['free_capacity_hours'], 1) }}h ·
                                {{ $timeline['display_status'] }} ({{ number_format($timeline['capacity_percentage'], 0) }}%)
                            </p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 text-sm">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">{{ __('Current tasks') }}</p>
                            <ul class="space-y-1">
                                @forelse ($timeline['current_tasks'] as $task)
                                    <li><a href="{{ $task['url'] }}" class="text-primary-600 hover:text-primary-700">{{ $task['title'] }}</a></li>
                                @empty
                                    <li class="text-slate-400">{{ __('None') }}</li>
                                @endforelse
                            </ul>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">{{ __('Future tasks') }}</p>
                            <ul class="space-y-1">
                                @forelse ($timeline['future_tasks'] as $task)
                                    <li><a href="{{ $task['url'] }}" class="text-primary-600 hover:text-primary-700">{{ $task['title'] }}</a></li>
                                @empty
                                    <li class="text-slate-400">{{ __('None') }}</li>
                                @endforelse
                            </ul>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">{{ __('Leave') }}</p>
                            <ul class="space-y-1">
                                @forelse ($timeline['leave'] as $leave)
                                    <li class="text-slate-600">{{ $leave['type'] ?? __('Leave') }} · {{ $leave['start_date'] }} → {{ $leave['end_date'] }}</li>
                                @empty
                                    <li class="text-slate-400">{{ __('None') }}</li>
                                @endforelse
                            </ul>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">{{ __('Free capacity') }}</p>
                            <p class="text-2xl font-semibold text-slate-900">{{ number_format($timeline['free_capacity_hours'], 1) }}h</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        @if ($allocations->isEmpty())
            <div class="p-12 text-center text-sm text-slate-500">{{ __('No allocations found.') }}</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Start') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('End') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Employee') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Project') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('%') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($allocations as $allocation)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 text-slate-700">{{ $allocation->planned_start_date->toDateString() }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $allocation->planned_end_date->toDateString() }}</td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('resources.allocations.show', $allocation) }}" class="text-primary-600 hover:text-primary-700 font-medium">
                                        {{ $allocation->employee?->full_name }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-slate-700">{{ $allocation->project?->name ?? $allocation->allocation_type_label }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $allocation->allocation_percentage }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-slate-200">{{ $allocations->links() }}</div>
        @endif
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
