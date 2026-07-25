<?php

namespace App\Http\Controllers\Api\Identity;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\CreateEmployeeLoginAccountRequest;
use App\Http\Requests\Hrms\LinkEmployeeUserRequest;
use App\Models\Employee;
use App\Models\User;
use App\Services\Hrms\EmployeeService;
use App\Services\Identity\UserAccountService;
use App\Services\Identity\UserInvitationService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class IdentityApiController extends Controller
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected EmployeeService $employeeService,
        protected UserInvitationService $invitations,
        protected UserAccountService $accounts,
    ) {}

    public function createLoginAccount(CreateEmployeeLoginAccountRequest $request, Employee $employee): JsonResponse
    {
        if ($employee->user_id) {
            return response()->json([
                'message' => __('This employee already has a login account.'),
            ], 422);
        }

        $employee = $this->employeeService->createAndLinkUser($employee, [
            ...$request->validated(),
            'create_user' => true,
        ], $request->user());

        return response()->json([
            'message' => __('Login account created.'),
            'employee' => $employee->load('user'),
            'invitation' => $this->invitations->invitationStatus($employee->user, $this->requireOrganization()),
        ], 201);
    }

    public function sendInvitation(Request $request, User $user): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('users.manage') || $request->user()?->hasPermission('hrms.manage'), 403);

        $organization = $this->requireOrganization();
        abort_unless($user->belongsToOrganization($organization), 404);

        $invitation = $this->invitations->resend($user, $organization, $request->user());

        return response()->json([
            'message' => __('Invitation sent.'),
            'invitation_id' => $invitation->id,
            'expires_at' => $invitation->expires_at?->toIso8601String(),
        ]);
    }

    public function invitationStatus(Request $request, User $user): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('users.view') || $request->user()?->hasPermission('hrms.view'), 403);

        $organization = $this->requireOrganization();
        abort_unless($user->belongsToOrganization($organization), 404);

        return response()->json($this->invitations->invitationStatus($user, $organization));
    }

    public function enablePortal(Request $request, User $user): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('users.manage') || $request->user()?->hasPermission('hrms.manage'), 403);

        $organization = $this->requireOrganization();
        $user = $this->accounts->enablePortal($user, $organization, $request->user());

        return response()->json([
            'message' => __('Portal access enabled.'),
            'portal_access_enabled' => $user->portal_access_enabled,
        ]);
    }

    public function disablePortal(Request $request, User $user): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('users.manage') || $request->user()?->hasPermission('hrms.manage'), 403);

        $organization = $this->requireOrganization();
        $user = $this->accounts->disablePortal($user, $organization, $request->user());

        return response()->json([
            'message' => __('Portal access disabled.'),
            'portal_access_enabled' => $user->portal_access_enabled,
        ]);
    }

    public function resetPassword(Request $request, User $user): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('users.manage') || $request->user()?->hasPermission('hrms.manage'), 403);

        $organization = $this->requireOrganization();
        $this->accounts->sendPasswordReset($user, $organization, $request->user());

        return response()->json([
            'message' => __('Password reset link sent.'),
        ]);
    }

    public function activate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $this->invitations->accept($data['token'], $data['password']);

        return response()->json([
            'message' => __('Account activated.'),
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'account_status' => $user->account_status?->value,
            ],
        ]);
    }

    protected function requireOrganization()
    {
        $organization = $this->tenantContext->get();
        abort_unless($organization, 404);

        return $organization;
    }
}
