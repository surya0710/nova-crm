<?php

namespace App\Policies;

use App\Models\TaskAttachment;
use App\Models\User;

class TaskAttachmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('tasks.view');
    }

    public function view(User $user, TaskAttachment $attachment): bool
    {
        return $user->hasPermission('tasks.view', $attachment->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('tasks.attachments');
    }

    public function update(User $user, TaskAttachment $attachment): bool
    {
        return $user->hasPermission('tasks.attachments', $attachment->organization);
    }

    public function delete(User $user, TaskAttachment $attachment): bool
    {
        return $user->hasPermission('tasks.attachments', $attachment->organization);
    }
}
