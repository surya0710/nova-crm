<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskStatusRequest;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Http\Resources\TaskStatusResource;
use App\Models\TaskStatus;
use App\Services\TaskStatusService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class TaskStatusController extends Controller
{
    public function __construct(protected TaskStatusService $statusService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', TaskStatus::class);

        return TaskStatusResource::collection(
            TaskStatus::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate($request->integer('per_page', 50))
        );
    }

    public function show(TaskStatus $status): TaskStatusResource
    {
        $this->authorize('view', $status);

        return new TaskStatusResource($status);
    }

    public function store(StoreTaskStatusRequest $request, TenantContext $tenant): JsonResponse
    {
        $status = $this->statusService->create(
            $tenant->get(),
            $request->validated(),
        );

        return (new TaskStatusResource($status))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateTaskStatusRequest $request, TaskStatus $status): TaskStatusResource
    {
        $status = $this->statusService->update($status, $request->validated());

        return new TaskStatusResource($status);
    }

    public function destroy(TaskStatus $status): JsonResponse
    {
        $this->authorize('delete', $status);

        try {
            $this->statusService->delete($status);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json(['success' => true]);
    }
}
