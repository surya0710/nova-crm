<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\ProjectLifecycleStage;
use App\Models\ProjectMilestone;
use App\Models\ProjectStatus;
use App\Models\ProjectType;
use App\Models\User;
use App\Services\MetadataEntityFormService;
use App\Services\ProjectService;
use App\Services\TenantContext;
use App\Services\TimelineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function __construct(
        protected ProjectService $projectService,
        protected MetadataEntityFormService $metadataForms,
    ) {
        $this->authorizeResource(Project::class, 'project');
    }

    public function index(Request $request, TenantContext $tenant): View
    {
        $organization = $tenant->get();

        $query = Project::query()
            ->with(['category', 'projectType', 'status', 'lifecycleStage', 'owner', 'manager', 'client'])
            ->latest();

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
        } else {
            $query->where('is_archived', false);
        }

        return view('projects.index', [
            'projects' => $query->paginate(15)->withQueryString(),
            'organization' => $organization,
            'categories' => ProjectCategory::query()->orderBy('sort_order')->orderBy('name')->get(),
            'statuses' => ProjectStatus::query()->orderBy('sort_order')->orderBy('name')->get(),
            'users' => $this->organizationMembers($organization),
            'filters' => $request->only(['search', 'status_id', 'category_id', 'owner_id', 'manager_id', 'priority', 'is_archived']),
        ]);
    }

    public function dashboard(Request $request, TenantContext $tenant): View
    {
        $this->authorize('viewAny', Project::class);

        $organization = $tenant->get();
        $user = $request->user();

        $activeCount = Project::query()
            ->where('is_archived', false)
            ->whereHas('status', fn ($q) => $q->where('is_closed', false))
            ->count();

        $myProjectsCount = Project::query()
            ->where('is_archived', false)
            ->where(function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                    ->orWhere('manager_id', $user->id)
                    ->orWhereHas('members', fn ($m) => $m
                        ->where('user_id', $user->id)
                        ->where('is_active', true));
            })
            ->count();

        $upcomingDeadlines = Project::query()
            ->where('is_archived', false)
            ->whereNotNull('planned_end_date')
            ->whereBetween('planned_end_date', [now()->startOfDay(), now()->addDays(14)->endOfDay()])
            ->count();

        $milestonesDue = ProjectMilestone::query()
            ->whereIn('status', ['pending', 'in_progress'])
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [now()->startOfDay(), now()->addDays(7)->endOfDay()])
            ->count();

        return view('projects.dashboard', [
            'organization' => $organization,
            'summary' => [
                'active' => $activeCount,
                'my_projects' => $myProjectsCount,
                'upcoming_deadlines' => $upcomingDeadlines,
                'milestones_due' => $milestonesDue,
            ],
        ]);
    }

    public function create(TenantContext $tenant): View
    {
        return view('projects.create', [
            'project' => new Project(['priority' => 'medium']),
            ...$this->formOptions($tenant),
            'metadataFields' => $this->metadataForms->fieldsFor($tenant->get(), 'project', 'create'),
            'metadataPresenter' => $this->metadataForms->presenter(),
        ]);
    }

    public function store(StoreProjectRequest $request, TenantContext $tenant): RedirectResponse
    {
        $metadataValues = $this->metadataForms->validatedValuesFromRequest(null, $tenant->get(), 'project', 'create', $request);

        $project = $this->projectService->create(
            $request->validated(),
            $request->user(),
            $metadataValues,
        );

        return redirect()
            ->route('projects.show', $project)
            ->with('status', 'project-created');
    }

    public function show(Project $project): View
    {
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

        return view('projects.show', [
            'project' => $project,
            'metadataFields' => $this->metadataForms->fieldsFor($project->organization, 'project', 'detail'),
            'metadataPresenter' => $this->metadataForms->presenter(),
        ]);
    }

    public function edit(Project $project, TenantContext $tenant): View
    {
        return view('projects.edit', [
            'project' => $project,
            ...$this->formOptions($tenant),
            'metadataFields' => $this->metadataForms->fieldsFor($tenant->get(), 'project', 'edit'),
            'metadataPresenter' => $this->metadataForms->presenter(),
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project, TenantContext $tenant): RedirectResponse
    {
        $metadataValues = $this->metadataForms->validatedValuesFromRequest($project, $tenant->get(), 'project', 'edit', $request);

        $this->projectService->update(
            $project,
            $request->validated(),
            $request->user(),
            $metadataValues,
        );

        return redirect()
            ->route('projects.show', $project)
            ->with('status', 'project-updated');
    }

    public function destroy(Project $project, Request $request): RedirectResponse
    {
        $this->projectService->delete($project, $request->user());

        return redirect()
            ->route('projects.index')
            ->with('status', 'project-deleted');
    }

    public function archive(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('archive', $project);

        $this->projectService->archive($project, $request->user());

        return redirect()
            ->route('projects.show', $project)
            ->with('status', 'project-archived');
    }

    public function restore(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('restore', $project);

        $this->projectService->restore($project, $request->user());

        return redirect()
            ->route('projects.show', $project)
            ->with('status', 'project-restored');
    }

    public function timeline(Project $project, TimelineService $timelineService): View
    {
        $this->authorize('viewTimeline', $project);

        $project->load([
            'milestones' => fn ($q) => $q->orderBy('sequence')->orderBy('id'),
            'status',
            'lifecycleStage',
        ]);

        $timeline = $timelineService->build($project);

        return view('projects.timeline', [
            'project' => $project,
            'timeline' => $timeline,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function formOptions(TenantContext $tenant): array
    {
        $organization = $tenant->get();

        return [
            'categories' => ProjectCategory::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'types' => ProjectType::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'statuses' => ProjectStatus::query()->orderBy('sort_order')->orderBy('name')->get(),
            'stages' => ProjectLifecycleStage::query()->orderBy('sequence')->orderBy('name')->get(),
            'clients' => Customer::query()->orderBy('company')->orderBy('name')->get(),
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(),
            'users' => $this->organizationMembers($organization),
        ];
    }

    /**
     * @return Collection<int, User>
     */
    protected function organizationMembers(?Organization $organization): Collection
    {
        if (! $organization) {
            return collect();
        }

        return $organization->users()->orderBy('name')->get();
    }
}
