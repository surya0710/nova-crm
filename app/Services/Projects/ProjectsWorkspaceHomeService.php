<?php

namespace App\Services\Projects;

use App\Models\Portfolio;
use App\Models\Program;
use App\Models\Project;
use App\Models\ProjectHealthSnapshot;
use App\Models\ProjectIssue;
use App\Models\ProjectMilestone;
use App\Models\ProjectRisk;
use App\Models\Task;
use App\Models\User;
use App\Models\UserUiPreference;
use App\Services\Navigation\ShellQuickActionService;
use App\Services\TenantContext;
use App\Services\Workspace\CachesWorkspaceHome;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class ProjectsWorkspaceHomeService
{
    use CachesWorkspaceHome;

    public function __construct(
        protected TenantContext $tenant,
        protected ShellQuickActionService $shellQuickActions,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(User $user): array
    {
        return $this->rememberHome('projects', $user, fn () => $this->buildUncached($user));
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildUncached(User $user): array
    {
        $organization = $this->tenant->get();
        $prefs = UserUiPreference::query()
            ->where('user_id', $user->id)
            ->where('organization_id', $organization?->id)
            ->first();

        return [
            'kpis' => $this->kpis($user),
            'attention' => $this->attention($user),
            'activeProjects' => $this->activeProjects($user),
            'myTasks' => $this->myTasks($user),
            'upcomingMilestones' => $this->upcomingMilestones($user),
            'overdueTasks' => $this->overdueTasks($user),
            'portfolioSummary' => $this->portfolioSummary($user),
            'programSummary' => $this->programSummary($user),
            'budgetOverview' => $this->budgetOverview($user),
            'riskOverview' => $this->riskOverview($user),
            'recentActivity' => $this->recentActivity($user),
            'quickActions' => $this->quickActions($user, $organization),
            'favoriteProjects' => $this->favoriteProjects($user, $prefs),
            'recentProjects' => $this->recentProjects($user),
            'pinnedPages' => $this->pinnedProjectPages($prefs),
            'widgetLayout' => $prefs?->dashboard_layout['projects'] ?? null,
        ];
    }

    /**
     * @return array<int, array{label: string, value: string|int, hint?: string|null}>
     */
    protected function kpis(User $user): array
    {
        $kpis = [];

        if ($user->hasPermission('projects.view')) {
            $active = Project::query()->where('is_archived', false)->count();
            $kpis[] = [
                'label' => __('Active projects'),
                'value' => $active,
                'hint' => __('Not archived'),
            ];
        }

        if ($user->hasPermission('tasks.view')) {
            $overdue = Task::query()
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', now()->toDateString())
                ->where(function ($q) {
                    $q->whereNull('status')
                        ->orWhereNotIn('status', ['completed', 'cancelled', 'done']);
                })
                ->count();
            $kpis[] = [
                'label' => __('Overdue tasks'),
                'value' => $overdue,
                'hint' => __('Past due date'),
            ];
        }

        if ($user->hasPermission('projects.view') && Schema::hasTable('project_health_snapshots')) {
            $atRisk = ProjectHealthSnapshot::query()
                ->whereIn('health_status', ['red', 'amber', 'at_risk', 'critical'])
                ->count();
            $kpis[] = [
                'label' => __('At-risk health'),
                'value' => $atRisk,
                'hint' => __('Health snapshots'),
            ];
        }

        if ($user->hasPermission('projects.view')) {
            $openRisks = ProjectRisk::query()
                ->whereNotIn('status', ['closed', 'mitigated', 'accepted'])
                ->count();
            $kpis[] = [
                'label' => __('Open risks'),
                'value' => $openRisks,
            ];
        }

        if ($user->hasPermission('projects.portfolios.view')) {
            $kpis[] = [
                'label' => __('Portfolios'),
                'value' => Portfolio::query()->count(),
            ];
        }

        if ($user->hasPermission('projects.programs.view')) {
            $kpis[] = [
                'label' => __('Programs'),
                'value' => Program::query()->count(),
            ];
        }

        return array_slice($kpis, 0, 6);
    }

    /**
     * @return Collection<int, array{title: string, subtitle?: string|null, href?: string|null, badge?: string|null}>
     */
    protected function attention(User $user): Collection
    {
        $items = collect();

        if ($user->hasPermission('tasks.view')) {
            Task::query()
                ->with('project')
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', now()->toDateString())
                ->where(function ($q) {
                    $q->whereNull('status')
                        ->orWhereNotIn('status', ['completed', 'cancelled', 'done']);
                })
                ->where(function ($q) use ($user) {
                    $q->where('assigned_to', $user->id)
                        ->orWhere('created_by', $user->id);
                })
                ->orderBy('due_date')
                ->limit(5)
                ->get()
                ->each(function (Task $task) use ($items) {
                    $items->push([
                        'title' => $task->title,
                        'subtitle' => $task->project?->name,
                        'href' => route('tasks.show', $task),
                        'badge' => __('Overdue'),
                    ]);
                });
        }

        if ($user->hasPermission('projects.view')) {
            ProjectRisk::query()
                ->with('project')
                ->whereIn('severity', [4, 5])
                ->whereNotIn('status', ['closed', 'mitigated', 'accepted'])
                ->orderByDesc('severity')
                ->limit(4)
                ->get()
                ->each(function (ProjectRisk $risk) use ($items) {
                    $items->push([
                        'title' => $risk->title,
                        'subtitle' => $risk->project?->name,
                        'href' => $risk->project
                            ? route('projects.risks.index', $risk->project)
                            : route('risks.index'),
                        'badge' => __('Critical'),
                    ]);
                });
        }

        return $items->take(8)->values();
    }

    /**
     * @return Collection<int, Project>
     */
    protected function activeProjects(User $user): Collection
    {
        if (! $user->hasPermission('projects.view')) {
            return collect();
        }

        return Project::query()
            ->with(['status', 'owner', 'manager'])
            ->where('is_archived', false)
            ->where(function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                    ->orWhere('manager_id', $user->id);
            })
            ->latest('updated_at')
            ->limit(8)
            ->get();
    }

    /**
     * @return Collection<int, Task>
     */
    protected function myTasks(User $user): Collection
    {
        if (! $user->hasPermission('tasks.view')) {
            return collect();
        }

        return Task::query()
            ->with(['project', 'assignee'])
            ->where('assigned_to', $user->id)
            ->where(function ($q) {
                $q->whereNull('status')
                    ->orWhereNotIn('status', ['completed', 'cancelled', 'done']);
            })
            ->orderBy('due_date')
            ->limit(8)
            ->get();
    }

    /**
     * @return Collection<int, ProjectMilestone>
     */
    protected function upcomingMilestones(User $user): Collection
    {
        if (! $user->hasPermission('projects.view')) {
            return collect();
        }

        return ProjectMilestone::query()
            ->with('project')
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '>=', now()->toDateString())
            ->orderBy('due_date')
            ->limit(8)
            ->get();
    }

    /**
     * @return Collection<int, Task>
     */
    protected function overdueTasks(User $user): Collection
    {
        if (! $user->hasPermission('tasks.view')) {
            return collect();
        }

        return Task::query()
            ->with(['project', 'assignee'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->where(function ($q) {
                $q->whereNull('status')
                    ->orWhereNotIn('status', ['completed', 'cancelled', 'done']);
            })
            ->orderBy('due_date')
            ->limit(8)
            ->get();
    }

    /**
     * @return array{count: int, href: string|null}|null
     */
    protected function portfolioSummary(User $user): ?array
    {
        if (! $user->hasPermission('projects.portfolios.view')) {
            return null;
        }

        return [
            'count' => Portfolio::query()->count(),
            'href' => Route::has('portfolios.index') ? route('portfolios.index') : null,
        ];
    }

    /**
     * @return array{count: int, href: string|null}|null
     */
    protected function programSummary(User $user): ?array
    {
        if (! $user->hasPermission('projects.programs.view')) {
            return null;
        }

        return [
            'count' => Program::query()->count(),
            'href' => Route::has('programs.index') ? route('programs.index') : null,
        ];
    }

    /**
     * @return array{estimated: float, actual: float, href: string|null}|null
     */
    protected function budgetOverview(User $user): ?array
    {
        if (! $user->hasPermission('projects.view')) {
            return null;
        }

        $row = Project::query()
            ->where('is_archived', false)
            ->selectRaw('COALESCE(SUM(estimated_budget), 0) as estimated, COALESCE(SUM(actual_budget), 0) as actual')
            ->first();

        return [
            'estimated' => (float) ($row->estimated ?? 0),
            'actual' => (float) ($row->actual ?? 0),
            'href' => Route::has('projects.budgets.hub') ? route('projects.budgets.hub') : (Route::has('projects.executive') ? route('projects.executive') : null),
        ];
    }

    /**
     * @return array{open: int, critical: int, open_issues: int, href: string|null}|null
     */
    protected function riskOverview(User $user): ?array
    {
        if (! $user->hasPermission('projects.view')) {
            return null;
        }

        return [
            'open' => ProjectRisk::query()->whereNotIn('status', ['closed', 'mitigated', 'accepted'])->count(),
            'critical' => ProjectRisk::query()
                ->whereIn('severity', [4, 5])
                ->whereNotIn('status', ['closed', 'mitigated', 'accepted'])
                ->count(),
            'open_issues' => ProjectIssue::query()->whereNotIn('status', ['closed', 'resolved', 'done'])->count(),
            'href' => Route::has('risks.index') ? route('risks.index') : null,
        ];
    }

    /**
     * @return Collection<int, array{title: string, subtitle: string|null, href: string|null, when: string|null}>
     */
    protected function recentActivity(User $user): Collection
    {
        $items = collect();

        if ($user->hasPermission('projects.view')) {
            Project::query()
                ->latest('updated_at')
                ->limit(5)
                ->get()
                ->each(function (Project $project) use ($items) {
                    $items->push([
                        'title' => $project->name,
                        'subtitle' => __('Project updated'),
                        'href' => route('projects.show', $project),
                        'when' => $project->updated_at?->diffForHumans(),
                        'at' => $project->updated_at,
                    ]);
                });
        }

        if ($user->hasPermission('tasks.view')) {
            Task::query()
                ->latest('updated_at')
                ->limit(4)
                ->get()
                ->each(function (Task $task) use ($items) {
                    $items->push([
                        'title' => $task->title,
                        'subtitle' => __('Task updated'),
                        'href' => route('tasks.show', $task),
                        'when' => $task->updated_at?->diffForHumans(),
                        'at' => $task->updated_at,
                    ]);
                });
        }

        if ($user->hasPermission('projects.view')) {
            ProjectIssue::query()
                ->latest('updated_at')
                ->limit(3)
                ->get()
                ->each(function (ProjectIssue $issue) use ($items) {
                    $items->push([
                        'title' => $issue->title,
                        'subtitle' => __('Issue · :status', ['status' => $issue->status]),
                        'href' => $issue->project_id
                            ? route('projects.issues.index', $issue->project_id)
                            : route('issues.index'),
                        'when' => $issue->updated_at?->diffForHumans(),
                        'at' => $issue->updated_at,
                    ]);
                });
        }

        return $items
            ->sortByDesc(fn ($item) => $item['at'] instanceof Carbon ? $item['at']->timestamp : 0)
            ->take(10)
            ->values();
    }

    /**
     * @return array{primary: array<int, array{label: string, href: string, variant?: string}>, overflow: array<int, array{label: string, href: string, variant?: string}>, all: array<int, array{label: string, href: string, variant?: string}>}
     */
    protected function quickActions(User $user, $organization): array
    {
        if (! $organization) {
            return ['primary' => [], 'overflow' => [], 'all' => []];
        }

        return $this->shellQuickActions->forWorkspace($user, $organization, 'projects');
    }

    /**
     * @return Collection<int, array{label: string, href: string}>
     */
    protected function favoriteProjects(User $user, ?UserUiPreference $prefs): Collection
    {
        if (! $user->hasPermission('projects.view')) {
            return collect();
        }

        return collect($prefs?->favorites ?? [])
            ->filter(function ($item) {
                if (! is_array($item)) {
                    return false;
                }
                $href = $item['href'] ?? '';

                return str_contains($href, '/projects/') && ! str_contains($href, '/projects?');
            })
            ->map(fn ($item) => [
                'label' => $item['label'] ?? __('Project'),
                'href' => $item['href'],
            ])
            ->take(6)
            ->values();
    }

    /**
     * @return Collection<int, Project>
     */
    protected function recentProjects(User $user): Collection
    {
        if (! $user->hasPermission('projects.view')) {
            return collect();
        }

        return Project::query()
            ->with('status')
            ->latest('updated_at')
            ->limit(6)
            ->get();
    }

    /**
     * @return Collection<int, array{label: string, href: string}>
     */
    protected function pinnedProjectPages(?UserUiPreference $prefs): Collection
    {
        $pinned = collect($prefs?->pinned_pages ?? []);

        return $pinned
            ->filter(function ($page) {
                $href = is_array($page) ? ($page['href'] ?? '') : '';
                $workspace = is_array($page) ? ($page['workspace'] ?? null) : null;

                return $href && (
                    $workspace === 'projects'
                    || str_contains($href, '/projects')
                    || str_contains($href, '/portfolios')
                    || str_contains($href, '/programs')
                    || str_contains($href, '/resources')
                    || str_contains($href, '/tasks')
                );
            })
            ->map(fn ($page) => [
                'label' => $page['label'] ?? ($page['title'] ?? __('Pinned page')),
                'href' => $page['href'],
            ])
            ->values();
    }
}
