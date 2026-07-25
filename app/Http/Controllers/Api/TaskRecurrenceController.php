<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRecurrenceRequest;
use App\Http\Requests\UpdateTaskRecurrenceRequest;
use App\Http\Resources\TaskRecurrenceResource;
use App\Models\Task;
use App\Models\TaskRecurrence;
use App\Services\TaskRecurrenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TaskRecurrenceController extends Controller
{
    public function __construct(protected TaskRecurrenceService $recurrenceService) {}

    public function store(StoreTaskRecurrenceRequest $request, Task $task): JsonResponse
    {
        try {
            $recurrence = $this->recurrenceService->create($task, $request->validated(), $request->user());
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        return (new TaskRecurrenceResource($recurrence))
            ->response()
            ->setStatusCode(201);
    }

    public function update(
        UpdateTaskRecurrenceRequest $request,
        Task $task,
        TaskRecurrence $recurrence,
    ): TaskRecurrenceResource|JsonResponse {
        $this->assertBelongsToTask($task, $recurrence);

        try {
            $recurrence = $this->recurrenceService->update($recurrence, $request->validated(), $request->user());
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        return new TaskRecurrenceResource($recurrence);
    }

    public function destroy(Request $request, Task $task, TaskRecurrence $recurrence): JsonResponse
    {
        $this->authorize('delete', $recurrence);
        $this->assertBelongsToTask($task, $recurrence);

        $this->recurrenceService->delete($recurrence, $request->user());

        return response()->json(['success' => true]);
    }

    protected function assertBelongsToTask(Task $task, TaskRecurrence $recurrence): void
    {
        abort_unless((int) $recurrence->task_id === (int) $task->id, 404);
    }
}
