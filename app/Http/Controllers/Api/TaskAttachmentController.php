<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskAttachmentRequest;
use App\Http\Resources\TaskAttachmentResource;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Services\TaskAttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaskAttachmentController extends Controller
{
    public function __construct(protected TaskAttachmentService $attachmentService) {}

    public function index(Task $task): AnonymousResourceCollection
    {
        $this->authorize('view', $task);

        return TaskAttachmentResource::collection(
            $task->attachments()->with('uploader')->latest()->paginate(request()->integer('per_page', 50))
        );
    }

    public function store(StoreTaskAttachmentRequest $request, Task $task): JsonResponse
    {
        try {
            $attachment = $this->attachmentService->upload($task, $request->file('file'), $request->user());
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        $attachment->load('uploader');

        return (new TaskAttachmentResource($attachment))
            ->response()
            ->setStatusCode(201);
    }

    public function download(Task $task, TaskAttachment $attachment): StreamedResponse|JsonResponse
    {
        abort_unless((int) $attachment->task_id === (int) $task->id, 404);
        $this->authorize('view', $attachment);

        try {
            return $this->attachmentService->download($attachment);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function destroy(Task $task, TaskAttachment $attachment, Request $request): JsonResponse
    {
        abort_unless((int) $attachment->task_id === (int) $task->id, 404);
        $this->authorize('delete', $attachment);

        try {
            $this->attachmentService->delete($attachment, $request->user());
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json(['success' => true]);
    }
}
