<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskPriorityRequest;
use App\Http\Requests\UpdateTaskPriorityRequest;
use App\Http\Resources\TaskPriorityResource;
use App\Models\TaskPriority;
use App\Services\TaskPriorityService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class TaskPriorityController extends Controller
{
    public function __construct(protected TaskPriorityService $priorityService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', TaskPriority::class);

        return TaskPriorityResource::collection(
            TaskPriority::query()
                ->orderBy('level')
                ->orderBy('name')
                ->paginate($request->integer('per_page', 50))
        );
    }

    public function show(TaskPriority $priority): TaskPriorityResource
    {
        $this->authorize('view', $priority);

        return new TaskPriorityResource($priority);
    }

    public function store(StoreTaskPriorityRequest $request, TenantContext $tenant): JsonResponse
    {
        $priority = $this->priorityService->create(
            $tenant->get(),
            $request->validated(),
        );

        return (new TaskPriorityResource($priority))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateTaskPriorityRequest $request, TaskPriority $priority): TaskPriorityResource
    {
        $priority = $this->priorityService->update($priority, $request->validated());

        return new TaskPriorityResource($priority);
    }

    public function destroy(TaskPriority $priority): JsonResponse
    {
        $this->authorize('delete', $priority);

        try {
            $this->priorityService->delete($priority);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json(['success' => true]);
    }
}
