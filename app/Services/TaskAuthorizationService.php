<?php

namespace App\Services;

use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskComment;
use App\Models\TaskTimeLog;
use App\Models\User;

/**
 * Assignment-driven task access for Release 1.2.1.
 *
 * Layers (all must pass where applicable):
 * 1. Dynamic RBAC base permission (e.g. tasks.view)
 * 2. Organization isolation (caller + BelongsToOrganization / tenant)
 * 3. Project membership (for project-bound tasks), unless elevated or assignee
 * 4. Task assignment (collaborator capabilities)
 */
class TaskAuthorizationService
{
    public function isAssignee(User $user, Task $task): bool
    {
        return $task->assigned_to !== null
            && (int) $task->assigned_to === (int) $user->id;
    }

    public function isActiveProjectMember(User $user, Task $task): bool
    {
        if ($task->project_id === null) {
            return true;
        }

        return ProjectMember::query()
            ->where('project_id', $task->project_id)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->whereNull('left_at')
            ->exists();
    }

    public function hasElevatedAccess(User $user, Task $task): bool
    {
        $organization = $task->organization;

        return $user->hasPermission('tasks.manage', $organization)
            || $user->hasPermission('tasks.edit', $organization)
            || $user->hasPermission('tasks.update', $organization);
    }

    public function canView(User $user, Task $task): bool
    {
        if (! $user->hasPermission('tasks.view', $task->organization)) {
            return false;
        }

        if ($this->hasElevatedAccess($user, $task)) {
            return true;
        }

        if ($this->isAssignee($user, $task)) {
            return true;
        }

        return $this->isActiveProjectMember($user, $task);
    }

    /**
     * Assignee (or elevated user) may execute collaboration work on the task.
     */
    public function canCollaborate(User $user, Task $task): bool
    {
        if (! $this->canView($user, $task)) {
            return false;
        }

        return $this->isAssignee($user, $task) || $this->hasElevatedAccess($user, $task);
    }

    public function canFullyUpdate(User $user, Task $task): bool
    {
        return $this->hasElevatedAccess($user, $task);
    }

    /**
     * Assignees may change status / progress only — not reassignment or settings.
     */
    public function canUpdateOwnWork(User $user, Task $task): bool
    {
        return $this->canCollaborate($user, $task);
    }

    public function canManageChecklists(User $user, Task $task): bool
    {
        if ($user->hasPermission('tasks.manage-checklists', $task->organization)) {
            return $this->canView($user, $task);
        }

        return $this->canCollaborate($user, $task);
    }

    public function canComment(User $user, Task $task): bool
    {
        if ($user->hasPermission('tasks.comment', $task->organization)) {
            return $this->canView($user, $task);
        }

        return $this->canCollaborate($user, $task);
    }

    public function canUpdateComment(User $user, TaskComment $comment): bool
    {
        $task = $comment->task;
        if ($task === null || ! $this->canComment($user, $task)) {
            return false;
        }

        return (int) $comment->user_id === (int) $user->id;
    }

    public function canDeleteComment(User $user, TaskComment $comment): bool
    {
        $task = $comment->task;
        if ($task === null || ! $this->canView($user, $task)) {
            return false;
        }

        if ((int) $comment->user_id === (int) $user->id && $this->canComment($user, $task)) {
            return true;
        }

        return $this->hasElevatedAccess($user, $task)
            || $user->hasPermission('tasks.manage', $task->organization);
    }

    public function canManageAttachments(User $user, Task $task): bool
    {
        if (! $this->attachmentsEnabled()) {
            return false;
        }

        if ($user->hasPermission('tasks.attachments', $task->organization)) {
            return $this->canView($user, $task);
        }

        return $this->canCollaborate($user, $task);
    }

    public function canDeleteAttachment(User $user, TaskAttachment $attachment): bool
    {
        $task = $attachment->task;
        if ($task === null || ! $this->canManageAttachments($user, $task)) {
            return false;
        }

        if ((int) $attachment->uploaded_by === (int) $user->id) {
            return true;
        }

        return $this->hasElevatedAccess($user, $task)
            || $user->hasPermission('tasks.attachments', $task->organization);
    }

    public function canLogTime(User $user, Task $task): bool
    {
        if ($user->hasPermission('tasks.time-log', $task->organization)) {
            return $this->canView($user, $task);
        }

        return $this->canCollaborate($user, $task);
    }

    public function canDeleteTimeLog(User $user, TaskTimeLog $timeLog): bool
    {
        $task = $timeLog->task;
        if ($task === null || ! $this->canLogTime($user, $task)) {
            return false;
        }

        if ((int) $timeLog->user_id === (int) $user->id) {
            return true;
        }

        return $this->hasElevatedAccess($user, $task);
    }

    public function attachmentsEnabled(): bool
    {
        return (bool) config('attachments.task_attachments_enabled', config('attachments.enabled', true));
    }

    /**
     * Fields an assignee may patch without full edit permission.
     *
     * @return list<string>
     */
    public function assigneeEditableFields(): array
    {
        return [
            'status',
            'status_id',
            'completion_percentage',
        ];
    }
}
