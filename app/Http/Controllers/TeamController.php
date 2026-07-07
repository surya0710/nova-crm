<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTeamMemberRequest;
use App\Http\Requests\UpdateTeamMemberRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\OrganizationMemberService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function __construct(protected OrganizationMemberService $memberService) {}

    public function index(TenantContext $tenant): View
    {
        $this->authorize('viewAny', User::class);

        $organization = $tenant->get();
        abort_unless($organization, 404);

        $members = $organization->users()
            ->orderBy('name')
            ->get()
            ->map(function (User $member) use ($organization) {
                $member->setRelation(
                    'organizationRole',
                    $member->getRoleInOrganization($organization)
                );

                return $member;
            });

        $assignableRoles = Role::query()
            ->where('organization_id', $organization->id)
            ->whereIn('slug', OrganizationMemberService::assignableRoleSlugs())
            ->orderBy('name')
            ->get();

        return view('team.index', [
            'organization' => $organization,
            'members' => $members,
            'assignableRoles' => $assignableRoles,
        ]);
    }

    public function store(StoreTeamMemberRequest $request, TenantContext $tenant): RedirectResponse
    {
        $organization = $tenant->get();
        abort_unless($organization, 404);

        $this->memberService->addMember($organization, $request->validated());

        return redirect()
            ->route('team.index')
            ->with('status', 'team-member-added');
    }

    public function update(UpdateTeamMemberRequest $request, User $member, TenantContext $tenant): RedirectResponse
    {
        $organization = $tenant->get();
        abort_unless($organization, 404);

        $this->memberService->updateMemberRole(
            $organization,
            $member,
            $request->validated('role')
        );

        return redirect()
            ->route('team.index')
            ->with('status', 'team-member-updated');
    }

    public function destroy(User $member, TenantContext $tenant): RedirectResponse
    {
        $this->authorize('delete', $member);

        $organization = $tenant->get();
        abort_unless($organization, 404);

        $this->memberService->removeMember($organization, $member);

        return redirect()
            ->route('team.index')
            ->with('status', 'team-member-removed');
    }
}
