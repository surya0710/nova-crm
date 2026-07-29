<?php

namespace App\Policies;

use App\Models\TaskAttachment;
use App\Models\User;
use App\Services\TaskAuthorizationService;

class TaskAttachmentPolicy
{
    public function __construct(protected TaskAuthorizationService $auth) {}

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('tasks.view') && $this->auth->attachmentsEnabled();
    }

    public function view(User $user, TaskAttachment $attachment): bool
    {
        if (! $this->auth->attachmentsEnabled()) {
            return false;
        }

        $task = $attachment->task;

        return $task !== null && $this->auth->canView($user, $task);
    }

    public function create(User $user): bool
    {
        return $this->auth->attachmentsEnabled()
            && ($user->hasPermission('tasks.attachments') || $user->hasPermission('tasks.view'));
    }

    public function update(User $user, TaskAttachment $attachment): bool
    {
        return $this->auth->canDeleteAttachment($user, $attachment);
    }

    public function delete(User $user, TaskAttachment $attachment): bool
    {
        return $this->auth->canDeleteAttachment($user, $attachment);
    }
}
