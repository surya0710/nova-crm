<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('projects.view');
    }

    public function view(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.view', $project->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('projects.create');
    }

    public function update(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.edit', $project->organization);
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.delete', $project->organization);
    }

    public function archive(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.archive', $project->organization);
    }

    public function restore(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.restore', $project->organization);
    }

    public function manage(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.manage', $project->organization);
    }

    public function assignMembers(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.assign-members', $project->organization);
    }

    public function manageMilestones(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.manage-milestones', $project->organization);
    }

    public function viewBudget(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.view-budget', $project->organization);
    }

    public function manageBudget(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.manage-budget', $project->organization);
    }

    public function export(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.export', $project->organization);
    }

    public function import(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.import', $project->organization);
    }

    public function viewProgress(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.progress.view', $project->organization);
    }

    public function createProgress(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.progress.create', $project->organization);
    }

    public function updateProgress(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.progress.update', $project->organization);
    }

    public function deleteProgress(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.progress.delete', $project->organization);
    }

    public function viewHealth(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.health.view', $project->organization);
    }

    public function viewReports(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.reports.view', $project->organization);
    }

    public function generateReports(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.reports.generate', $project->organization);
    }

    public function viewTimeline(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.timeline.view', $project->organization);
    }

    public function viewGantt(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.gantt.view', $project->organization);
    }

    public function viewStatistics(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.statistics.view', $project->organization);
    }

    public function viewCollaboration(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.collaboration.view', $project->organization)
            || $user->hasPermission('projects.collaboration.manage', $project->organization);
    }

    public function manageCollaboration(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.collaboration.manage', $project->organization);
    }

    public function viewWatchers(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.watchers.view', $project->organization)
            || $user->hasPermission('projects.watchers.manage', $project->organization);
    }

    public function manageWatchers(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.watchers.manage', $project->organization);
    }

    public function viewCalendar(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.calendar.view', $project->organization)
            || $user->hasPermission('projects.calendar.manage', $project->organization);
    }

    public function manageCalendar(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.calendar.manage', $project->organization);
    }

    public function viewAutomation(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.automation.view', $project->organization)
            || $user->hasPermission('projects.automation.manage', $project->organization);
    }

    public function manageAutomation(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.automation.manage', $project->organization);
    }

    public function viewMentions(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.mentions.view', $project->organization);
    }

    public function viewRisks(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.risks.view', $project->organization)
            || $user->hasPermission('projects.risks.manage', $project->organization);
    }

    public function createRisks(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.risks.create', $project->organization)
            || $user->hasPermission('projects.risks.manage', $project->organization);
    }

    public function manageRisks(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.risks.manage', $project->organization)
            || $user->hasPermission('projects.risks.update', $project->organization);
    }

    public function viewIssues(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.issues.view', $project->organization)
            || $user->hasPermission('projects.issues.manage', $project->organization);
    }

    public function createIssues(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.issues.create', $project->organization)
            || $user->hasPermission('projects.issues.manage', $project->organization);
    }

    public function manageIssues(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.issues.manage', $project->organization)
            || $user->hasPermission('projects.issues.update', $project->organization);
    }

    public function viewBaselines(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.baselines.view', $project->organization)
            || $user->hasPermission('projects.baselines.manage', $project->organization);
    }

    public function createBaselines(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.baselines.create', $project->organization)
            || $user->hasPermission('projects.baselines.manage', $project->organization);
    }

    public function viewBudgets(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.budgets.view', $project->organization)
            || $user->hasPermission('projects.budgets.manage', $project->organization);
    }

    public function manageBudgets(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.budgets.update', $project->organization)
            || $user->hasPermission('projects.budgets.create', $project->organization)
            || $user->hasPermission('projects.budgets.manage', $project->organization);
    }

    public function viewDependencies(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.dependencies.view', $project->organization)
            || $user->hasPermission('projects.dependencies.manage', $project->organization);
    }

    public function manageDependencies(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.dependencies.manage', $project->organization);
    }

    public function viewForecasts(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.forecasts.view', $project->organization);
    }
}
