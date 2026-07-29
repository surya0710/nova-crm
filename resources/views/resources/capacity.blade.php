<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Resource Allocation')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Capacity'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="mb-4 inline-flex rounded-lg border border-slate-200 p-0.5 bg-white">
            @foreach (['daily' => __('Daily'), 'weekly' => __('Weekly'), 'monthly' => __('Monthly')] as $key => $label)
                <a href="{{ route('resources.capacity', array_merge(request()->except(['period', 'from', 'to']), ['period' => $key])) }}"
                   class="px-3 py-1.5 text-sm rounded-md {{ ($period ?? 'weekly') === $key ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-slate-600 hover:text-slate-900' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-4 sm:p-5 mb-6">
            <form method="GET" action="{{ route('resources.capacity') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <input type="hidden" name="period" value="{{ $period ?? 'weekly' }}">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">{{ __('From') }}</label>
                    <x-text-input type="date" name="from" :value="$from->toDateString()" class="w-full" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">{{ __('To') }}</label>
                    <x-text-input type="date" name="to" :value="$to->toDateString()" class="w-full" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">{{ __('Project') }}</label>
                    <select name="project_id" class="w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm">
                        <option value="">{{ __('All projects') }}</option>
                        @foreach ($projects ?? [] as $project)
                            <option value="{{ $project->id }}" @selected(($filters['project_id'] ?? null) == $project->id)>{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">{{ __('Department ID') }}</label>
                    <x-text-input type="number" name="department_id" :value="$filters['department_id'] ?? ''" class="w-full" min="1" />
                </div>
                <div class="flex items-end">
                    <x-primary-button type="submit">{{ __('Apply') }}</x-primary-button>
                </div>
            </form>
        </div>

        @if (! empty($charts))
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
                <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
                    <h3 class="text-sm font-semibold text-slate-900 mb-3">{{ __('Tasks per employee') }}</h3>
                    <ul class="space-y-2 text-sm">
                        @foreach (array_slice($charts['tasks_per_employee'] ?? [], 0, 8) as $row)
                            <li class="flex justify-between gap-2">
                                <span class="text-slate-600 truncate">{{ $row['employee'] }}</span>
                                <span class="font-medium tabular-nums">{{ $row['tasks'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
                    <h3 class="text-sm font-semibold text-slate-900 mb-3">{{ __('Hours per employee') }}</h3>
                    <ul class="space-y-2 text-sm">
                        @foreach (array_slice($charts['hours_per_employee'] ?? [], 0, 8) as $row)
                            <li class="flex justify-between gap-2">
                                <span class="text-slate-600 truncate">{{ $row['employee'] }}</span>
                                <span class="font-medium tabular-nums">{{ number_format($row['estimated'], 1) }}h</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
                    <h3 class="text-sm font-semibold text-slate-900 mb-3">{{ __('Remaining workload') }}</h3>
                    <ul class="space-y-2 text-sm">
                        @foreach (array_slice($charts['remaining_workload'] ?? [], 0, 8) as $row)
                            <li class="flex justify-between gap-2">
                                <span class="text-slate-600 truncate">{{ $row['employee'] }}</span>
                                <span class="font-medium tabular-nums">{{ number_format($row['remaining_hours'], 1) }}h</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            @if ($team->isEmpty())
                <div class="p-12 text-center text-sm text-slate-500">{{ __('No employees found for capacity calculation.') }}</div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Employee') }}</th>
                                <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Projects') }}</th>
                                <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Tasks') }}</th>
                                <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Est. Hours') }}</th>
                                <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Logged') }}</th>
                                <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Remaining') }}</th>
                                <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Capacity') }}</th>
                                <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($team as $row)
                                @php
                                    $employee = $employees->get($row['employee_id']);
                                    $status = $row['status'] ?? 'optimal';
                                    $badge = match ($status) {
                                        'overallocated' => 'bg-red-100 text-red-800',
                                        'underutilized' => 'bg-sky-100 text-sky-800',
                                        default => 'bg-emerald-100 text-emerald-800',
                                    };
                                @endphp
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3">
                                        @if ($employee)
                                            <a href="{{ route('resources.employees.workload', $employee) }}" class="font-medium text-primary-600 hover:text-primary-700">
                                                {{ $row['employee_name'] ?? $employee->full_name }}
                                            </a>
                                        @else
                                            {{ $row['employee_name'] ?? '#'.$row['employee_id'] }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">{{ $row['active_projects'] ?? 0 }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ $row['active_tasks'] ?? 0 }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ number_format((float) ($row['estimated_hours'] ?? 0), 1) }}h</td>
                                    <td class="px-4 py-3 text-slate-700">{{ number_format((float) ($row['logged_hours'] ?? 0), 1) }}h</td>
                                    <td class="px-4 py-3 text-slate-700">{{ number_format((float) ($row['remaining_hours'] ?? 0), 1) }}h</td>
                                    <td class="px-4 py-3 text-slate-700 min-w-[10rem]">
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1 h-2 rounded-full bg-slate-100 overflow-hidden">
                                                @php $util = min(100, (float) ($row['capacity_percentage'] ?? $row['utilization'] ?? 0)); @endphp
                                                <div class="h-full rounded-full {{ $status === 'overallocated' ? 'bg-red-500' : ($status === 'underutilized' ? 'bg-sky-400' : 'bg-emerald-500') }}" style="width: {{ max(3, $util) }}%"></div>
                                            </div>
                                            <span class="tabular-nums w-14 text-right">{{ number_format((float) ($row['capacity_percentage'] ?? $row['utilization'] ?? 0), 0) }}%</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badge }}">
                                            {{ $row['display_status'] ?? config('resources.utilization_statuses.'.$status, ucfirst($status)) }}
                                            @if (! empty($row['overallocated']))
                                                · {{ __('Overallocated') }}
                                            @endif
                                        </span>
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
