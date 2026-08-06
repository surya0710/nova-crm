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
class RoleController extends Controller
{
    public function __construct(
        protected RoleService $service,
        protected TenantContext $tenant,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', PermissionGroup::class);

        $organization = $this->tenant->get();
        abort_unless($organization, 404);

        return view('rbac.roles.index', [
            'organization' => $organization,
            'roles' => $this->service->list($organization, $request->only('search', 'active')),
        ]);
    }

    public function create(): View
    {
        $this->authorize('createRole', Role::class);

        return view('rbac.roles.create', [
            'organization' => $this->tenant->get(),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $organization = $this->tenant->get();
        abort_unless($organization, 404);

        $this->service->create($organization, $request->validated(), $request->user());

        return redirect()->route('rbac.roles.index')->with('status', 'role-created');
    }

    public function edit(Role $role): View
    {
        $this->authorize('updateRole', $role);

        return view('rbac.roles.edit', [
            'organization' => $this->tenant->get(),
            'role' => $role->load('permissions'),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $this->service->update($role, $request->validated(), $request->user());

        return redirect()->route('rbac.roles.index')->with('status', 'role-updated');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('deleteRole', $role);
        $this->service->delete($role, auth()->user());

        return redirect()->route('rbac.roles.index')->with('status', 'role-deleted');
    }

    public function duplicate(Role $role): RedirectResponse
    {
        $this->authorize('updateRole', $role);
        $this->service->duplicate($role, auth()->user());

        return redirect()->route('rbac.roles.index')->with('status', 'role-duplicated');
    }

    public function activate(Role $role): RedirectResponse
    {
        $this->authorize('updateRole', $role);
        $this->service->activate($role, auth()->user());

        return back()->with('status', 'role-activated');
    }

    public function deactivate(Role $role): RedirectResponse
    {
        $this->authorize('updateRole', $role);
        $this->service->deactivate($role, auth()->user());

        return back()->with('status', 'role-deactivated');
    }
}
