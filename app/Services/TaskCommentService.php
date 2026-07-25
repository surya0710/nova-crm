<?php

namespace App\Services;

use App\Events\CommentAdded;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use App\Workflow\WorkflowRuntimeContext;
use Illuminate\Validation\ValidationException;

class TaskCommentService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Task $task, array $data, User $actor): TaskComment
    {
        if ($task->isArchived()) {
            throw ValidationException::withMessages([
                'task' => __('Archived tasks are read-only.'),
            ]);
        }

        $body = trim((string) ($data['comment'] ?? ''));

        if ($body === '') {
            throw ValidationException::withMessages([
                'comment' => __('A comment is required.'),
            ]);
        }

        $parentId = isset($data['parent_comment_id']) ? (int) $data['parent_comment_id'] : null;

        if ($parentId) {
            $parent = TaskComment::query()
                ->where('task_id', $task->id)
                ->whereKey($parentId)
                ->first();

            if (! $parent) {
                throw ValidationException::withMessages([
                    'parent_comment_id' => __('The parent comment was not found on this task.'),
                ]);
            }
        }

        $comment = TaskComment::query()->create([
            'organization_id' => $task->organization_id,
            'task_id' => $task->id,
            'user_id' => $actor->id,
            'comment' => $body,
            'parent_comment_id' => $parentId,
        ]);

        $runtime = app(WorkflowRuntimeContext::class);
        event(CommentAdded::forModel(
            $comment,
            [
                'actor_id' => $actor->id,
                'task_id' => $task->id,
                'parent_comment_id' => $parentId,
            ],
            causationId: $runtime->causationId,
            depth: $runtime->causationId ? $runtime->depth + 1 : 0,
        ));

        $this->notifyMentions($task, $comment, $actor);

        app(WatcherService::class)->notifyWatchers(
            $task,
            'task.comment_added',
            __(':actor commented on :task.', ['actor' => $actor->name, 'task' => $task->title]),
            $actor,
            __('New comment'),
        );

        return $comment->fresh(['user', 'replies']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(TaskComment $comment, array $data, User $actor): TaskComment
    {
        if ($comment->user_id !== $actor->id) {
            throw ValidationException::withMessages([
                'comment' => __('You can only edit your own comments.'),
            ]);
        }

        if ($comment->task?->isArchived()) {
            throw ValidationException::withMessages([
                'task' => __('Archived tasks are read-only.'),
            ]);
        }

        $body = trim((string) ($data['comment'] ?? ''));

        if ($body === '') {
            throw ValidationException::withMessages([
                'comment' => __('A comment is required.'),
            ]);
        }

        $comment->update([
            'comment' => $body,
            'edited_at' => now(),
        ]);

        return $comment->fresh(['user', 'replies']);
    }

    public function delete(TaskComment $comment, User $actor): void
    {
        if ($comment->user_id !== $actor->id) {
            throw ValidationException::withMessages([
                'comment' => __('You can only delete your own comments.'),
            ]);
        }

        if ($comment->task?->isArchived()) {
            throw ValidationException::withMessages([
                'task' => __('Archived tasks are read-only.'),
            ]);
        }

        $comment->delete();
    }

    /**
     * @return list<string>
     */
    public function extractMentions(string $body): array
    {
        return app(MentionService::class)->extractMentions($body);
    }

    protected function notifyMentions(Task $task, TaskComment $comment, User $actor): void
    {
        app(MentionService::class)->recordMentions(
            (int) $task->organization_id,
            $task->project,
            $task,
            TaskComment::class,
            $comment->id,
            $comment->comment,
            $actor,
        );
    }
}
