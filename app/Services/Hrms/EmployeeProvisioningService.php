<?php

namespace App\Services\Hrms;

use App\Enums\UserAccountStatus;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Dashboard\DashboardProvisioningService;
use App\Services\Identity\UserAccountService;
use App\Services\Identity\UserInvitationService;
use App\Services\NotificationService;
use App\Services\TenantContext;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Single entry point for provisioning employees across Organization Settings,
 * HRMS, API, and import flows.
 *
 * Always creates (as applicable): User, Employee, profile relations,
 * organization membership, role assignment, invitation, and optional dashboard/notifications.
 */
class EmployeeProvisioningService
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected EmployeeService $employeeService,
        protected AuditLogger $auditLogger,
        protected NotificationService $notificationService,
        protected DashboardProvisioningService $dashboardProvisioning,
        protected UserInvitationService $invitations,
        protected UserAccountService $accounts,
    ) {}

    /**
     * Provision a full employee from any entry point.
     *
     * @param  array{
     *     first_name: string,
     *     last_name?: string|null,
     *     email?: string|null,
     *     create_user?: bool,
     *     user_id?: int|null,
     *     user?: array{name?: string, email?: string, role?: string}|null,
     *     role?: string,
     *     notify?: bool,
     *     send_invitation?: bool,
     *     portal_access?: bool,
     *     provision_dashboard?: bool,
     * }  $data
     */
    public function provision(array $data, User $actor, ?Organization $organization = null): Employee
    {
        $organization ??= $this->requireOrganization();

        return DB::transaction(function () use ($data, $actor, $organization): Employee {
            $createUser = (bool) ($data['create_user'] ?? ! empty($data['user']) || ! empty($data['user_id']));
            $roleSlug = (string) ($data['role'] ?? $data['user']['role'] ?? config('identity.default_employee_role', 'employee'));
            $employeeData = Arr::except($data, [
                'create_user', 'user', 'user_id', 'role', 'notify', 'provision_dashboard', 'password',
                'user_email', 'entry_point', 'send_invitation', 'portal_access',
            ]);

            $user = null;
            $createdNewUser = false;

            if ($createUser) {
                [$user, $createdNewUser] = $this->resolveOrCreateUser($organization, $data, $roleSlug);
                $employeeData['user_id'] = $user->id;

                if (empty($employeeData['email']) && $user->email) {
                    $employeeData['email'] = $user->email;
                }
            }

            $employee = $this->employeeService->createEmployee($employeeData, $actor);

            if ($user !== null && (int) $employee->user_id !== (int) $user->id) {
                $this->employeeService->linkUser($employee, $user, $actor);
                $employee->refresh();
            }

            if ($user !== null) {
                $portalAccess = (bool) ($data['portal_access'] ?? true);
                $user->forceFill(['portal_access_enabled' => $portalAccess])->save();

                $sendInvitation = (bool) ($data['send_invitation'] ?? $createdNewUser);
                if ($sendInvitation && ($user->account_status === UserAccountStatus::PendingInvitation || $createdNewUser)) {
                    $this->invitations->invite($user, $organization, $actor, [
                        'send_email' => ($data['notify'] ?? true) === true,
                    ]);
                } elseif (($data['notify'] ?? true) === true && ! $sendInvitation) {
                    $this->notifyProvisioned($organization, $employee);
                }
            }

            if (($data['provision_dashboard'] ?? false) === true) {
                $this->dashboardProvisioning->provision($organization);
            }

            $this->auditLogger->log($employee, 'employee_provisioned', [
                'user_id' => $employee->user_id,
                'role' => $roleSlug,
                'entry_point' => $data['entry_point'] ?? 'hrms',
                'invitation_sent' => (bool) ($data['send_invitation'] ?? $createdNewUser),
            ], $actor);

            return $employee->fresh(['user', 'branch', 'department', 'designation', 'emergencyContacts']);
        });
    }

    /**
     * Ensure an existing employee has a linked user + membership + role + invitation.
     *
     * @param  array{name?: string, email: string, role?: string, notify?: bool, send_invitation?: bool, portal_access?: bool}  $data
     */
    public function provisionUserForEmployee(Employee $employee, array $data, User $actor): Employee
    {
        $organization = $this->requireOrganization();

        return DB::transaction(function () use ($employee, $data, $actor, $organization): Employee {
            if ($employee->user_id) {
                throw ValidationException::withMessages([
                    'user_id' => __('This employee is already linked to a user account.'),
                ]);
            }

            $roleSlug = (string) ($data['role'] ?? config('identity.default_employee_role', 'employee'));
            [$user, $createdNewUser] = $this->resolveOrCreateUser($organization, [
                'user' => [
                    'name' => $data['name'] ?? $employee->full_name,
                    'email' => $data['email'],
                    'role' => $roleSlug,
                ],
            ], $roleSlug);

            $employee = $this->employeeService->linkUser($employee, $user, $actor);

            $portalAccess = (bool) ($data['portal_access'] ?? true);
            $user->forceFill(['portal_access_enabled' => $portalAccess])->save();

            $sendInvitation = (bool) ($data['send_invitation'] ?? true);
            if ($sendInvitation) {
                $this->invitations->invite($user, $organization, $actor, [
                    'send_email' => ($data['notify'] ?? true) === true,
                ]);
            } elseif (($data['notify'] ?? true) === true) {
                $this->notifyProvisioned($organization, $employee);
            }

            return $employee->fresh(['user']);
        });
    }

    /**
     * Import-friendly batch provision (single row).
     *
     * @param  array<string, mixed>  $row
     */
    public function provisionFromImport(array $row, User $actor, ?Organization $organization = null): Employee
    {
        return $this->provision([
            ...$row,
            'create_user' => (bool) ($row['create_user'] ?? ! empty($row['email'])),
            'entry_point' => 'import',
            'notify' => (bool) ($row['notify'] ?? false),
            'send_invitation' => (bool) ($row['send_invitation'] ?? true),
        ], $actor, $organization);
    }

    /**
     * API-friendly provision wrapper.
     *
     * @param  array<string, mixed>  $data
     */
    public function provisionFromApi(array $data, User $actor, ?Organization $organization = null): Employee
    {
        return $this->provision([
            ...$data,
            'entry_point' => 'api',
            'create_user' => (bool) ($data['create_user'] ?? true),
        ], $actor, $organization);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: User, 1: bool} [user, createdNewUser]
     */
    protected function resolveOrCreateUser(Organization $organization, array $data, string $roleSlug): array
    {
        if (! empty($data['user_id'])) {
            $user = User::query()->findOrFail((int) $data['user_id']);

            if (! $user->belongsToOrganization($organization)) {
                $organization->addMember($user, $roleSlug);
            }

            $existing = Employee::query()
                ->where('organization_id', $organization->id)
                ->where('user_id', $user->id)
                ->exists();

            if ($existing) {
                throw ValidationException::withMessages([
                    'user_id' => __('This user is already linked to another employee.'),
                ]);
            }

            return [$user, false];
        }

        $userPayload = $data['user'] ?? [];
        $email = strtolower(trim((string) ($userPayload['email'] ?? $data['email'] ?? $data['user_email'] ?? '')));

        if ($email === '') {
            throw ValidationException::withMessages([
                'email' => __('An email address is required to create a user account.'),
            ]);
        }

        $name = trim((string) ($userPayload['name'] ?? trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? ''))));
        if ($name === '') {
            $name = $email;
        }

        $existingUser = User::query()->where('email', $email)->first();

        if ($existingUser) {
            if (! $existingUser->belongsToOrganization($organization)) {
                $organization->addMember($existingUser, $roleSlug);
            }

            $linked = Employee::query()
                ->where('organization_id', $organization->id)
                ->where('user_id', $existingUser->id)
                ->exists();

            if ($linked) {
                throw ValidationException::withMessages([
                    'email' => __('This user is already linked to another employee in this organization.'),
                ]);
            }

            return [$existingUser, false];
        }

        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            // Unusable until invitation accepted — never admin-assigned.
            'password' => Hash::make(Str::password(32)),
            'account_status' => UserAccountStatus::PendingInvitation,
            'portal_access_enabled' => true,
            'email_verified_at' => null,
            'password_changed_at' => null,
        ]);

        $organization->addMember($user, $roleSlug);

        return [$user, true];
    }

    protected function notifyProvisioned(Organization $organization, Employee $employee): void
    {
        if (! $employee->user_id) {
            return;
        }

        try {
            $this->notificationService->send(
                $organization->id,
                (int) $employee->user_id,
                __('Welcome to :org', ['org' => $organization->name]),
                __('Your employee profile has been created. You can access self-service from My HR.'),
                '/ess'
            );
        } catch (\Throwable) {
            // Notification failures must not roll back provisioning.
        }
    }

    protected function requireOrganization(): Organization
    {
        $organization = $this->tenantContext->get();
        if (! $organization) {
            abort(404);
        }

        return $organization;
    }
}
