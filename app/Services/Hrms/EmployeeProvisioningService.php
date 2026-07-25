<?php

namespace App\Services\Hrms;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Dashboard\DashboardProvisioningService;
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
 * organization membership, role assignment, and optional dashboard/notifications.
 */
class EmployeeProvisioningService
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected EmployeeService $employeeService,
        protected AuditLogger $auditLogger,
        protected NotificationService $notificationService,
        protected DashboardProvisioningService $dashboardProvisioning,
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
     *     user?: array{name?: string, email?: string, password?: string|null, role?: string}|null,
     *     role?: string,
     *     notify?: bool,
     *     provision_dashboard?: bool,
     * }  $data
     */
    public function provision(array $data, User $actor, ?Organization $organization = null): Employee
    {
        $organization ??= $this->requireOrganization();

        return DB::transaction(function () use ($data, $actor, $organization): Employee {
            $createUser = (bool) ($data['create_user'] ?? ! empty($data['user']) || ! empty($data['user_id']));
            $roleSlug = (string) ($data['role'] ?? $data['user']['role'] ?? 'employee');
            $employeeData = Arr::except($data, [
                'create_user', 'user', 'user_id', 'role', 'notify', 'provision_dashboard', 'password',
                'user_email', 'entry_point',
            ]);

            $user = null;

            if ($createUser) {
                $user = $this->resolveOrCreateUser($organization, $data, $roleSlug);
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

            if (($data['provision_dashboard'] ?? false) === true) {
                $this->dashboardProvisioning->provision($organization);
            }

            if (($data['notify'] ?? true) === true && $employee->user_id) {
                $this->notifyProvisioned($organization, $employee);
            }

            $this->auditLogger->log($employee, 'employee_provisioned', [
                'user_id' => $employee->user_id,
                'role' => $roleSlug,
                'entry_point' => $data['entry_point'] ?? 'hrms',
            ], $actor);

            return $employee->fresh(['user', 'branch', 'department', 'designation', 'emergencyContacts']);
        });
    }

    /**
     * Ensure an existing employee has a linked user + membership + role.
     *
     * @param  array{name?: string, email: string, password?: string|null, role?: string, notify?: bool}  $data
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

            $roleSlug = (string) ($data['role'] ?? 'employee');
            $user = $this->resolveOrCreateUser($organization, [
                'user' => [
                    'name' => $data['name'] ?? $employee->full_name,
                    'email' => $data['email'],
                    'password' => $data['password'] ?? null,
                    'role' => $roleSlug,
                ],
            ], $roleSlug);

            $employee = $this->employeeService->linkUser($employee, $user, $actor);

            if (($data['notify'] ?? true) === true) {
                $this->notifyProvisioned($organization, $employee);
            }

            return $employee;
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
     */
    protected function resolveOrCreateUser(Organization $organization, array $data, string $roleSlug): User
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

            return $user;
        }

        $userPayload = $data['user'] ?? [];
        $email = strtolower(trim((string) ($userPayload['email'] ?? $data['email'] ?? '')));

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

            return $existingUser;
        }

        $password = $userPayload['password'] ?? null;
        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $password ? Hash::make($password) : Hash::make(Str::password(16)),
        ]);

        $organization->addMember($user, $roleSlug);

        return $user;
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
