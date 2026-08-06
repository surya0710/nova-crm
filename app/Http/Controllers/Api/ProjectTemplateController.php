<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateProjectFromTemplateRequest;
use App\Http\Requests\SaveProjectAsTemplateRequest;
use App\Http\Requests\StoreProjectTemplateRequest;
use App\Http\Requests\UpdateProjectTemplateRequest;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\ProjectTemplateResource;
use App\Models\Project;
use App\Models\ProjectTemplate;
use App\Services\ProjectTemplateService;
use App\Services\TemplateCloneService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class ProjectTemplateController extends Controller
{
    public function __construct(
        protected ProjectTemplateService $templateService,
        protected TemplateCloneService $cloneService,
    ) {}

    public function index(Request $request, TenantContext $tenant): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ProjectTemplate::class);

        $templates = $this->templateService->list($tenant->id(), [
            'search' => $request->string('search')->trim()->toString() ?: null,
            'category' => $request->string('category')->toString() ?: null,
            'industry' => $request->string('industry')->toString() ?: null,
            'favorites' => $request->boolean('favorites') ?: null,
            'system' => $request->has('system') ? $request->boolean('system') : null,
        ]);

        return ProjectTemplateResource::collection($templates);
    }

    public function show(ProjectTemplate $template): ProjectTemplateResource
    {
        $this->authorize('view', $template);

        $template->load(['creator', 'department']);
        $template->loadCount(['templateMilestones', 'templateTasks']);

        return new ProjectTemplateResource($template);
    }

    public function store(StoreProjectTemplateRequest $request, TenantContext $tenant): JsonResponse
    {
        $template = $this->templateService->create([
            ...$request->validated(),
            'organization_id' => $tenant->id(),
        ], $request->user());

        return (new ProjectTemplateResource($template))
            ->response()
            ->setStatusCode(201);
    }

    public function update(
        UpdateProjectTemplateRequest $request,
        ProjectTemplate $template,
    ): ProjectTemplateResource|JsonResponse {
        try {
            $template = $this->templateService->update($template, $request->validated(), $request->user());
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        return new ProjectTemplateResource($template);
    }

    public function destroy(ProjectTemplate $template, Request $request): JsonResponse
    {
        $this->authorize('delete', $template);

        try {
            $this->templateService->delete($template, $request->user());
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json(['success' => true]);
    }

    public function saveFromProject(SaveProjectAsTemplateRequest $request, Project $project): JsonResponse
    {
        $template = $this->templateService->saveFromProject(
            $project,
            $request->validated(),
            $request->user(),
        );

        return (new ProjectTemplateResource($template))
            ->response()
            ->setStatusCode(201);
    }

    public function createFromTemplate(
        CreateProjectFromTemplateRequest $request,
        ProjectTemplate $template,
    ): JsonResponse {
        $project = $this->cloneService->createProjectFromTemplate(
            $template,
            $request->validated(),
            $request->user(),
        );

        return (new ProjectResource($project->load(['category', 'projectType', 'status', 'owner', 'manager'])))
            ->response()
            ->setStatusCode(201);
    }

    public function duplicate(Request $request, ProjectTemplate $template): JsonResponse
    {
        $this->authorize('create', ProjectTemplate::class);
        $this->authorize('view', $template);

        $copy = $this->templateService->duplicate($template, $request->user());

        return (new ProjectTemplateResource($copy))
            ->response()
            ->setStatusCode(201);
    }

    public function favorite(Request $request, ProjectTemplate $template): ProjectTemplateResource
    {
        $this->authorize('update', $template);

        $template = $this->templateService->toggleFavorite($template, $request->user());

        return new ProjectTemplateResource($template);
    }
}
