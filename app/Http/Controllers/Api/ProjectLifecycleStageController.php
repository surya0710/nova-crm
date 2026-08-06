<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectLifecycleStageRequest;
use App\Http\Requests\UpdateProjectLifecycleStageRequest;
use App\Http\Resources\ProjectLifecycleStageResource;
use App\Models\ProjectLifecycleStage;
use App\Services\ProjectLifecycleService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class ProjectLifecycleStageController extends Controller
{
    public function __construct(protected ProjectLifecycleService $lifecycleService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ProjectLifecycleStage::class);

        return ProjectLifecycleStageResource::collection(
            ProjectLifecycleStage::query()
                ->orderBy('sequence')
                ->orderBy('name')
                ->paginate($request->integer('per_page', 50))
        );
    }

    public function show(ProjectLifecycleStage $stage): ProjectLifecycleStageResource
    {
        $this->authorize('view', $stage);

        return new ProjectLifecycleStageResource($stage);
    }

    public function store(StoreProjectLifecycleStageRequest $request, TenantContext $tenant): JsonResponse
    {
        $stage = $this->lifecycleService->create(
            $tenant->get(),
            $request->validated(),
        );

        return (new ProjectLifecycleStageResource($stage))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateProjectLifecycleStageRequest $request, ProjectLifecycleStage $stage): ProjectLifecycleStageResource
    {
        $stage = $this->lifecycleService->update($stage, $request->validated());

        return new ProjectLifecycleStageResource($stage);
    }

    public function destroy(ProjectLifecycleStage $stage): JsonResponse
    {
        $this->authorize('delete', $stage);

        try {
            $this->lifecycleService->delete($stage);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json(['success' => true]);
    }
}
