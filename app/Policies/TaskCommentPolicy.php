<?php

namespace App\Policies;

use App\Models\TaskComment;
use App\Models\User;

class TaskCommentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('tasks.view');
    }

    public function view(User $user, TaskComment $comment): bool
    {
        return $user->hasPermission('tasks.view', $comment->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('tasks.comment');
    }

    public function update(User $user, TaskComment $comment): bool
    {
        return $user->hasPermission('tasks.comment', $comment->organization);
    }

    public function delete(User $user, TaskComment $comment): bool
    {
        return $user->hasPermission('tasks.comment', $comment->organization);
    }
}
