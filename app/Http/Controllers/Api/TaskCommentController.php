<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskCommentRequest;
use App\Http\Requests\UpdateTaskCommentRequest;
use App\Http\Resources\TaskCommentResource;
use App\Models\Task;
use App\Models\TaskComment;
use App\Services\TaskCommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class TaskCommentController extends Controller
{
    public function __construct(protected TaskCommentService $commentService) {}

    public function index(Task $task): AnonymousResourceCollection
    {
        $this->authorize('view', $task);

        return TaskCommentResource::collection(
            $task->comments()
                ->with(['user', 'replies.user'])
                ->whereNull('parent_comment_id')
                ->oldest()
                ->paginate(request()->integer('per_page', 50))
        );
    }

    public function store(StoreTaskCommentRequest $request, Task $task): JsonResponse
    {
        try {
            $comment = $this->commentService->create($task, $request->validated(), $request->user());
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        $comment->load(['user', 'replies']);

        return (new TaskCommentResource($comment))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateTaskCommentRequest $request, Task $task, TaskComment $comment): TaskCommentResource|JsonResponse
    {
        abort_unless((int) $comment->task_id === (int) $task->id, 404);

        try {
            $comment = $this->commentService->update($comment, $request->validated(), $request->user());
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        $comment->load(['user', 'replies']);

        return new TaskCommentResource($comment);
    }

    public function destroy(Task $task, TaskComment $comment, Request $request): JsonResponse
    {
        abort_unless((int) $comment->task_id === (int) $task->id, 404);
        $this->authorize('delete', $comment);

        try {
            $this->commentService->delete($comment, $request->user());
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json(['success' => true]);
    }
}
