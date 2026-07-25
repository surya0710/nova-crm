<?php

namespace App\Http\Controllers\Rbac;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rbac\BulkMatrixUpdateRequest;
use App\Http\Requests\Rbac\InstallTemplateRequest;
use App\Http\Requests\Rbac\StorePermissionGroupRequest;
use App\Http\Requests\Rbac\StoreRoleRequest;
use App\Http\Requests\Rbac\SyncRolePermissionsRequest;
use App\Http\Requests\Rbac\SyncUserRolesRequest;
use App\Http\Requests\Rbac\UpdatePermissionGroupRequest;
use App\Http\Requests\Rbac\UpdatePermissionRequest;
use App\Http\Requests\Rbac\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\PermissionTemplate;
use App\Models\Role;
use App\Models\User;
use App\Services\Rbac\AuthorizationService;
use App\Services\Rbac\PermissionGroupService;
use App\Services\Rbac\PermissionService;
use App\Services\Rbac\PermissionTemplateService;
use App\Services\Rbac\RolePermissionService;
use App\Services\Rbac\RoleService;
use App\Services\Rbac\UserRoleService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
class UserRoleController extends Controller
{
    public function __construct(
        protected UserRoleService $service,
        protected TenantContext $tenant,
    ) {}

    public function index(): View
    {
        $this->authorize('manageUserRoles', User::class);

        $organization = $this->tenant->get();
        abort_unless($organization, 404);

        $members = $organization->users()->orderBy('name')->get();
        $roles = Role::query()->where('organization_id', $organization->id)->active()->orderByDesc('hierarchy_level')->get();

        return view('rbac.user-roles.index', [
            'organization' => $organization,
            'members' => $members,
            'roles' => $roles,
        ]);
    }

    public function show(User $user): View
    {
        $this->authorize('manageUserRoles', User::class);

        $organization = $this->tenant->get();
        abort_unless($organization, 404);

        return view('rbac.user-roles.show', [
            'organization' => $organization,
            'member' => $user,
            'assignedRoles' => $this->service->rolesForUser($user, $organization),
            'effectivePermissions' => $this->service->effectivePermissions($user, $organization),
            'availableRoles' => Role::query()->where('organization_id', $organization->id)->active()->orderByDesc('hierarchy_level')->get(),
        ]);
    }

    public function sync(SyncUserRolesRequest $request, User $user): RedirectResponse
    {
        $organization = $this->tenant->get();
        abort_unless($organization, 404);

        $this->service->sync(
            $user,
            $organization,
            $request->validated('role_ids'),
            $request->user(),
            $request->validated('primary_role_id'),
        );

        return redirect()->route('rbac.user-roles.show', $user)->with('status', 'user-roles-updated');
    }
}
