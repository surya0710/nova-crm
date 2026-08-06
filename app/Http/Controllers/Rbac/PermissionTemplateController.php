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
class PermissionTemplateController extends Controller
{
    public function __construct(
        protected PermissionTemplateService $service,
        protected TenantContext $tenant,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', PermissionGroup::class);

        return view('rbac.templates.index', [
            'organization' => $this->tenant->get(),
            'templates' => $this->service->list(),
        ]);
    }

    public function show(PermissionTemplate $template): View
    {
        $this->authorize('viewAny', PermissionGroup::class);

        return view('rbac.templates.show', [
            'organization' => $this->tenant->get(),
            'preview' => $this->service->preview($template),
        ]);
    }

    public function install(InstallTemplateRequest $request): RedirectResponse
    {
        $organization = $this->tenant->get();
        abort_unless($organization, 404);

        $template = PermissionTemplate::query()->findOrFail($request->validated('template_id'));
        $this->service->install($template, $organization, $request->user());

        return redirect()->route('rbac.templates.index')->with('status', 'template-installed');
    }

    public function reset(Request $request): RedirectResponse
    {
        $this->authorize('installTemplate', PermissionTemplate::class);

        $organization = $this->tenant->get();
        abort_unless($organization, 404);

        $this->service->reset($organization, $request->user());

        return redirect()->route('rbac.templates.index')->with('status', 'template-reset');
    }
}
