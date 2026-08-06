@php
    $priorityVariant = [
        'low' => 'neutral',
        'medium' => 'warning',
        'high' => 'danger',
        'critical' => 'danger',
    ];
    $milestoneStatusVariant = [
        'pending' => 'neutral',
        'in_progress' => 'info',
        'completed' => 'success',
        'cancelled' => 'neutral',
    ];
    $project->loadMissing('watchers');
    $isWatching = $project->watchers->contains(fn ($w) => (int) $w->user_id === (int) auth()->id());
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="$project->name"
        :subtitle="$project->project_number"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('All projects'), 'href' => route('projects.index')],
                ['label' => $project->name, 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <div class="flex flex-wrap items-center gap-2">
                @can('viewCollaboration', $project)
                    <x-ui.button :href="route('projects.collaboration.show', $project)" variant="secondary" size="sm">{{ __('Collaboration') }}</x-ui.button>
                @endcan
                @can('viewHealth', $project)
                    <x-ui.button :href="route('projects.health.show', $project)" variant="secondary" size="sm">{{ __('Health') }}</x-ui.button>
                @endcan
                @can('viewProgress', $project)
                    <x-ui.button :href="route('projects.progress.index', $project)" variant="secondary" size="sm">{{ __('Progress') }}</x-ui.button>
                @endcan
                @can('viewGantt', $project)
                    <x-ui.button :href="route('projects.gantt.show', $project)" variant="secondary" size="sm">{{ __('Gantt') }}</x-ui.button>
                @endcan
                @can('viewReports', $project)
                    <x-ui.button :href="route('projects.reports.index', $project)" variant="secondary" size="sm">{{ __('Reports') }}</x-ui.button>
                @endcan
                @can('viewBudgets', $project)
                    <x-ui.button :href="route('projects.budgets.show', $project)" variant="secondary" size="sm">{{ __('Budget') }}</x-ui.button>
                @endcan
                @can('viewAny', App\Models\Task::class)
                    <x-ui.button :href="route('projects.tasks.index', $project)" variant="secondary" size="sm">{{ __('Tasks') }}</x-ui.button>
                @endcan
                @can('manageWatchers', $project)
                    @if ($isWatching)
                        <form method="POST" action="{{ route('projects.watch.destroy', $project) }}">
                            @csrf
                            @method('DELETE')
                            <x-ui.button type="submit" variant="ghost" size="sm">{{ __('Unwatch') }}</x-ui.button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('projects.watch.store', $project) }}">
                            @csrf
                            <x-ui.button type="submit" variant="ghost" size="sm">{{ __('Watch') }}</x-ui.button>
                        </form>
                    @endif
                @endcan
                @can('update', $project)
                    <x-ui.button :href="route('projects.edit', $project)" variant="primary" size="sm">{{ __('Edit') }}</x-ui.button>
                @endcan
                @if (! $project->is_archived)
                    @can('archive', $project)
                        <form method="POST" action="{{ route('projects.archive', $project) }}" onsubmit="return confirm('{{ __('Archive this project?') }}')">
                            @csrf
                            <x-ui.button type="submit" variant="danger" size="sm">{{ __('Archive') }}</x-ui.button>
                        </form>
                    @endcan
                @else
                    @can('restore', $project)
                        <form method="POST" action="{{ route('projects.restore', $project) }}">
                            @csrf
                            <x-ui.button type="submit" variant="secondary" size="sm">{{ __('Restore') }}</x-ui.button>
                        </form>
                    @endcan
                @endif
            </div>
        </x-slot:actions>

        <x-slot:tabs>
            <div class="flex flex-wrap items-center gap-2">
                @if ($project->status)
                    <span class="inline-flex text-xs font-medium px-2 py-0.5 rounded-full" style="background-color: {{ $project->status->color }}20; color: {{ $project->status->color }}">
                        {{ $project->status->name }}
                    </span>
                @endif
                <x-ui.badge :variant="$priorityVariant[$project->priority] ?? 'neutral'">{{ $project->priority_label }}</x-ui.badge>
                @if ($project->is_archived)
                    <x-ui.badge variant="neutral">{{ __('Archived') }}</x-ui.badge>
                @endif
                <span class="text-xs text-ink-muted">{{ __('Completion :pct%', ['pct' => $project->completion_percentage ?? 0]) }}</span>
            </div>
        </x-slot:tabs>

        <x-entity.section :title="__('Project details')">
            <x-entity.definition-list>
                <x-entity.definition-item :label="__('Description')" :span="2">
                    <span class="whitespace-pre-wrap">{{ $project->description ?? '—' }}</span>
                </x-entity.definition-item>
                <x-entity.definition-item :label="__('Objective')" :span="2">
                    <span class="whitespace-pre-wrap">{{ $project->objective ?? '—' }}</span>
                </x-entity.definition-item>
                <x-entity.definition-item :label="__('Category')">{{ $project->category?->name ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Type')">{{ $project->projectType?->name ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Lifecycle stage')">{{ $project->lifecycleStage?->name ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Priority')">
                    <x-ui.badge :variant="$priorityVariant[$project->priority] ?? 'neutral'">{{ $project->priority_label }}</x-ui.badge>
                </x-entity.definition-item>
                <x-entity.definition-item :label="__('Client')">
                    @if ($project->client)
                        {{ $project->client->company ?: $project->client->name }}
                    @else
                        —
                    @endif
                </x-entity.definition-item>
                <x-entity.definition-item :label="__('Department')">{{ $project->department?->name ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Owner')">{{ $project->owner?->name ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Manager')">{{ $project->manager?->name ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Start date')">{{ $project->start_date?->format('M j, Y') ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Planned end')">{{ $project->planned_end_date?->format('M j, Y') ?? '—' }}</x-entity.definition-item>
                @can('viewBudget', $project)
                    <x-entity.definition-item :label="__('Estimated budget')">{{ $project->estimated_budget ? number_format($project->estimated_budget, 2) : '—' }}</x-entity.definition-item>
                    <x-entity.definition-item :label="__('Actual budget')">{{ $project->actual_budget ? number_format($project->actual_budget, 2) : '—' }}</x-entity.definition-item>
                @endcan
            </x-entity.definition-list>
        </x-entity.section>

        @include('metadata-fields._runtime_detail', [
            'metadataFields' => $metadataFields ?? collect(),
            'metadataPresenter' => $metadataPresenter ?? app(\App\Services\MetadataFormValuePresenter::class),
            'record' => $project,
        ])

        <x-entity.section :title="__('Team members')" :subtitle="__('People assigned to this project')">
            <x-slot:actions>
                <a href="{{ route('projects.members.index', $project) }}" class="text-sm font-medium text-primary-600 hover:text-primary-700">{{ __('Manage') }}</a>
            </x-slot:actions>
            @if ($project->members->isEmpty())
                <x-ui.empty-state-preset :title="__('No members assigned yet')" class="!py-6" />
            @else
                <ul class="divide-y divide-line -mx-1">
                    @foreach ($project->members->take(5) as $member)
                        <li class="flex items-center justify-between gap-3 py-2.5">
                            <div>
                                <p class="text-sm font-medium text-ink-heading">{{ $member->user?->name ?? '—' }}</p>
                                <p class="text-xs text-ink-muted">{{ $member->project_role_label }}</p>
                            </div>
                            @if (! $member->is_active)
                                <x-ui.badge variant="neutral">{{ __('Inactive') }}</x-ui.badge>
                            @endif
                        </li>
                    @endforeach
                </ul>
                @if ($project->members->count() > 5)
                    <p class="mt-2 text-xs text-ink-muted">{{ __('And :count more…', ['count' => $project->members->count() - 5]) }}</p>
                @endif
            @endif
        </x-entity.section>

        <x-entity.section :title="__('Milestones')" :subtitle="__('Key deliverables and checkpoints')">
            <x-slot:actions>
                <a href="{{ route('projects.milestones.index', $project) }}" class="text-sm font-medium text-primary-600 hover:text-primary-700">{{ __('Manage') }}</a>
            </x-slot:actions>
            @if ($project->milestones->isEmpty())
                <x-ui.empty-state-preset variant="milestones" class="!py-6" />
            @else
                <ul class="divide-y divide-line -mx-1">
                    @foreach ($project->milestones->take(5) as $milestone)
                        <li class="flex items-center justify-between gap-3 py-2.5">
                            <div>
                                <p class="text-sm font-medium text-ink-heading">{{ $milestone->name }}</p>
                                <p class="text-xs text-ink-muted">{{ $milestone->due_date?->format('M j, Y') ?? __('No due date') }}</p>
                            </div>
                            <x-ui.badge :variant="$milestoneStatusVariant[$milestone->status] ?? 'neutral'">{{ $milestone->status_label }}</x-ui.badge>
                        </li>
                    @endforeach
                </ul>
                @if ($project->milestones->count() > 5)
                    <p class="mt-2 text-xs text-ink-muted">{{ __('And :count more…', ['count' => $project->milestones->count() - 5]) }}</p>
                @endif
            @endif
        </x-entity.section>

        <x-slot:aside>
            <x-entity.section :title="__('Progress')">
                <div class="flex items-center justify-between text-sm mb-2">
                    <span class="text-ink-muted">{{ __('Completion') }}</span>
                    <span class="font-semibold text-ink-heading">{{ $project->completion_percentage ?? 0 }}%</span>
                </div>
                <div class="h-2 rounded-full bg-surface-muted overflow-hidden" role="progressbar" aria-valuenow="{{ $project->completion_percentage ?? 0 }}" aria-valuemin="0" aria-valuemax="100">
                    <div class="h-full rounded-full bg-primary-600" style="width: {{ min(100, max(0, $project->completion_percentage ?? 0)) }}%"></div>
                </div>
            </x-entity.section>

            <x-entity.section :title="__('More')">
                <nav class="flex flex-col gap-1.5 text-sm" aria-label="{{ __('Project links') }}">
                    <a href="{{ route('projects.timeline', $project) }}" class="text-primary-600 hover:text-primary-700">{{ __('Timeline') }}</a>
                    <a href="{{ route('projects.members.index', $project) }}" class="text-primary-600 hover:text-primary-700">{{ __('Members') }}</a>
                    <a href="{{ route('projects.milestones.index', $project) }}" class="text-primary-600 hover:text-primary-700">{{ __('Milestones') }}</a>
                    @can('viewRisks', $project)
                        <a href="{{ route('projects.risks.index', $project) }}" class="text-primary-600 hover:text-primary-700">{{ __('Risks') }}</a>
                    @endcan
                    @can('viewIssues', $project)
                        <a href="{{ route('projects.issues.index', $project) }}" class="text-primary-600 hover:text-primary-700">{{ __('Issues') }}</a>
                    @endcan
                    @can('viewBaselines', $project)
                        <a href="{{ route('projects.baselines.index', $project) }}" class="text-primary-600 hover:text-primary-700">{{ __('Baselines') }}</a>
                    @endcan
                    @can('viewDependencies', $project)
                        <a href="{{ route('projects.dependencies.index', $project) }}" class="text-primary-600 hover:text-primary-700">{{ __('Dependencies') }}</a>
                    @endcan
                    @can('viewCalendar', $project)
                        <a href="{{ route('projects.calendar', ['project_id' => $project->id]) }}" class="text-primary-600 hover:text-primary-700">{{ __('Calendar') }}</a>
                    @endcan
                </nav>
            </x-entity.section>

            <x-entity.section :title="__('Record')">
                <x-entity.definition-list>
                    <x-entity.definition-item :label="__('Created')">{{ $project->created_at->format('M j, Y g:i A') }}</x-entity.definition-item>
                    <x-entity.definition-item :label="__('Updated')">{{ $project->updated_at->format('M j, Y g:i A') }}</x-entity.definition-item>
                </x-entity.definition-list>
            </x-entity.section>
        </x-slot:aside>
    </x-layouts.entity-detail>
</x-app-layout>
