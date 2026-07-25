<?php

namespace App\Services;

use App\Events\ProjectMemberAssigned;
use App\Events\ProjectMemberRemoved;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use App\Notifications\CrmNotification;
use App\Workflow\WorkflowRuntimeContext;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

class ProjectMemberService
{
    public function add(Project $project, User $member, string $role, User $actor): ProjectMember
    {
        $this->assertProjectWritable($project);
        $this->assertOrgMember($project, $member->id, 'user_id');
        $this->validateRole($role);

        $existing = ProjectMember::query()
            ->where('organization_id', $project->organization_id)
            ->where('project_id', $project->id)
            ->where('user_id', $member->id)
            ->first();

        if ($existing && $existing->is_active) {
            throw ValidationException::withMessages([
                'user_id' => __('This user is already an active member of the project.'),
            ]);
        }

        if ($existing) {
            $existing->update([
                'project_role' => $role,
                'joined_at' => now(),
                'left_at' => null,
                'is_active' => true,
            ]);

            $projectMember = $existing->fresh(['user']);
        } else {
            $projectMember = ProjectMember::query()->create([
                'organization_id' => $project->organization_id,
                'project_id' => $project->id,
                'user_id' => $member->id,
                'project_role' => $role,
                'joined_at' => now(),
                'is_active' => true,
            ]);

            $projectMember = $projectMember->fresh(['user']);
        }

        $runtime = app(WorkflowRuntimeContext::class);
        event(ProjectMemberAssigned::forModel(
            $projectMember,
            [
                'actor_id' => $actor->id,
                'project_id' => $project->id,
                'user_id' => $member->id,
                'role' => $role,
            ],
            causationId: $runtime->causationId,
            depth: $runtime->causationId ? $runtime->depth + 1 : 0,
        ));

        if ($this->auditLogger()) {
            $this->auditLogger()->log($projectMember, 'member_assigned', [
                'project_id' => $project->id,
                'user_id' => $member->id,
                'role' => $role,
            ], $actor);
        }

        if ($member->id !== $actor->id) {
            $member->notify(new CrmNotification(
                title: __('Added to project'),
                message: __('You were added to :project as :role.', [
                    'project' => $project->name,
                    'role' => config('projects.roles.'.$role, $role),
                ]),
                actionUrl: $this->projectUrl($project),
                organizationId: (int) $project->organization_id,
            ));
        }

        return $projectMember;
    }

    public function remove(Project $project, ProjectMember|User $target, User $actor): ProjectMember
    {
        $this->assertProjectWritable($project);

        $projectMember = $target instanceof ProjectMember
            ? $target
            : ProjectMember::query()
                ->where('organization_id', $project->organization_id)
                ->where('project_id', $project->id)
                ->where('user_id', $target->id)
                ->firstOrFail();

        if ((int) $projectMember->project_id !== (int) $project->id) {
            throw ValidationException::withMessages([
                'member' => __('The member does not belong to this project.'),
            ]);
        }

        if (! $projectMember->is_active) {
            return $projectMember;
        }

        $projectMember->update([
            'is_active' => false,
            'left_at' => now(),
        ]);

        $projectMember = $projectMember->fresh(['user']);

        $runtime = app(WorkflowRuntimeContext::class);
        event(ProjectMemberRemoved::forModel(
            $projectMember,
            [
                'actor_id' => $actor->id,
                'project_id' => $project->id,
                'user_id' => $projectMember->user_id,
                'role' => $projectMember->project_role,
            ],
            causationId: $runtime->causationId,
            depth: $runtime->causationId ? $runtime->depth + 1 : 0,
        ));

        $member = $projectMember->user;

        if ($member && $member->id !== $actor->id) {
            $member->notify(new CrmNotification(
                title: __('Removed from project'),
                message: __('You were removed from :project.', ['project' => $project->name]),
                actionUrl: $this->projectUrl($project),
                organizationId: (int) $project->organization_id,
            ));
        }

        return $projectMember;
    }

    public function changeRole(ProjectMember $projectMember, string $role, User $actor): ProjectMember
    {
        $project = $projectMember->project;

        if (! $project) {
            throw ValidationException::withMessages([
                'member' => __('The project member record is invalid.'),
            ]);
        }

        $this->assertProjectWritable($project);
        $this->validateRole($role);

        if (! $projectMember->is_active) {
            throw ValidationException::withMessages([
                'member' => __('Cannot change the role of an inactive project member.'),
            ]);
        }

        $projectMember->update(['project_role' => $role]);

        return $projectMember->fresh(['user']);
    }

    protected function validateRole(string $role): void
    {
        if (! array_key_exists($role, config('projects.roles', []))) {
            throw ValidationException::withMessages([
                'project_role' => __('Invalid project role.'),
            ]);
        }
    }

    protected function assertOrgMember(Project $project, int $userId, string $field): void
    {
        if (! $project->organization->users()->whereKey($userId)->exists()) {
            throw ValidationException::withMessages([
                $field => __('The selected user is not an organization member.'),
            ]);
        }
    }

    protected function assertProjectWritable(Project $project): void
    {
        if ($project->isArchived()) {
            throw ValidationException::withMessages([
                'project' => __('Archived projects are read-only.'),
            ]);
        }
    }

    protected function projectUrl(Project $project): ?string
    {
        return Route::has('projects.show')
            ? route('projects.show', $project)
            : null;
    }

    protected function auditLogger(): ?AuditLogger
    {
        return app()->bound(AuditLogger::class) ? app(AuditLogger::class) : null;
    }
}
