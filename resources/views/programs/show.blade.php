<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail :title="__('Details Programs')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Details Programs'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>


    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 space-y-6">
            <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="font-semibold text-slate-900">{{ __('Program Details') }}</h3>
                </div>
                <dl class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Description') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900 whitespace-pre-wrap">{{ $program->description ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Portfolio') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">
                            @if ($program->portfolio)
                                <a href="{{ route('portfolios.show', $program->portfolio) }}" class="text-primary-600 hover:text-primary-700">{{ $program->portfolio->name }}</a>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Manager') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $program->manager?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Start Date') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $program->start_date?->format('M j, Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Target End') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $program->target_end_date?->format('M j, Y') ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                    <h3 class="font-semibold text-slate-900">{{ __('Projects') }}</h3>
                    @can('attachProject', $program)
                        <details class="relative">
                            <summary class="list-none cursor-pointer text-sm font-medium text-primary-600 hover:text-primary-700">{{ __('Attach Project') }}</summary>
                            <div class="absolute right-0 mt-2 z-20 w-72 rounded-xl border border-slate-200 bg-white shadow-lg p-4">
                                <form method="POST" action="{{ route('programs.projects.attach', $program) }}" class="space-y-3">
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
                @if ($program->projects->isEmpty())
                    <div class="p-8 text-center text-sm text-slate-500">{{ __('No projects in this program yet.') }}</div>
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
                                @foreach ($program->projects as $project)
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="px-6 py-4">
                                            <a href="{{ route('projects.show', $project) }}" class="text-sm font-medium text-slate-900 hover:text-indigo-700">{{ $project->name }}</a>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-slate-600">{{ $project->status?->name ?? '—' }}</td>
                                        <td class="px-6 py-4 text-sm text-slate-600">{{ $project->completion_percentage ?? 0 }}%</td>
                                        <td class="px-6 py-4 text-right">
                                            @can('attachProject', $program)
                                                <form method="POST" action="{{ route('programs.projects.detach', [$program, $project]) }}" class="inline" onsubmit="return confirm('{{ __('Detach this project?') }}')">
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
        </div>

        <div class="space-y-6">
            <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-6">
                <h3 class="text-sm font-semibold text-slate-900">{{ __('Summary') }}</h3>
                <dl class="mt-4 space-y-3">
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Projects') }}</dt>
                        <dd class="text-2xl font-bold text-slate-900">{{ $program->projects->count() }}</dd>
                    </div>
                </dl>
            </div>
            <a href="{{ route('programs.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-primary-600 hover:text-primary-700">
                ← {{ __('Back to programs') }}
            </a>
        </div>
    </div>
    </x-layouts.entity-detail>
</x-app-layout>
