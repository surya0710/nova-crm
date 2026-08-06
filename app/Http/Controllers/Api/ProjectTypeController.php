<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectTypeRequest;
use App\Http\Requests\UpdateProjectTypeRequest;
use App\Http\Resources\ProjectTypeResource;
use App\Models\ProjectType;
use App\Services\ProjectTypeService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class ProjectTypeController extends Controller
{
    public function __construct(protected ProjectTypeService $typeService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ProjectType::class);

        return ProjectTypeResource::collection(
            ProjectType::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate($request->integer('per_page', 50))
        );
    }

    public function show(ProjectType $type): ProjectTypeResource
    {
        $this->authorize('view', $type);

        return new ProjectTypeResource($type);
    }

    public function store(StoreProjectTypeRequest $request, TenantContext $tenant): JsonResponse
    {
        $type = $this->typeService->create(
            $tenant->get(),
            $request->validated(),
        );

        return (new ProjectTypeResource($type))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateProjectTypeRequest $request, ProjectType $type): ProjectTypeResource
    {
        $type = $this->typeService->update($type, $request->validated());

        return new ProjectTypeResource($type);
    }

    public function destroy(ProjectType $type): JsonResponse
    {
        $this->authorize('delete', $type);

        try {
            $this->typeService->delete($type);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json(['success' => true]);
    }
}
