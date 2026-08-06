<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectStatusRequest;
use App\Http\Requests\UpdateProjectStatusRequest;
use App\Http\Resources\ProjectStatusResource;
use App\Models\ProjectStatus;
use App\Services\ProjectStatusService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class ProjectStatusController extends Controller
{
    public function __construct(protected ProjectStatusService $statusService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ProjectStatus::class);

        return ProjectStatusResource::collection(
            ProjectStatus::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate($request->integer('per_page', 50))
        );
    }

    public function show(ProjectStatus $status): ProjectStatusResource
    {
        $this->authorize('view', $status);

        return new ProjectStatusResource($status);
    }

    public function store(StoreProjectStatusRequest $request, TenantContext $tenant): JsonResponse
    {
        $status = $this->statusService->create(
            $tenant->get(),
            $request->validated(),
        );

        return (new ProjectStatusResource($status))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateProjectStatusRequest $request, ProjectStatus $status): ProjectStatusResource
    {
        $status = $this->statusService->update($status, $request->validated());

        return new ProjectStatusResource($status);
    }

    public function destroy(ProjectStatus $status): JsonResponse
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
