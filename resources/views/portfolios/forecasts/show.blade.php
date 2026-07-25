<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail :title="__('Details Forecasts')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Details Forecasts'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Likely Delays') }}</p>
            <p class="mt-1 text-3xl font-bold {{ ($forecast['delayed_project_count'] ?? 0) > 0 ? 'text-amber-600' : 'text-slate-900' }}">{{ $forecast['delayed_project_count'] ?? 0 }}</p>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Budget Overruns') }}</p>
            <p class="mt-1 text-3xl font-bold {{ ($forecast['overrun_project_count'] ?? 0) > 0 ? 'text-red-600' : 'text-slate-900' }}">{{ $forecast['overrun_project_count'] ?? 0 }}</p>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Avg. Risk Score') }}</p>
            <p class="mt-1 text-3xl font-bold text-slate-900">{{ $forecast['average_risk_score'] ?? 0 }}</p>
        </div>
    </div>

    @if (! empty($forecast['portfolio_capacity']))
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-6 mb-6">
            <h3 class="text-sm font-semibold text-slate-900 mb-4">{{ __('Capacity Overview') }}</h3>
            <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                @foreach ($forecast['portfolio_capacity'] as $key => $value)
                    @if (! is_array($value))
                        <div>
                            <dt class="text-xs text-slate-500">{{ ucwords(str_replace('_', ' ', $key)) }}</dt>
                            <dd class="text-sm font-semibold text-slate-900">{{ is_numeric($value) ? number_format($value, is_float($value) ? 1 : 0) : $value }}</dd>
                        </div>
                    @endif
                @endforeach
            </dl>
        </div>
    @endif

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
            <h3 class="font-semibold text-slate-900">{{ __('Project Forecasts') }}</h3>
        </div>
        @if (empty($forecast['projects']))
            <div class="p-8 text-center text-sm text-slate-500">{{ __('No project forecasts available.') }}</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Project') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Est. Completion') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Delay Risk') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Budget Overrun') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Risk Score') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($forecast['projects'] as $projectForecast)
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-6 py-4">
                                    @if (! empty($projectForecast['project_id']))
                                        <a href="{{ route('projects.show', $projectForecast['project_id']) }}" class="text-sm font-medium text-slate-900 hover:text-indigo-700">#{{ $projectForecast['project_id'] }}</a>
                                    @else
                                        <span class="text-sm text-slate-500">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ $projectForecast['estimated_completion'] ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    @if ($projectForecast['likely_delay']['is_likely'] ?? false)
                                        <span class="inline-flex text-xs font-medium px-2 py-0.5 rounded-full bg-amber-100 text-amber-800">{{ __('Likely') }}</span>
                                    @else
                                        <span class="inline-flex text-xs font-medium px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800">{{ __('On Track') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if ($projectForecast['budget_overrun']['is_likely'] ?? false)
                                        <span class="inline-flex text-xs font-medium px-2 py-0.5 rounded-full bg-red-100 text-red-800">{{ __('Likely') }}</span>
                                    @else
                                        <span class="inline-flex text-xs font-medium px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800">{{ __('Within Budget') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ $projectForecast['risk_forecast']['score'] ?? 0 }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    </x-layouts.entity-detail>
</x-app-layout>
