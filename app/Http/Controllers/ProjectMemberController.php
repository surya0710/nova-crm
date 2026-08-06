<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectMemberRequest;
use App\Http\Requests\UpdateProjectMemberRequest;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use App\Services\ProjectMemberService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ProjectMemberController extends Controller
{
    public function __construct(protected ProjectMemberService $memberService) {}

    public function index(Project $project, TenantContext $tenant): View
    {
        $this->authorize('view', $project);

        $project->load(['members' => fn ($q) => $q->with('user')->orderBy('project_role')]);

        return view('projects.members.index', [
            'project' => $project,
            'members' => $project->members,
            'users' => $this->organizationMembers($tenant->get()),
            'roles' => config('projects.roles'),
        ]);
    }

    public function store(StoreProjectMemberRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('assignMembers', $project);

        $validated = $request->validated();
        $member = User::query()->findOrFail($validated['user_id']);

        $this->memberService->add(
            $project,
            $member,
            $validated['project_role'],
            $request->user(),
        );

        return redirect()
            ->route('projects.members.index', $project)
            ->with('status', 'project-member-added');
    }

    public function update(UpdateProjectMemberRequest $request, Project $project, ProjectMember $member): RedirectResponse
    {
        $this->assertMemberBelongsToProject($project, $member);

        $this->memberService->changeRole(
            $member,
            $request->validated('project_role'),
            $request->user(),
        );

        return redirect()
            ->route('projects.members.index', $project)
            ->with('status', 'project-member-updated');
    }

    public function destroy(Project $project, ProjectMember $member, Request $request): RedirectResponse
    {
        $this->authorize('delete', $member);
        $this->assertMemberBelongsToProject($project, $member);

        $this->memberService->remove($project, $member, $request->user());

        return redirect()
            ->route('projects.members.index', $project)
            ->with('status', 'project-member-removed');
    }

    protected function assertMemberBelongsToProject(Project $project, ProjectMember $member): void
    {
        abort_unless((int) $member->project_id === (int) $project->id, 404);
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
