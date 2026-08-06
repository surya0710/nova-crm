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
class RolePermissionController extends Controller
{
    public function __construct(
        protected RolePermissionService $service,
        protected TenantContext $tenant,
    ) {}

    public function matrix(Request $request): View
    {
        $this->authorize('managePermissions', Permission::class);

        $organization = $this->tenant->get();
        abort_unless($organization, 404);

        $data = $this->service->matrix($organization, $request->query('module'));

        return view('rbac.matrix.index', [
            'organization' => $organization,
            'roles' => $data['roles'],
            'permissions' => $data['permissions'],
            'assignments' => $data['assignments'],
            'module' => $request->query('module'),
            'modules' => $data['permissions']->pluck('module')->unique()->sort()->values(),
        ]);
    }

    public function sync(SyncRolePermissionsRequest $request, Role $role): RedirectResponse
    {
        $organization = $this->tenant->get();
        abort_unless($organization, 404);

        $this->service->sync($organization, $role, $request->validated('permission_ids'), $request->user());

        return back()->with('status', 'permissions-synced');
    }

    public function bulkUpdate(BulkMatrixUpdateRequest $request): RedirectResponse
    {
        $organization = $this->tenant->get();
        abort_unless($organization, 404);

        $this->service->bulkUpdate($organization, $request->validated('matrix'), $request->user());

        return back()->with('status', 'matrix-updated');
    }
}
