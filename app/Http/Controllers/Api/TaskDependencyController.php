<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskDependencyRequest;
use App\Http\Resources\TaskDependencyResource;
use App\Models\Task;
use App\Models\TaskDependency;
use App\Services\TaskDependencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class TaskDependencyController extends Controller
{
    public function __construct(protected TaskDependencyService $dependencyService) {}

    public function index(Task $task): AnonymousResourceCollection
    {
        $this->authorize('view', $task);

        $dependencies = TaskDependency::query()
            ->where(function ($q) use ($task) {
                $q->where('successor_task_id', $task->id)
                    ->orWhere('predecessor_task_id', $task->id);
            })
            ->with(['predecessor', 'successor'])
            ->orderBy('id')
            ->paginate(request()->integer('per_page', 50));

        return TaskDependencyResource::collection($dependencies);
    }

    public function store(StoreTaskDependencyRequest $request, Task $task): JsonResponse
    {
        $validated = $request->validated();

        try {
            $dependency = $this->dependencyService->create(
                Task::query()->findOrFail($validated['predecessor_task_id']),
                $task,
                ['dependency_type' => $validated['dependency_type'] ?? 'finish_to_start'],
                $request->user(),
            );
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        $dependency->load(['predecessor', 'successor']);

        return (new TaskDependencyResource($dependency))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Task $task, TaskDependency $dependency, Request $request): JsonResponse
    {
        abort_unless(
            (int) $dependency->successor_task_id === (int) $task->id
            || (int) $dependency->predecessor_task_id === (int) $task->id,
            404
        );
        $this->authorize('delete', $dependency);

        try {
            $this->dependencyService->delete($dependency, $request->user());
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json(['success' => true]);
    }
}
