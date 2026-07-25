<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Capacity')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Capacity'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>


    <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-4 sm:p-5 mb-6">
        <form method="GET" action="{{ route('resources.capacity') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
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

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        @if ($team->isEmpty())
            <div class="p-12 text-center text-sm text-slate-500">{{ __('No employees found for capacity calculation.') }}</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Employee') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Available') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Allocated') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Utilization') }}</th>
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
                                    'underutilized' => 'bg-amber-100 text-amber-800',
                                    default => 'bg-emerald-100 text-emerald-800',
                                };
                            @endphp
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    @if ($employee)
                                        <a href="{{ route('resources.employees.workload', $employee) }}" class="font-medium text-primary-600 hover:text-primary-700">
                                            {{ $employee->full_name }}
                                        </a>
                                    @else
                                        #{{ $row['employee_id'] }}
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-700">{{ number_format((float) ($row['available'] ?? 0), 1) }}h</td>
                                <td class="px-4 py-3 text-slate-700">{{ number_format((float) ($row['allocated'] ?? 0), 1) }}h</td>
                                <td class="px-4 py-3 text-slate-700 min-w-[10rem]">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 h-2 rounded-full bg-slate-100 overflow-hidden">
                                            @php $util = min(100, (float) ($row['utilization'] ?? 0)); @endphp
                                            <div class="h-full rounded-full {{ $status === 'overallocated' ? 'bg-red-500' : ($status === 'underutilized' ? 'bg-amber-400' : 'bg-emerald-500') }}" style="width: {{ max(3, $util) }}%"></div>
                                        </div>
                                        <span class="tabular-nums w-14 text-right">{{ number_format((float) ($row['utilization'] ?? 0), 1) }}%</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badge }}">
                                        {{ config('resources.utilization_statuses.'.$status, ucfirst($status)) }}
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
