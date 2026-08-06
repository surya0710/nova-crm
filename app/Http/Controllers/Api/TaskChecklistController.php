<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskChecklistRequest;
use App\Http\Requests\UpdateTaskChecklistRequest;
use App\Http\Resources\TaskChecklistResource;
use App\Models\Task;
use App\Models\TaskChecklist;
use App\Services\ChecklistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class TaskChecklistController extends Controller
{
    public function __construct(protected ChecklistService $checklistService) {}

    public function index(Task $task): AnonymousResourceCollection
    {
        $this->authorize('view', $task);

        return TaskChecklistResource::collection(
            $task->checklists()->orderBy('sequence')->paginate(request()->integer('per_page', 50))
        );
    }

    public function store(StoreTaskChecklistRequest $request, Task $task): JsonResponse
    {
        try {
            $item = $this->checklistService->create($task, $request->validated(), $request->user());
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        return (new TaskChecklistResource($item))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateTaskChecklistRequest $request, Task $task, TaskChecklist $checklist): TaskChecklistResource|JsonResponse
    {
        abort_unless((int) $checklist->task_id === (int) $task->id, 404);

        try {
            $validated = $request->validated();

            if (array_key_exists('is_completed', $validated)) {
                $checklist = $this->checklistService->complete($checklist, $request->user(), (bool) $validated['is_completed']);
                unset($validated['is_completed']);
            }

            if ($validated !== []) {
                $checklist = $this->checklistService->update($checklist, $validated, $request->user());
            }
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        return new TaskChecklistResource($checklist);
    }

    public function destroy(Task $task, TaskChecklist $checklist, Request $request): JsonResponse
    {
        abort_unless((int) $checklist->task_id === (int) $task->id, 404);
        $this->authorize('delete', $checklist);

        try {
            $this->checklistService->delete($checklist, $request->user());
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json(['success' => true]);
    }

    public function complete(Task $task, TaskChecklist $checklist, Request $request): TaskChecklistResource|JsonResponse
    {
        abort_unless((int) $checklist->task_id === (int) $task->id, 404);
        $this->authorize('manageChecklists', $task);

        try {
            $checklist = $this->checklistService->complete($checklist, $request->user(), true);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        return new TaskChecklistResource($checklist);
    }
}
