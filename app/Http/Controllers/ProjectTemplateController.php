<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateProjectFromTemplateRequest;
use App\Http\Requests\SaveProjectAsTemplateRequest;
use App\Http\Requests\StoreProjectTemplateRequest;
use App\Http\Requests\UpdateProjectTemplateRequest;
use App\Models\Project;
use App\Models\ProjectTemplate;
use App\Services\ProjectTemplateService;
use App\Services\TemplateCloneService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProjectTemplateController extends Controller
{
    public function __construct(
        protected ProjectTemplateService $templateService,
        protected TemplateCloneService $cloneService,
    ) {
        $this->authorizeResource(ProjectTemplate::class, 'template');
    }

    public function index(Request $request, TenantContext $tenant): View
    {
        $templates = $this->templateService->list($tenant->id(), [
            'search' => $request->string('search')->trim()->toString() ?: null,
            'category' => $request->string('category')->toString() ?: null,
            'industry' => $request->string('industry')->toString() ?: null,
            'favorites' => $request->boolean('favorites') ?: null,
            'system' => $request->has('system') ? $request->boolean('system') : null,
        ]);

        return view('projects.templates.index', [
            'templates' => $templates,
            'organization' => $tenant->get(),
        ]);
    }

    public function create(TenantContext $tenant): View
    {
        return view('projects.templates.create', [
            'template' => new ProjectTemplate,
            'organization' => $tenant->get(),
        ]);
    }

    public function store(StoreProjectTemplateRequest $request, TenantContext $tenant): RedirectResponse
    {
        $template = $this->templateService->create([
            ...$request->validated(),
            'organization_id' => $tenant->id(),
        ], $request->user());

        return redirect()
            ->route('project-templates.show', $template)
            ->with('status', 'project-template-created');
    }

    public function show(ProjectTemplate $template): View
    {
        $template->load(['creator', 'department', 'templateMilestones', 'templateTasks', 'templateLabels']);

        return view('projects.templates.show', [
            'template' => $template,
        ]);
    }

    public function edit(ProjectTemplate $template): View
    {
        return view('projects.templates.edit', [
            'template' => $template,
        ]);
    }

    public function update(UpdateProjectTemplateRequest $request, ProjectTemplate $template): RedirectResponse
    {
        try {
            $this->templateService->update($template, $request->validated(), $request->user());
        } catch (ValidationException $e) {
            throw $e;
        }

        return redirect()
            ->route('project-templates.show', $template)
            ->with('status', 'project-template-updated');
    }

    public function destroy(ProjectTemplate $template, Request $request): RedirectResponse
    {
        try {
            $this->templateService->delete($template, $request->user());
        } catch (ValidationException $e) {
            return redirect()
                ->route('project-templates.index')
                ->withErrors($e->errors());
        }

        return redirect()
            ->route('project-templates.index')
            ->with('status', 'project-template-deleted');
    }

    public function saveFromProject(SaveProjectAsTemplateRequest $request, Project $project): RedirectResponse
    {
        $template = $this->templateService->saveFromProject(
            $project,
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('project-templates.show', $template)
            ->with('status', 'project-saved-as-template');
    }

    public function createFromTemplate(
        CreateProjectFromTemplateRequest $request,
        ProjectTemplate $template,
    ): RedirectResponse {
        $project = $this->cloneService->createProjectFromTemplate(
            $template,
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('projects.show', $project)
            ->with('status', 'project-created-from-template');
    }

    public function duplicate(Request $request, ProjectTemplate $template): RedirectResponse
    {
        $this->authorize('create', ProjectTemplate::class);
        $this->authorize('view', $template);

        $copy = $this->templateService->duplicate($template, $request->user());

        return redirect()
            ->route('project-templates.show', $copy)
            ->with('status', 'project-template-duplicated');
    }

    public function favorite(Request $request, ProjectTemplate $template): RedirectResponse
    {
        $this->authorize('update', $template);

        $this->templateService->toggleFavorite($template, $request->user());

        return redirect()
            ->back()
            ->with('status', 'project-template-favorite-toggled');
    }
}
