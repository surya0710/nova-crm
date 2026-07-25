<?php

namespace App\Services\CommandPalette;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class ProjectsCommandProvider implements CommandProviderInterface
{
    public function commands(User $user, ?Organization $organization): Collection
    {
        if (! $organization) {
            return collect();
        }

        $commands = collect();
        $group = __('Projects');

        if (Route::has('projects.home') && $user->hasAnyPermission([
            'projects.view', 'resources.view', 'projects.portfolios.view', 'projects.programs.view', 'tasks.view',
        ])) {
            $commands->push([
                'id' => 'projects.home',
                'label' => __('Open Projects Home'),
                'group' => $group,
                'href' => route('projects.home'),
                'keywords' => ['projects', 'home', 'epm', 'portfolio'],
            ]);
        }

        if ($user->hasPermission('projects.create') && Route::has('projects.create')) {
            $commands->push([
                'id' => 'projects.create',
                'label' => __('Create Project'),
                'group' => $group,
                'href' => route('projects.create'),
                'keywords' => ['project', 'new', 'create'],
            ]);
        }

        if ($user->hasPermission('tasks.create') && Route::has('tasks.create')) {
            $commands->push([
                'id' => 'projects.create-task',
                'label' => __('Create Task'),
                'group' => $group,
                'href' => route('tasks.create'),
                'keywords' => ['task', 'new', 'create'],
            ]);
        }

        if ($user->hasPermission('projects.portfolios.view') && Route::has('portfolios.index')) {
            $commands->push([
                'id' => 'projects.open-portfolio',
                'label' => __('Open Portfolios'),
                'group' => $group,
                'href' => route('portfolios.index'),
                'keywords' => ['portfolio', 'open'],
            ]);
        }

        if ($user->hasPermission('projects.programs.view') && Route::has('programs.index')) {
            $commands->push([
                'id' => 'projects.open-program',
                'label' => __('Open Programs'),
                'group' => $group,
                'href' => route('programs.index'),
                'keywords' => ['program', 'open'],
            ]);
        }

        if ($user->hasPermission('resources.view') && Route::has('resources.planner')) {
            $commands->push([
                'id' => 'projects.open-resource-planner',
                'label' => __('Open Resource Planner'),
                'group' => $group,
                'href' => route('resources.planner'),
                'keywords' => ['resource', 'planner', 'capacity', 'allocation'],
            ]);
        }

        if ($user->hasPermission('projects.view') && Route::has('projects.index')) {
            $commands->push([
                'id' => 'projects.search-projects',
                'label' => __('Search Projects'),
                'group' => $group,
                'href' => route('projects.index'),
                'keywords' => ['project', 'search', 'find'],
            ]);
        }

        if ($user->hasPermission('tasks.view') && Route::has('tasks.index')) {
            $commands->push([
                'id' => 'projects.search-tasks',
                'label' => __('Search Tasks'),
                'group' => $group,
                'href' => route('tasks.index'),
                'keywords' => ['task', 'search', 'find'],
            ]);
        }

        if ($user->hasPermission('tasks.view') && Route::has('tasks.board')) {
            $commands->push([
                'id' => 'projects.open-task-board',
                'label' => __('Open Task Board'),
                'group' => $group,
                'href' => route('tasks.board'),
                'keywords' => ['kanban', 'board', 'tasks'],
            ]);
        }

        if ($user->hasPermission('projects.view') && Route::has('projects.reports.hub')) {
            $commands->push([
                'id' => 'projects.open-reports',
                'label' => __('Open Project Reports'),
                'group' => $group,
                'href' => route('projects.reports.hub'),
                'keywords' => ['reports', 'executive', 'portfolio'],
            ]);
        }

        return $commands;
    }
}
