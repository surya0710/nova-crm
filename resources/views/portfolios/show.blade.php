@php
    $healthColors = [
        'on_track' => 'bg-emerald-100 text-emerald-800',
        'at_risk' => 'bg-amber-100 text-amber-800',
        'delayed' => 'bg-red-100 text-red-800',
        'completed' => 'bg-indigo-100 text-indigo-800',
        'archived' => 'bg-slate-100 text-slate-600',
    ];
    $healthBarColors = [
        'on_track' => 'bg-emerald-500',
        'at_risk' => 'bg-amber-500',
        'delayed' => 'bg-red-500',
        'completed' => 'bg-indigo-500',
        'archived' => 'bg-slate-400',
    ];
    $healthStatuses = config('projects.health_statuses', []);
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail :title="__('Details Portfolios')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Details Portfolios'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>


    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Projects') }}</p>
            <p class="mt-1 text-3xl font-bold text-slate-900">{{ $statistics['project_count'] ?? 0 }}</p>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Avg. Completion') }}</p>
            <p class="mt-1 text-3xl font-bold text-primary-600">{{ $statistics['average_completion_percentage'] ?? 0 }}%</p>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Risk Score') }}</p>
            <p class="mt-1 text-3xl font-bold {{ ($statistics['risk_score'] ?? 0) > 50 ? 'text-red-600' : 'text-slate-900' }}">{{ $statistics['risk_score'] ?? 0 }}</p>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Budget Variance') }}</p>
            <p class="mt-1 text-3xl font-bold text-slate-900">{{ number_format($statistics['budget']['variance_total'] ?? 0, 0) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 space-y-6">
            <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="font-semibold text-slate-900">{{ __('Portfolio Details') }}</h3>
                </div>
                <dl class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Description') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900 whitespace-pre-wrap">{{ $portfolio->description ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Owner') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $portfolio->owner?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Programs') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $portfolio->programs->count() }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Start Date') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $portfolio->start_date?->format('M j, Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Target End') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $portfolio->target_end_date?->format('M j, Y') ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                    <h3 class="font-semibold text-slate-900">{{ __('Projects') }}</h3>
                    @can('attachProject', $portfolio)
                        <details class="relative">
                            <summary class="list-none cursor-pointer text-sm font-medium text-primary-600 hover:text-primary-700">{{ __('Attach Project') }}</summary>
                            <div class="absolute right-0 mt-2 z-20 w-72 rounded-xl border border-slate-200 bg-white shadow-lg p-4">
                                <form method="POST" action="{{ route('portfolios.projects.attach', $portfolio) }}" class="space-y-3">
                                    @csrf
                                    <div>
                                        <x-input-label for="project_id" :value="__('Project ID')" />
                                        <x-text-input id="project_id" name="project_id" type="number" class="block mt-1 w-full" :value="old('project_id')" required />
                                        <x-input-error :messages="$errors->get('project_id')" class="mt-2" />
                                    </div>
                                    <x-primary-button type="submit" class="w-full justify-center">{{ __('Attach') }}</x-primary-button>
                                </form>
                            </div>
                        </details>
                    @endcan
                </div>
                @if ($portfolio->projects->isEmpty())
                    <div class="p-8 text-center text-sm text-slate-500">{{ __('No projects in this portfolio yet.') }}</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50/80">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Project') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Status') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Completion') }}</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($portfolio->projects as $project)
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="px-6 py-4">
                                            <a href="{{ route('projects.show', $project) }}" class="text-sm font-medium text-slate-900 hover:text-indigo-700">{{ $project->name }}</a>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-slate-600">{{ $project->status?->name ?? '—' }}</td>
                                        <td class="px-6 py-4 text-sm text-slate-600">{{ $project->completion_percentage ?? 0 }}%</td>
                                        <td class="px-6 py-4 text-right">
                                            @can('attachProject', $portfolio)
                                                <form method="POST" action="{{ route('portfolios.projects.detach', [$portfolio, $project]) }}" class="inline" onsubmit="return confirm('{{ __('Detach this project?') }}')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-sm text-red-600 hover:text-red-800">{{ __('Detach') }}</button>
                                                </form>
                                            @endcan
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            @if ($portfolio->programs->isNotEmpty())
                <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                        <h3 class="font-semibold text-slate-900">{{ __('Programs') }}</h3>
                    </div>
                    <ul class="divide-y divide-slate-100">
                        @foreach ($portfolio->programs as $program)
                            <li class="px-6 py-4 flex items-center justify-between gap-3">
                                <a href="{{ route('programs.show', $program) }}" class="text-sm font-medium text-slate-900 hover:text-indigo-700">{{ $program->name }}</a>
                                <span class="text-xs text-slate-500">{{ config('projects.program_statuses')[$program->status] ?? $program->status }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-6">
                <h3 class="text-sm font-semibold text-slate-900 mb-4">{{ __('Health Distribution') }}</h3>
                @php $health = $statistics['health'] ?? []; $total = max(1, array_sum($health)); @endphp
                <div class="flex h-3 rounded-full overflow-hidden bg-slate-100">
                    @foreach ($healthStatuses as $key => $label)
                        @if (($health[$key] ?? 0) > 0)
                            <div class="{{ $healthBarColors[$key] ?? 'bg-slate-400' }} h-full" style="width: {{ (($health[$key] ?? 0) / $total) * 100 }}%" title="{{ $label }}: {{ $health[$key] ?? 0 }}"></div>
                        @endif
                    @endforeach
                </div>
                <div class="mt-4 space-y-2">
                    @foreach ($healthStatuses as $key => $label)
                        <div class="flex items-center justify-between text-xs">
                            <span class="flex items-center gap-2 text-slate-600">
                                <span class="w-2.5 h-2.5 rounded-full {{ $healthBarColors[$key] ?? 'bg-slate-400' }}"></span>
                                {{ $label }}
                            </span>
                            <span class="font-medium text-slate-900">{{ $health[$key] ?? 0 }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <a href="{{ route('portfolios.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-primary-600 hover:text-primary-700">
                ← {{ __('Back to portfolios') }}
            </a>
        </div>
    </div>
    </x-layouts.entity-detail>
</x-app-layout>
