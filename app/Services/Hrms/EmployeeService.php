<?php

namespace App\Services\Hrms;

use App\Events\EmployeeCreated;
use App\Events\EmployeeDepartmentChanged;
use App\Events\EmployeeExited;
use App\Events\EmployeeManagerChanged;
use App\Events\EmployeeProfileUpdated;
use App\Events\EmployeeUpdated;
use App\Models\Employee;
use App\Models\EmployeeBankAccount;
use App\Models\EmployeeEmergencyContact;
use App\Models\EmployeeIdentity;
use App\Models\Organization;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TenantContext;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeService
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected AuditLogger $auditLogger,
        protected EmployeeSkillService $skillService,
        protected EmployeeCertificationService $certificationService,
        protected EmployeeEducationService $educationService,
        protected EmployeeExperienceService $experienceService,
        protected WfhPolicyService $wfhPolicyService,
    ) {}

    public function createEmployee(array $data, User $actor): Employee
    {
        $organization = $this->requireOrganization();

        return DB::transaction(function () use ($data, $actor, $organization): Employee {
            $employee = Employee::query()->create([
                ...Arr::except($data, [
                    'emergency_contacts',
                    'bank_accounts',
                    'identities',
                    'educations',
                    'experiences',
                    'skills',
                    'certifications',
                ]),
                'employee_code' => $this->generateEmployeeCode($organization),
            ]);

            $this->validateManagerHierarchy($employee, $data['reporting_manager_id'] ?? null);
            $this->syncProfile($employee, $data, $actor);
            $employee->refresh();

            $this->auditLogger->log($employee, 'employee_created', ['employee_code' => $employee->employee_code], $actor);
            event(EmployeeCreated::forModel($employee, ['actor_id' => $actor->id]));

            return $employee;
        });
    }

    public function updateEmployee(Employee $employee, array $data, User $actor): Employee
    {
        return DB::transaction(function () use ($employee, $data, $actor): Employee {
            $before = $employee->only([
                'status',
                'reporting_manager_id',
                'department_id',
                'branch_id',
                'designation_id',
                'organization_id',
            ]);
            $employee->update(Arr::except($data, [
                'employee_code',
                'emergency_contacts',
                'bank_accounts',
                'identities',
                'educations',
                'experiences',
                'skills',
                'certifications',
            ]));
            $this->validateManagerHierarchy($employee, $employee->reporting_manager_id);
            $this->syncProfile($employee, $data, $actor);
            $employee->refresh();

            $this->auditLogger->log($employee, 'employee_updated', [
                'before' => $before,
                'after' => $employee->only(array_keys($before)),
            ], $actor);
            if ($before['status'] !== $employee->status) {
                $this->auditLogger->log($employee, 'employee_status_changed', ['from' => $before['status'], 'to' => $employee->status], $actor);
            }
            if ($before['reporting_manager_id'] !== $employee->reporting_manager_id) {
                $this->auditLogger->log($employee, 'employee_reporting_manager_changed', ['from' => $before['reporting_manager_id'], 'to' => $employee->reporting_manager_id], $actor);
                event(EmployeeManagerChanged::forModel($employee, ['actor_id' => $actor->id]));
            }
            if ($before['department_id'] !== $employee->department_id) {
                $this->auditLogger->log($employee, 'employee_department_changed', ['from' => $before['department_id'], 'to' => $employee->department_id], $actor);
                event(EmployeeDepartmentChanged::forModel($employee, ['actor_id' => $actor->id]));
            }
            if ($before['branch_id'] !== $employee->branch_id) {
                $this->auditLogger->log($employee, 'employee_branch_changed', [
                    'from' => $before['branch_id'],
                    'to' => $employee->branch_id,
                    'wfh_policy' => 'unchanged',
                ], $actor);
            }
            if ($before['designation_id'] !== $employee->designation_id) {
                $this->auditLogger->log($employee, 'employee_designation_changed', ['from' => $before['designation_id'], 'to' => $employee->designation_id], $actor);
            }
            if ((int) $before['organization_id'] !== (int) $employee->organization_id) {
                $transfer = $this->wfhPolicyService->handleEmployeeOrganizationTransfer(
                    $employee,
                    (int) $before['organization_id'],
                    $actor,
                );
                $this->auditLogger->log($employee, 'employee_organization_changed', [
                    'from' => $before['organization_id'],
                    'to' => $employee->organization_id,
                    'wfh' => $transfer,
                ], $actor);
            }

            event(EmployeeUpdated::forModel($employee, ['actor_id' => $actor->id]));

            return $employee;
        });
    }

    public function updateOwnProfile(Employee $employee, array $data, User $actor): Employee
    {
        return DB::transaction(function () use ($employee, $data, $actor): Employee {
            $allowed = config('hrms.ess.self_editable_fields', []);
            $profileData = Arr::only($data, $allowed);
            $before = $employee->only($allowed);

            if ($profileData !== []) {
                $employee->update($profileData);
            }

            $sections = config('hrms.ess.self_editable_profile_sections', ['emergency_contacts']);
            $syncPayload = [];
            foreach ($sections as $section) {
                if (array_key_exists($section, $data)) {
                    $syncPayload[$section] = $data[$section];
                }
            }
            if ($syncPayload !== []) {
                $this->syncProfile($employee, $syncPayload, $actor);
            }

            $employee->refresh();

            $this->auditLogger->log($employee, 'employee_profile_updated', [
                'before' => $before,
                'after' => $employee->only($allowed),
            ], $actor);
            event(EmployeeProfileUpdated::forModel($employee, ['actor_id' => $actor->id]));

            return $employee->load([
                'emergencyContacts',
                'skills',
                'certifications',
                'educations',
                'experiences',
                'department',
                'designation',
                'reportingManager',
            ]);
        });
    }

    public function exitEmployee(Employee $employee, array $data, User $actor): Employee
    {
        return DB::transaction(function () use ($employee, $data, $actor): Employee {
            $employee->update([
                'status' => $data['status'],
                'exit_date' => $data['exit_date'],
            ]);

            $this->auditLogger->log($employee, 'employee_exited', [
                'status' => $employee->status,
                'exit_date' => $employee->exit_date?->toDateString(),
            ], $actor);
            event(EmployeeExited::forModel($employee, ['actor_id' => $actor->id]));

            return $employee;
        });
    }

    public function linkUser(Employee $employee, User $user, User $actor): Employee
    {
        return DB::transaction(function () use ($employee, $user, $actor): Employee {
            $organization = $this->requireOrganization();
            abort_unless($user->belongsToOrganization($organization), 422);

            $existing = Employee::query()
                ->where('user_id', $user->id)
                ->where('id', '!=', $employee->id)
                ->exists();
            if ($existing) {
                throw ValidationException::withMessages(['user_id' => 'This user is already linked to another employee.']);
            }

            $employee->update(['user_id' => $user->id]);
            $this->auditLogger->log($employee, 'employee_user_linked', ['user_id' => $user->id], $actor);

            return $employee;
        });
    }

    public function createAndLinkUser(Employee $employee, array $data, User $actor): Employee
    {
        return app(EmployeeProvisioningService::class)->provisionUserForEmployee($employee, [
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'] ?? config('identity.default_employee_role', 'employee'),
            'notify' => $data['notify'] ?? true,
            'send_invitation' => $data['send_invitation'] ?? true,
            'portal_access' => $data['portal_access'] ?? true,
        ], $actor);
    }

    public function unlinkUser(Employee $employee, User $actor): Employee
    {
        return DB::transaction(function () use ($employee, $actor): Employee {
            $previous = $employee->user_id;
            $employee->update(['user_id' => null]);
            $this->auditLogger->log($employee, 'employee_user_unlinked', ['user_id' => $previous], $actor);

            return $employee;
        });
    }

    public function generateEmployeeCode(Organization $organization): string
    {
        $prefix = (string) config('hrms.employee_code.prefix', 'EMP');
        $padding = max(1, (int) config('hrms.employee_code.padding', 5));

        $rows = DB::table('employees')
            ->where('organization_id', $organization->id)
            ->select('employee_code')
            ->lockForUpdate()
            ->get();

        $max = 0;
        foreach ($rows as $row) {
            if (preg_match('/(\d+)$/', (string) $row->employee_code, $match) === 1) {
                $max = max($max, (int) $match[1]);
            }
        }

        return sprintf('%s-%0'.$padding.'d', $prefix, $max + 1);
    }

    public function syncProfile(Employee $employee, array $data, User $actor): void
    {
        if (array_key_exists('emergency_contacts', $data)) {
            EmployeeEmergencyContact::query()->where('employee_id', $employee->id)->delete();
            $hasPrimary = false;
            foreach ($data['emergency_contacts'] ?? [] as $contact) {
                if (blank($contact['name'] ?? null) || blank($contact['phone'] ?? ($contact['mobile'] ?? null))) {
                    continue;
                }
                $isPrimary = (bool) ($contact['is_primary'] ?? false);
                if ($isPrimary) {
                    $hasPrimary = true;
                }
                $employee->emergencyContacts()->create([
                    'name' => $contact['name'],
                    'relationship' => $contact['relationship'] ?? null,
                    'phone' => $contact['phone'] ?? $contact['mobile'] ?? null,
                    'alternate_mobile' => $contact['alternate_mobile'] ?? null,
                    'email' => $contact['email'] ?? null,
                    'address' => $contact['address'] ?? null,
                    'is_primary' => $isPrimary,
                ]);
            }
            if (! $hasPrimary) {
                $first = $employee->emergencyContacts()->first();
                if ($first) {
                    $first->update(['is_primary' => true]);
                }
            }
        }

        if (array_key_exists('bank_accounts', $data)) {
            EmployeeBankAccount::query()->where('employee_id', $employee->id)->delete();
            foreach ($data['bank_accounts'] ?? [] as $bank) {
                $employee->bankAccounts()->create($bank);
            }
        }

        if (array_key_exists('identities', $data)) {
            EmployeeIdentity::query()->where('employee_id', $employee->id)->delete();
            foreach ($data['identities'] ?? [] as $identity) {
                $employee->identities()->create($identity);
            }
        }

        if (array_key_exists('educations', $data)) {
            $this->educationService->syncForEmployee($employee, $data['educations'] ?? [], $actor);
        }

        if (array_key_exists('experiences', $data)) {
            $this->experienceService->syncForEmployee($employee, $data['experiences'] ?? [], $actor);
        }

        if (array_key_exists('skills', $data)) {
            $this->skillService->syncForEmployee($employee, $data['skills'] ?? [], $actor);
        }

        if (array_key_exists('certifications', $data)) {
            $this->certificationService->syncForEmployee($employee, $data['certifications'] ?? [], $actor);
        }
    }

    public function validateManagerHierarchy(Employee $employee, ?int $managerId): void
    {
        if ($managerId === null) {
            return;
        }

        if ($managerId === $employee->id) {
            throw ValidationException::withMessages(['reporting_manager_id' => 'An employee cannot report to self.']);
        }

        $cursor = Employee::query()->find($managerId);
        while ($cursor !== null) {
            if ($cursor->id === $employee->id) {
                throw ValidationException::withMessages(['reporting_manager_id' => 'Circular reporting hierarchy is not allowed.']);
            }
            $cursor = $cursor->reporting_manager_id ? Employee::query()->find($cursor->reporting_manager_id) : null;
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
