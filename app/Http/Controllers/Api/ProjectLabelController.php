<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttachLabelRequest;
use App\Http\Requests\StoreProjectLabelRequest;
use App\Http\Requests\UpdateProjectLabelRequest;
use App\Http\Resources\ProjectLabelResource;
use App\Models\ProjectLabel;
use App\Models\Task;
use App\Services\ProjectLabelService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class ProjectLabelController extends Controller
{
    public function __construct(protected ProjectLabelService $labelService) {}

    public function index(Request $request, TenantContext $tenant): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ProjectLabel::class);

        $labels = $this->labelService->list($tenant->id(), [
            'search' => $request->string('search')->trim()->toString() ?: null,
            'is_system' => $request->has('is_system') ? $request->boolean('is_system') : null,
            'task_id' => $request->integer('task_id') ?: null,
        ]);

        return ProjectLabelResource::collection($labels);
    }

    public function show(ProjectLabel $label): ProjectLabelResource
    {
        $this->authorize('view', $label);

        return new ProjectLabelResource($label);
    }

    public function store(StoreProjectLabelRequest $request, TenantContext $tenant): JsonResponse
    {
        $label = $this->labelService->create([
            ...$request->validated(),
            'organization_id' => $tenant->id(),
        ], $request->user());

        return (new ProjectLabelResource($label))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateProjectLabelRequest $request, ProjectLabel $label): ProjectLabelResource|JsonResponse
    {
        try {
            $label = $this->labelService->update($label, $request->validated(), $request->user());
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        return new ProjectLabelResource($label);
    }

    public function destroy(ProjectLabel $label, Request $request): JsonResponse
    {
        $this->authorize('delete', $label);

        try {
            $this->labelService->delete($label, $request->user());
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json(['success' => true]);
    }

    public function attach(AttachLabelRequest $request, Task $task, ProjectLabel $label): JsonResponse
    {
        try {
            $this->labelService->attach($task, $label, $request->user());
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json(['success' => true], 201);
    }

    public function detach(AttachLabelRequest $request, Task $task, ProjectLabel $label): JsonResponse
    {
        $this->labelService->detach($task, $label, $request->user());

        return response()->json(['success' => true]);
    }
}
