<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexApiProjectRequest;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class ProjectController extends Controller
{
    public function __construct(protected ProjectService $projectService) {}

    public function index(IndexApiProjectRequest $request): AnonymousResourceCollection
    {
        $query = Project::query()
            ->with(['category', 'projectType', 'status', 'lifecycleStage', 'owner', 'manager', 'client']);

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('project_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($statusId = $request->integer('status_id')) {
            $query->where('status_id', $statusId);
        }

        if ($categoryId = $request->integer('category_id')) {
            $query->where('category_id', $categoryId);
        }

        if ($ownerId = $request->integer('owner_id')) {
            $query->where('owner_id', $ownerId);
        }

        if ($managerId = $request->integer('manager_id')) {
            $query->where('manager_id', $managerId);
        }

        if ($priority = $request->string('priority')->toString()) {
            $query->where('priority', $priority);
        }

        if ($request->has('is_archived')) {
            $query->where('is_archived', $request->boolean('is_archived'));
        }

        return ProjectResource::collection(
            $query->latest()->paginate($request->perPage())
        );
    }

    public function show(Request $request, Project $project): ProjectResource
    {
        $this->authorize('view', $project);

        $project->load([
            'category',
            'projectType',
            'status',
            'lifecycleStage',
            'client',
            'owner',
            'manager',
            'department',
            'members.user',
            'milestones',
        ]);

        return new ProjectResource($project);
    }

    public function store(StoreProjectRequest $request, TenantContext $tenant): JsonResponse
    {
        $organization = $tenant->get();

        if (! $organization) {
            return response()->json([
                'message' => __('Organization context is required.'),
            ], 422);
        }

        $project = $this->projectService->create(
            $request->validated(),
            $request->user(),
        );

        $project->load(['category', 'projectType', 'status', 'lifecycleStage', 'owner', 'manager']);

        return (new ProjectResource($project))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateProjectRequest $request, Project $project): ProjectResource
    {
        $project = $this->projectService->update(
            $project,
            $request->validated(),
            $request->user(),
        );

        $project->load(['category', 'projectType', 'status', 'lifecycleStage', 'owner', 'manager']);

        return new ProjectResource($project);
    }

    public function destroy(Request $request, Project $project): JsonResponse
    {
        $this->authorize('delete', $project);

        try {
            $this->projectService->delete($project, $request->user());
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json(['success' => true]);
    }

    public function archive(Request $request, Project $project): ProjectResource
    {
        $this->authorize('archive', $project);

        $project = $this->projectService->archive($project, $request->user());
        $project->load(['status', 'owner', 'manager']);

        return new ProjectResource($project);
    }

    public function restore(Request $request, Project $project): ProjectResource
    {
        $this->authorize('restore', $project);

        $project = $this->projectService->restore($project, $request->user());
        $project->load(['status', 'owner', 'manager']);

        return new ProjectResource($project);
    }
}
