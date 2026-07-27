<?php

namespace App\Services\Operations;

use App\Models\Task;
use App\Models\User;
use App\Services\TenantContext;
use App\Services\Workspace\CachesWorkspaceHome;
use Illuminate\Support\Facades\Route;

class OperationsWorkspaceHomeService
{
    use CachesWorkspaceHome;

    public function __construct(protected TenantContext $tenant) {}

    /**
     * @return array<string, mixed>
     */
    public function build(User $user): array
    {
        return $this->rememberHome('operations', $user, fn () => $this->buildUncached($user));
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildUncached(User $user): array
    {
        return [
            'kpis' => $this->kpis($user),
            'myTasks' => $this->myTasks($user),
            'overdueTasks' => $this->overdueTasks($user),
            'quickActions' => $this->quickActions($user),
        ];
    }

    /**
     * @return array<int, array{label: string, value: string, hint?: string|null}>
     */
    protected function kpis(User $user): array
    {
        if (! $user->hasPermission('tasks.view')) {
            return [];
        }

        $organization = $this->tenant->get();
        if (! $organization) {
            return [];
        }

        $base = Task::query()->where('organization_id', $organization->id);

        $open = (clone $base)->whereNull('completed_at')->where('is_archived', false)->count();
        $mine = (clone $base)
            ->whereNull('completed_at')
            ->where('is_archived', false)
            ->where('assigned_to', $user->id)
            ->count();
        $overdue = (clone $base)
            ->whereNull('completed_at')
            ->where('is_archived', false)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->count();
        $dueToday = (clone $base)
            ->whereNull('completed_at')
            ->where('is_archived', false)
            ->whereDate('due_date', now()->toDateString())
            ->count();

        return [
            ['label' => __('Open tasks'), 'value' => (string) $open],
            ['label' => __('My tasks'), 'value' => (string) $mine],
            ['label' => __('Overdue'), 'value' => (string) $overdue, 'hint' => __('Needs attention')],
            ['label' => __('Due today'), 'value' => (string) $dueToday],
        ];
    }

    protected function myTasks(User $user)
    {
        if (! $user->hasPermission('tasks.view')) {
            return collect();
        }

        $organization = $this->tenant->get();
        if (! $organization) {
            return collect();
        }

        return Task::query()
            ->where('organization_id', $organization->id)
            ->where('assigned_to', $user->id)
            ->whereNull('completed_at')
            ->where('is_archived', false)
            ->latest('updated_at')
            ->limit(8)
            ->get(['id', 'title', 'due_date', 'priority', 'status']);
    }

    protected function overdueTasks(User $user)
    {
        if (! $user->hasPermission('tasks.view')) {
            return collect();
        }

        $organization = $this->tenant->get();
        if (! $organization) {
            return collect();
        }

        return Task::query()
            ->where('organization_id', $organization->id)
            ->whereNull('completed_at')
            ->where('is_archived', false)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->orderBy('due_date')
            ->limit(8)
            ->get(['id', 'title', 'due_date', 'priority', 'status']);
    }

    /**
     * @return array<int, array{label: string, href: string, variant?: string}>
     */
    protected function quickActions(User $user): array
    {
        $actions = [];

        if ($user->hasPermission('tasks.create') && Route::has('tasks.create')) {
            $actions[] = ['label' => __('Create Task'), 'href' => route('tasks.create'), 'variant' => 'primary'];
        }

        if ($user->hasPermission('tasks.view') && Route::has('tasks.board')) {
            $actions[] = ['label' => __('Task board'), 'href' => route('tasks.board')];
        }

        if ($user->hasPermission('tasks.view') && Route::has('tasks.list')) {
            $actions[] = ['label' => __('Task list'), 'href' => route('tasks.list')];
        }

        return $actions;
    }
}
