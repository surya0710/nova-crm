@php
    $healthColors = [
        'on_track' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
        'at_risk' => 'bg-amber-100 text-amber-800 border-amber-200',
        'delayed' => 'bg-red-100 text-red-800 border-red-200',
        'completed' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
        'archived' => 'bg-slate-100 text-slate-600 border-slate-200',
    ];
    $statusClass = $snapshot ? ($healthColors[$snapshot->health_status] ?? 'bg-slate-100 text-slate-600 border-slate-200') : '';
    $statsOnly = $statisticsOnly ?? false;
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail :title="__('Dashboard')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Dashboard'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        @if ($snapshot)
            <div class="lg:col-span-2 rounded-xl bg-white border border-slate-200 shadow-sm p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Health') }}</p>
                <div class="mt-2 flex items-center gap-3 flex-wrap">
                    <span class="inline-flex text-sm font-semibold px-3 py-1 rounded-full border {{ $statusClass }}">{{ $snapshot->health_status_label }}</span>
                    <span class="text-2xl font-bold text-primary-600">{{ $snapshot->completion_percentage }}%</span>
                </div>
                <div class="mt-3 w-full bg-slate-100 rounded-full h-2">
                    <div class="bg-primary-600 h-2 rounded-full" style="width: {{ min(100, $snapshot->completion_percentage) }}%"></div>
                </div>
            </div>
        @endif
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Open Tasks') }}</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ $statistics['tasks']['open'] ?? 0 }}</p>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Completed Tasks') }}</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ $statistics['tasks']['closed'] ?? 0 }}</p>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Delayed Tasks') }}</p>
            <p class="mt-1 text-2xl font-bold {{ ($statistics['tasks']['overdue'] ?? 0) > 0 ? 'text-red-600' : 'text-slate-900' }}">{{ $statistics['tasks']['overdue'] ?? 0 }}</p>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Hours Logged') }}</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format((float) ($statistics['hours']['actual'] ?? 0), 1) }}</p>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Remaining Hours') }}</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format((float) ($statistics['hours']['remaining'] ?? 0), 1) }}</p>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Team Capacity') }}</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format((float) ($teamCapacity ?? ($snapshot?->metadata['metrics']['team_capacity_percentage'] ?? 0)), 0) }}%</p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 space-y-6">
            <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                    <h3 class="font-semibold text-slate-900">{{ __('Milestone Progress') }}</h3>
                    <a href="{{ route('projects.milestones.index', $project) }}" class="text-xs text-primary-600 hover:text-primary-700">{{ __('Manage') }}</a>
                </div>
                @if (empty($milestoneProgress))
                    <div class="p-8 text-center text-sm text-slate-500">{{ __('No milestones defined.') }}</div>
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach ($milestoneProgress as $row)
                            <div class="px-6 py-4">
                                <div class="flex items-center justify-between gap-2 mb-2">
                                    <p class="text-sm font-medium text-slate-900">{{ $row['name'] }}</p>
                                    @if ($row['is_delayed'])
                                        <span class="text-xs font-medium text-red-600">{{ __('Delayed :days days', ['days' => $row['delay_days']]) }}</span>
                                    @endif
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-xs text-slate-500 mb-2">
                                    <span>{{ __('Planned') }}: {{ $row['planned_progress'] }}%</span>
                                    <span>{{ __('Actual') }}: {{ $row['actual_progress'] }}%</span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-2">
                                    <div class="bg-primary-600 h-2 rounded-full" style="width: {{ min(100, $row['actual_progress']) }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            @if (! $statsOnly && ! empty($timeline['milestones']))
                <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                        <h3 class="font-semibold text-slate-900">{{ __('Timeline Snippet') }}</h3>
                        <a href="{{ route('projects.timeline', $project) }}" class="text-xs text-primary-600 hover:text-primary-700">{{ __('Full timeline') }}</a>
                    </div>
                    <ol class="relative border-l border-slate-200 ml-9 my-6 space-y-6 mr-6">
                        @foreach (array_slice($timeline['milestones'], 0, 5) as $milestone)
                            <li class="ml-4">
                                <span class="absolute -left-1.5 flex h-3 w-3 rounded-full bg-indigo-500 ring-4 ring-white"></span>
                                <p class="text-sm font-medium text-slate-900">{{ $milestone['name'] }}</p>
                                <p class="text-xs text-slate-500">{{ $milestone['due_date'] ?? '—' }} · {{ ucfirst(str_replace('_', ' ', $milestone['status'])) }}</p>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endif

            @if (! $statsOnly && ($overdueTasks->isNotEmpty() || $delayedMilestones->isNotEmpty()))
                <div class="rounded-xl bg-white border border-red-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-red-100 bg-red-50/50">
                        <h3 class="font-semibold text-red-900">{{ __('Risks') }}</h3>
                    </div>
                    <div class="p-6 space-y-3 text-sm">
                        @if ($overdueTasks->isNotEmpty())
                            <p class="font-medium text-red-800">{{ __(':count overdue tasks', ['count' => $overdueTasks->count()]) }}</p>
                            <ul class="list-disc list-inside text-slate-600 space-y-1">
                                @foreach ($overdueTasks->take(5) as $task)
                                    <li>{{ $task->title }}</li>
                                @endforeach
                            </ul>
                        @endif
                        @if ($delayedMilestones->isNotEmpty())
                            <p class="font-medium text-amber-800">{{ __(':count delayed milestones', ['count' => $delayedMilestones->count()]) }}</p>
                            <ul class="list-disc list-inside text-slate-600 space-y-1">
                                @foreach ($delayedMilestones->take(5) as $milestone)
                                    <li>{{ $milestone->name }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
                <h3 class="text-sm font-semibold text-slate-900 mb-3">{{ __('Statistics') }}</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">{{ __('Total tasks') }}</dt><dd class="font-medium">{{ $statistics['tasks']['total'] ?? 0 }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">{{ __('Closed') }}</dt><dd class="font-medium">{{ $statistics['tasks']['closed'] ?? 0 }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">{{ __('Velocity (14d)') }}</dt><dd class="font-medium">{{ $statistics['velocity']['completed_count'] ?? 0 }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">{{ __('Hours logged') }}</dt><dd class="font-medium">{{ $statistics['hours']['logged'] ?? 0 }}</dd></div>
                    @if ($statistics['average_task_duration_days'] ?? null)
                        <div class="flex justify-between"><dt class="text-slate-500">{{ __('Avg task duration') }}</dt><dd class="font-medium">{{ $statistics['average_task_duration_days'] }}d</dd></div>
                    @endif
                </dl>
            </div>

            @if (! $statsOnly && $project->members->isNotEmpty())
                <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
                    <h3 class="text-sm font-semibold text-slate-900 mb-3">{{ __('Team') }}</h3>
                    <ul class="space-y-2">
                        @foreach ($project->members->take(8) as $member)
                            <li class="text-sm text-slate-700">{{ $member->user?->name ?? __('Unknown') }} <span class="text-xs text-slate-400">({{ $member->project_role_label }})</span></li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (! $statsOnly && $project->progressUpdates->isNotEmpty())
                <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
                    <h3 class="text-sm font-semibold text-slate-900 mb-3">{{ __('Recent Activity') }}</h3>
                    <ul class="space-y-3">
                        @foreach ($project->progressUpdates as $update)
                            <li class="text-xs">
                                <p class="font-medium text-slate-900">{{ $update->progress_percentage }}% — {{ Str::limit($update->summary, 60) }}</p>
                                <p class="text-slate-400">{{ $update->updater?->name }} · {{ $update->created_at?->diffForHumans() }}</p>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
    </x-layouts.entity-detail>
</x-app-layout>
