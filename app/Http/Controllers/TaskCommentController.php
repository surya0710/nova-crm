<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskCommentRequest;
use App\Http\Requests\UpdateTaskCommentRequest;
use App\Models\Task;
use App\Models\TaskComment;
use App\Services\TaskCommentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskCommentController extends Controller
{
    public function __construct(protected TaskCommentService $commentService) {}

    public function index(Task $task): View
    {
        $this->authorize('view', $task);

        return view('tasks.comments.index', [
            'task' => $task,
            'comments' => $task->comments()->with(['user', 'replies.user'])->whereNull('parent_comment_id')->oldest()->get(),
        ]);
    }

    public function store(StoreTaskCommentRequest $request, Task $task): RedirectResponse
    {
        $this->commentService->create($task, $request->validated(), $request->user());

        return redirect()
            ->route('tasks.show', $task)
            ->with('status', 'task-comment-created');
    }

    public function update(UpdateTaskCommentRequest $request, Task $task, TaskComment $comment): RedirectResponse
    {
        $this->assertBelongsToTask($task, $comment);

        $this->commentService->update($comment, $request->validated(), $request->user());

        return redirect()
            ->route('tasks.show', $task)
            ->with('status', 'task-comment-updated');
    }

    public function destroy(Task $task, TaskComment $comment, Request $request): RedirectResponse
    {
        $this->assertBelongsToTask($task, $comment);
        $this->authorize('delete', $comment);

        $this->commentService->delete($comment, $request->user());

        return redirect()
            ->route('tasks.show', $task)
            ->with('status', 'task-comment-deleted');
    }

    protected function assertBelongsToTask(Task $task, TaskComment $comment): void
    {
        abort_unless((int) $comment->task_id === (int) $task->id, 404);
    }
}
