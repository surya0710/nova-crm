<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Services\Dashboard\DashboardCache;
use App\Services\Rbac\AuthorizationService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrganizationSwitchController extends Controller
{
    public function __construct(
        protected AuthorizationService $authorization,
        protected DashboardCache $dashboardCache,
        protected TenantContext $tenant,
    ) {}

    public function __invoke(Request $request, Organization $organization): RedirectResponse
    {
        $user = $request->user();
        $membership = $user->activeOrganizations()
            ->where('organizations.id', $organization->id)
            ->first();

        abort_unless($membership, 403);

        $previousOrganizationId = (int) $request->session()->get('current_organization_id');

        if ($previousOrganizationId > 0) {
            $this->authorization->forgetUserCache($user, $previousOrganizationId);
        }
        $this->authorization->forgetUserCache($user, $membership);
        $this->dashboardCache->clearUser($membership->id, $user->id);

        $this->tenant->set($membership);
        $user->unsetRelation('organizations');

        $request->session()->put([
            'current_organization_id' => $membership->id,
            'current_organization_name' => $membership->name,
            'current_membership' => [
                'id' => $membership->pivot->id,
                'organization_id' => $membership->id,
                'user_id' => $user->id,
                'role' => $membership->pivot->role,
                'role_id' => $membership->pivot->role_id,
                'is_owner' => (bool) $membership->pivot->is_owner,
                'is_active' => (bool) $membership->pivot->is_active,
            ],
        ]);

        return redirect()
            ->route('dashboard')
            ->with('status', 'organization-switched');
    }
}
