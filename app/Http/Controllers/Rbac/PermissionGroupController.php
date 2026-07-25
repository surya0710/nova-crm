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
class PermissionGroupController extends Controller
{
    public function __construct(
        protected PermissionGroupService $service,
        protected TenantContext $tenant,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', PermissionGroup::class);

        $organization = $this->tenant->get();
        abort_unless($organization, 404);

        return view('rbac.permission-groups.index', [
            'organization' => $organization,
            'groups' => $this->service->list($organization, $request->only('search', 'active')),
        ]);
    }

    public function create(): View
    {
        $this->authorize('createGroup', PermissionGroup::class);

        return view('rbac.permission-groups.create', [
            'organization' => $this->tenant->get(),
        ]);
    }

    public function store(StorePermissionGroupRequest $request): RedirectResponse
    {
        $organization = $this->tenant->get();
        abort_unless($organization, 404);

        $this->service->create($organization, $request->validated());

        return redirect()->route('rbac.permission-groups.index')->with('status', 'permission-group-created');
    }

    public function edit(PermissionGroup $permissionGroup): View
    {
        $this->authorize('updateGroup', $permissionGroup);

        return view('rbac.permission-groups.edit', [
            'organization' => $this->tenant->get(),
            'group' => $permissionGroup,
        ]);
    }

    public function update(UpdatePermissionGroupRequest $request, PermissionGroup $permissionGroup): RedirectResponse
    {
        $this->service->update($permissionGroup, $request->validated());

        return redirect()->route('rbac.permission-groups.index')->with('status', 'permission-group-updated');
    }

    public function archive(PermissionGroup $permissionGroup): RedirectResponse
    {
        $this->authorize('updateGroup', $permissionGroup);
        $this->service->archive($permissionGroup);

        return redirect()->route('rbac.permission-groups.index')->with('status', 'permission-group-archived');
    }
}
