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
class AuthorizationLookupController extends Controller
{
    public function __construct(
        protected AuthorizationService $authorization,
        protected TenantContext $tenant,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PermissionGroup::class);

        $organization = $this->tenant->get();
        abort_unless($organization, 404);

        $user = $request->user();
        $permission = $request->query('permission');

        if ($permission) {
            return response()->json([
                'permission' => $permission,
                'allowed' => $this->authorization->can($user, $permission, $organization),
            ]);
        }

        return response()->json([
            'permissions' => $this->authorization->effectivePermissions($user, $organization)->values(),
        ]);
    }
}
