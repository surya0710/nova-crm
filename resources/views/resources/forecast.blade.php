<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Forecast')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Forecast'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>


    <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-4 sm:p-5 mb-6">
        <form method="GET" action="{{ route('resources.forecast') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
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

    @php $summary = $forecast['summary'] ?? []; @endphp
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-4">
            <p class="text-xs text-slate-500">{{ __('Employees') }}</p>
            <p class="mt-1 text-xl font-semibold">{{ $summary['employee_count'] ?? 0 }}</p>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-4">
            <p class="text-xs text-slate-500">{{ __('Available hours') }}</p>
            <p class="mt-1 text-xl font-semibold">{{ number_format((float) ($summary['total_available_hours'] ?? 0), 1) }}</p>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-4">
            <p class="text-xs text-slate-500">{{ __('Forecast load') }}</p>
            <p class="mt-1 text-xl font-semibold">{{ number_format((float) ($summary['total_forecast_load_hours'] ?? 0), 1) }}</p>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-4">
            <p class="text-xs text-slate-500">{{ __('At risk') }}</p>
            <p class="mt-1 text-xl font-semibold text-red-600">{{ count($risks) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200">
                <h2 class="text-sm font-semibold text-slate-900">{{ __('Employee forecast') }}</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Employee') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Load') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($forecast['employees'] ?? [] as $row)
                            <tr>
                                <td class="px-4 py-3 text-slate-800">{{ $employees->get($row['employee_id'])?->full_name ?? '#'.$row['employee_id'] }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ number_format((float) ($row['forecast_load_hours'] ?? 0), 1) }}h / {{ number_format((float) ($row['available_hours'] ?? 0), 1) }}h</td>
                                <td class="px-4 py-3 text-slate-700">{{ config('resources.utilization_statuses.'.($row['status'] ?? ''), $row['status'] ?? '—') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200">
                <h2 class="text-sm font-semibold text-slate-900">{{ __('Upcoming risks') }}</h2>
            </div>
            @if (empty($risks))
                <div class="p-8 text-center text-sm text-slate-500">{{ __('No capacity risks detected.') }}</div>
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach ($risks as $risk)
                        <li class="px-5 py-3 text-sm">
                            <p class="font-medium text-slate-900">{{ $employees->get($risk['employee_id'])?->full_name ?? '#'.$risk['employee_id'] }}</p>
                            <p class="text-slate-500">{{ number_format((float) ($risk['utilization'] ?? 0), 1) }}% · {{ $risk['from'] }} → {{ $risk['to'] }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
