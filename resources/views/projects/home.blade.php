<x-app-layout>
    <x-flash-messages />

    <x-layouts.workspace-home
        :title="__('Projects')"
        :subtitle="__('Deliver work on time and on budget')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-workspace.quick-actions :actions="$quickActions" />
        </x-slot:actions>

        <x-slot:kpis>
            @forelse ($kpis as $kpi)
                <x-ui.stat-card
                    :label="$kpi['label']"
                    :value="$kpi['value']"
                    :hint="$kpi['hint'] ?? null"
                />
            @empty
                <x-ui.stat-card :label="__('Projects')" :value="__('—')" :hint="__('No metrics available for your role')" />
            @endforelse
        </x-slot:kpis>

        <div class="space-y-6">
            <x-workspace.widget
                :title="__('My projects')"
                :subtitle="__('Owned or managed by you')"
                :href="auth()->user()->hasPermission('projects.view') ? route('projects.index', ['owner_id' => auth()->id()]) : null"
            >
                @if ($activeProjects->isEmpty())
                    <x-ui.empty-state-preset
                        variant="projects"
                        :action-href="auth()->user()->can('create', App\Models\Project::class) ? route('projects.create') : null"
                        class="!py-6"
                    />
                @else
                    <ul class="divide-y divide-line -mx-1">
                        @foreach ($activeProjects as $project)
                            <li class="py-2.5 flex items-center justify-between gap-3">
                                <a href="{{ route('projects.show', $project) }}" class="min-w-0 text-sm font-medium text-ink-heading hover:text-primary-700 truncate">{{ $project->name }}</a>
                                @if ($project->status)
                                    <x-ui.badge variant="primary">{{ $project->status->name }}</x-ui.badge>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-workspace.widget>

            <div class="grid gap-6 md:grid-cols-2">
                <x-workspace.widget
                    :title="__('My tasks')"
                    :subtitle="__('Assigned to you')"
                    :href="auth()->user()->hasPermission('tasks.view') ? route('tasks.index') : null"
                >
                    @forelse ($myTasks as $task)
                        <a href="{{ route('tasks.show', $task) }}" class="flex items-center justify-between gap-2 py-2 text-sm border-b border-line last:border-0">
                            <span class="font-medium text-ink-heading truncate hover:text-primary-700">{{ $task->title }}</span>
                            <span class="text-xs text-ink-muted shrink-0">{{ $task->due_date?->format('M j') ?? '—' }}</span>
                        </a>
                    @empty
                        <x-ui.empty-state-preset variant="tasks" class="!py-6" />
                    @endforelse
                </x-workspace.widget>

                <x-workspace.widget
                    :title="__('Overdue tasks')"
                    :href="auth()->user()->hasPermission('tasks.view') ? route('tasks.list') : null"
                >
                    @forelse ($overdueTasks as $task)
                        <a href="{{ route('tasks.show', $task) }}" class="flex items-center justify-between gap-2 py-2 text-sm border-b border-line last:border-0">
                            <span class="font-medium text-ink-heading truncate hover:text-primary-700">{{ $task->title }}</span>
                            <x-ui.badge variant="danger">{{ $task->due_date?->format('M j') }}</x-ui.badge>
                        </a>
                    @empty
                        <p class="text-sm text-ink-muted py-4 text-center">{{ __('No overdue tasks.') }}</p>
                    @endforelse
                </x-workspace.widget>
            </div>

            <x-workspace.widget
                :title="__('Upcoming milestones')"
                :href="auth()->user()->hasPermission('projects.view') ? route('projects.milestones.hub', ['upcoming' => 1]) : null"
            >
                @forelse ($upcomingMilestones as $milestone)
                    <a href="{{ route('projects.milestones.index', $milestone->project) }}" class="flex items-center justify-between gap-2 py-2 text-sm border-b border-line last:border-0">
                        <div class="min-w-0">
                            <p class="font-medium text-ink-heading truncate hover:text-primary-700">{{ $milestone->name }}</p>
                            <p class="text-xs text-ink-muted truncate">{{ $milestone->project?->name }}</p>
                        </div>
                        <span class="text-xs text-ink-muted shrink-0">{{ $milestone->due_date?->format('M j') }}</span>
                    </a>
                @empty
                    <x-ui.empty-state-preset variant="milestones" class="!py-6" />
                @endforelse
            </x-workspace.widget>

            <div class="grid gap-6 md:grid-cols-2">
                @if ($portfolioSummary)
                    <x-workspace.widget :title="__('Portfolio summary')" :href="$portfolioSummary['href']">
                        <dl class="text-sm">
                            <div class="flex justify-between gap-3 py-1">
                                <dt class="text-ink-muted">{{ __('Portfolios') }}</dt>
                                <dd class="font-semibold text-ink-heading">{{ $portfolioSummary['count'] }}</dd>
                            </div>
                        </dl>
                    </x-workspace.widget>
                @endif

                @if ($programSummary)
                    <x-workspace.widget :title="__('Program summary')" :href="$programSummary['href']">
                        <dl class="text-sm">
                            <div class="flex justify-between gap-3 py-1">
                                <dt class="text-ink-muted">{{ __('Programs') }}</dt>
                                <dd class="font-semibold text-ink-heading">{{ $programSummary['count'] }}</dd>
                            </div>
                        </dl>
                    </x-workspace.widget>
                @endif
            </div>

            @if ($budgetOverview)
                <x-workspace.widget :title="__('Budget overview')" :href="$budgetOverview['href']">
                    <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2 text-sm">
                        <div>
                            <dt class="text-xs text-ink-muted">{{ __('Estimated') }}</dt>
                            <dd class="mt-1 font-semibold text-ink-heading">{{ number_format($budgetOverview['estimated'], 0) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-ink-muted">{{ __('Actual') }}</dt>
                            <dd class="mt-1 font-semibold text-ink-heading">{{ number_format($budgetOverview['actual'], 0) }}</dd>
                        </div>
                    </dl>
                </x-workspace.widget>
            @endif

            @if ($riskOverview)
                <x-workspace.widget :title="__('Risk overview')" :href="$riskOverview['href']">
                    <dl class="grid grid-cols-1 gap-3 sm:grid-cols-3 text-sm">
                        <div>
                            <dt class="text-xs text-ink-muted">{{ __('Open risks') }}</dt>
                            <dd class="mt-1 font-semibold text-ink-heading">{{ $riskOverview['open'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-ink-muted">{{ __('Critical') }}</dt>
                            <dd class="mt-1 font-semibold text-ink-heading">{{ $riskOverview['critical'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-ink-muted">{{ __('Open issues') }}</dt>
                            <dd class="mt-1 font-semibold text-ink-heading">{{ $riskOverview['open_issues'] }}</dd>
                        </div>
                    </dl>
                </x-workspace.widget>
            @endif

            <div class="grid gap-6 md:grid-cols-2">
                <x-workspace.widget :title="__('Favorite projects')">
                    @forelse ($favoriteProjects as $fav)
                        <a href="{{ $fav['href'] }}" class="block text-sm font-medium text-primary-600 hover:text-primary-700 py-1.5">{{ $fav['label'] }}</a>
                    @empty
                        <p class="text-sm text-ink-muted py-4 text-center">{{ __('Star projects from the shell favorites control.') }}</p>
                    @endforelse
                </x-workspace.widget>

                <x-workspace.widget
                    :title="__('Recent projects')"
                    :href="auth()->user()->hasPermission('projects.view') ? route('projects.index') : null"
                >
                    @forelse ($recentProjects as $project)
                        <a href="{{ route('projects.show', $project) }}" class="flex items-center justify-between gap-2 py-2 text-sm border-b border-line last:border-0">
                            <span class="font-medium text-ink-heading truncate hover:text-primary-700">{{ $project->name }}</span>
                            <span class="text-xs text-ink-muted shrink-0">{{ $project->updated_at?->diffForHumans() }}</span>
                        </a>
                    @empty
                        <x-ui.empty-state-preset variant="projects" class="!py-6" />
                    @endforelse
                </x-workspace.widget>
            </div>

            <x-workspace.widget :title="__('Recent activity')">
                @forelse ($recentActivity as $item)
                    <a href="{{ $item['href'] }}" class="flex items-start justify-between gap-3 py-2.5 border-b border-line last:border-0">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-ink-heading truncate hover:text-primary-700">{{ $item['title'] }}</p>
                            <p class="text-xs text-ink-muted">{{ $item['subtitle'] }}</p>
                        </div>
                        <span class="text-xs text-ink-muted shrink-0">{{ $item['when'] }}</span>
                    </a>
                @empty
                    <x-ui.empty-state-preset variant="activities" class="!py-6" />
                @endforelse
            </x-workspace.widget>
        </div>

        <x-slot:aside>
            <x-workspace.attention-rail :title="__('Needs attention')">
                @forelse ($attention as $item)
                    <x-workspace.attention-item
                        :href="$item['href'] ?? null"
                        :title="$item['title']"
                        :subtitle="$item['subtitle'] ?? null"
                        :badge="$item['badge'] ?? null"
                    />
                @empty
                @endforelse
            </x-workspace.attention-rail>

            <x-entity.section :title="__('Pinned pages')">
                @forelse ($pinnedPages as $page)
                    <a href="{{ $page['href'] }}" class="block text-sm font-medium text-primary-600 hover:text-primary-700 py-1.5">{{ $page['label'] }}</a>
                @empty
                    <p class="text-sm text-ink-muted">{{ __('Pin pages from the shell favorites control.') }}</p>
                @endforelse
            </x-entity.section>

            @if (auth()->user()->hasPermission('resources.view') && \Illuminate\Support\Facades\Route::has('resources.planner'))
                <x-entity.section :title="__('Resource utilization')">
                    <a href="{{ route('resources.capacity') }}" class="block text-sm font-medium text-primary-600 hover:text-primary-700 py-1.5">{{ __('Open capacity') }}</a>
                    <a href="{{ route('resources.planner') }}" class="block text-sm font-medium text-primary-600 hover:text-primary-700 py-1.5">{{ __('Open planner') }}</a>
                    <a href="{{ route('resources.timeline') }}" class="block text-sm font-medium text-primary-600 hover:text-primary-700 py-1.5">{{ __('Team workload') }}</a>
                </x-entity.section>
            @endif
        </x-slot:aside>
    </x-layouts.workspace-home>
</x-app-layout>
