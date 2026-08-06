<?php

namespace App\Policies;

use App\Models\TaskComment;
use App\Models\User;
use App\Services\TaskAuthorizationService;

class TaskCommentPolicy
{
    public function __construct(protected TaskAuthorizationService $auth) {}

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('tasks.view');
    }

    public function view(User $user, TaskComment $comment): bool
    {
        $task = $comment->task;

        return $task !== null && $this->auth->canView($user, $task);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('tasks.comment')
            || $user->hasPermission('tasks.view');
    }

    public function update(User $user, TaskComment $comment): bool
    {
        return $this->auth->canUpdateComment($user, $comment);
    }

    public function delete(User $user, TaskComment $comment): bool
    {
        return $this->auth->canDeleteComment($user, $comment);
    }
}
