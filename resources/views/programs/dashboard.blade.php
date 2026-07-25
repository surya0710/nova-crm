<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail :title="__('Dashboard')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Dashboard'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Projects') }}</p>
            <p class="mt-1 text-3xl font-bold text-slate-900">{{ $program->projects->count() }}</p>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Avg. Completion') }}</p>
            <p class="mt-1 text-3xl font-bold text-primary-600">{{ $program->projects->isEmpty() ? 0 : round($program->projects->avg(fn ($p) => $p->completion_percentage ?? 0), 1) }}%</p>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Status') }}</p>
            <p class="mt-1 text-lg font-bold text-slate-900">{{ config('projects.program_statuses')[$program->status] ?? $program->status }}</p>
        </div>
    </div>

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
            <h3 class="font-semibold text-slate-900">{{ __('Project Progress') }}</h3>
        </div>
        @if ($program->projects->isEmpty())
            <div class="p-8 text-center text-sm text-slate-500">{{ __('No projects in this program.') }}</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Project') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Status') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Completion') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Planned End') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($program->projects as $project)
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-6 py-4 text-sm font-medium text-slate-900">{{ $project->name }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ $project->status?->name ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 h-2 rounded-full bg-slate-100 max-w-[120px]">
                                            <div class="h-full rounded-full bg-primary-600" style="width: {{ min(100, $project->completion_percentage ?? 0) }}%"></div>
                                        </div>
                                        <span class="text-sm text-slate-600">{{ $project->completion_percentage ?? 0 }}%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ $project->planned_end_date?->format('M j, Y') ?? '—' }}</td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('projects.show', $project) }}" class="text-sm text-primary-600 hover:text-primary-700">{{ __('View') }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    </x-layouts.entity-detail>
</x-app-layout>
