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
class PermissionController extends Controller
{
    public function __construct(
        protected PermissionService $service,
        protected TenantContext $tenant,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', PermissionGroup::class);

        $organization = $this->tenant->get();
        abort_unless($organization, 404);

        return view('rbac.permissions.index', [
            'organization' => $organization,
            'permissions' => $this->service->list($organization, $request->only('search', 'group_id', 'module', 'active')),
            'groups' => PermissionGroup::query()->forOrganization($organization)->active()->orderBy('sort_order')->get(),
            'filters' => $request->only('search', 'group_id', 'module'),
        ]);
    }

    public function update(UpdatePermissionRequest $request, Permission $permission): RedirectResponse
    {
        $this->service->update($permission, $request->validated(), $request->user());

        return back()->with('status', 'permission-updated');
    }

    public function activate(Permission $permission): RedirectResponse
    {
        $this->authorize('updatePermission', $permission);
        $this->service->activate($permission, auth()->user());

        return back()->with('status', 'permission-activated');
    }

    public function deactivate(Permission $permission): RedirectResponse
    {
        $this->authorize('updatePermission', $permission);
        $this->service->deactivate($permission, auth()->user());

        return back()->with('status', 'permission-deactivated');
    }
}
