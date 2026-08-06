<?php

namespace App\Services\Import\Adapters;

use App\Contracts\Import\ImportableEntityInterface;
use App\Models\Employee;
use App\Models\ImportSession;
use App\Models\Organization;
use App\Services\Hrms\EmployeeProvisioningService;
use App\Services\Hrms\EmployeeService;
use App\Services\Import\Adapters\Concerns\OrganizationImportSupport;
use App\Services\Import\ImportFieldDefinition;
use App\Services\TenantContext;
use Illuminate\Support\Facades\DB;

class EmployeeImportAdapter implements ImportableEntityInterface
{
    use OrganizationImportSupport;

    public function __construct(
        protected TenantContext $tenant,
        protected EmployeeService $employees,
        protected EmployeeProvisioningService $provisioning,
    ) {}

    public function entityType(): string
    {
        return 'employee';
    }

    public function entityLabel(): string
    {
        return 'Employee';
    }

    public function fieldDefinitions(): array
    {
        return [
            new ImportFieldDefinition(
                key: 'first_name',
                label: 'First Name',
                required: true,
                aliases: ['Firstname', 'Given Name'],
            ),
            new ImportFieldDefinition(
                key: 'last_name',
                label: 'Last Name',
                required: false,
                aliases: ['Lastname', 'Surname', 'Family Name'],
            ),
            new ImportFieldDefinition(
                key: 'email',
                label: 'Email',
                required: false,
                dataType: ImportFieldDefinition::TYPE_EMAIL,
                aliases: ['Work Email', 'Email Address'],
            ),
            new ImportFieldDefinition(
                key: 'mobile',
                label: 'Mobile',
                required: false,
                dataType: ImportFieldDefinition::TYPE_PHONE,
                aliases: ['Phone', 'Mobile Number', 'Phone Number'],
            ),
            new ImportFieldDefinition(
                key: 'employment_type',
                label: 'Employment Type',
                required: false,
                aliases: ['Type'],
            ),
            new ImportFieldDefinition(
                key: 'status',
                label: 'Status',
                required: false,
                aliases: ['Employment Status'],
            ),
            new ImportFieldDefinition(
                key: 'joining_date',
                label: 'Joining Date',
                required: false,
                dataType: ImportFieldDefinition::TYPE_DATE,
                aliases: ['Date of Joining', 'Start Date'],
            ),
            new ImportFieldDefinition(
                key: 'department_code',
                label: 'Department Code',
                required: false,
                aliases: ['Department'],
            ),
            new ImportFieldDefinition(
                key: 'designation_code',
                label: 'Designation Code',
                required: false,
                aliases: ['Designation', 'Title'],
            ),
            new ImportFieldDefinition(
                key: 'branch_code',
                label: 'Branch Code',
                required: false,
                aliases: ['Branch'],
            ),
            new ImportFieldDefinition(
                key: 'employee_code',
                label: 'Employee Code',
                required: false,
                aliases: ['Code', 'Emp Code'],
            ),
            new ImportFieldDefinition(
                key: 'create_login',
                label: 'Create Login',
                required: false,
                dataType: ImportFieldDefinition::TYPE_BOOLEAN,
                aliases: ['Create User', 'Provision Login'],
            ),
            new ImportFieldDefinition(
                key: 'send_invitation',
                label: 'Send Invitation',
                required: false,
                dataType: ImportFieldDefinition::TYPE_BOOLEAN,
            ),
            new ImportFieldDefinition(
                key: 'portal_access',
                label: 'Portal Access',
                required: false,
                dataType: ImportFieldDefinition::TYPE_BOOLEAN,
            ),
            new ImportFieldDefinition(
                key: 'role',
                label: 'Role',
                required: false,
                aliases: ['User Role'],
            ),
        ];
    }

    public function validateMappedRows(array $rows, ImportSession $session): array
    {
        $organization = $this->organizationFor($session);
        $errors = [];
        $seenKeys = [];

        foreach ($rows as $row) {
            if (! ($row['valid'] ?? true)) {
                continue;
            }

            $values = $row['values'];
            $rowNumber = (int) $row['row_number'];
            $employeeCode = $this->stringOrNull($values['employee_code'] ?? null);
            $email = $this->normalizeEmail($values['email'] ?? null);
            $duplicateKey = $this->duplicateKey($employeeCode, $email);

            if ($duplicateKey !== null) {
                if (isset($seenKeys[$duplicateKey])) {
                    $field = $employeeCode !== null ? 'employee_code' : 'email';
                    $errors[] = $this->error($rowNumber, $field, 'Duplicate employee within import file.', $values[$field] ?? null);
                } else {
                    $seenKeys[$duplicateKey] = $rowNumber;
                }
            }

            $departmentCode = $this->stringOrNull($values['department_code'] ?? null);
            if ($departmentCode !== null && $this->resolveDepartmentByCode($organization, $departmentCode) === null) {
                $errors[] = $this->error($rowNumber, 'department_code', 'Unknown department code.', $departmentCode);
            }

            $designationCode = $this->stringOrNull($values['designation_code'] ?? null);
            if ($designationCode !== null && $this->resolveDesignationByCode($organization, $designationCode) === null) {
                $errors[] = $this->error($rowNumber, 'designation_code', 'Unknown designation code.', $designationCode);
            }

            $branchCode = $this->stringOrNull($values['branch_code'] ?? null);
            if ($branchCode !== null && $this->resolveBranchByCode($organization, $branchCode) === null) {
                $errors[] = $this->error($rowNumber, 'branch_code', 'Unknown branch code.', $branchCode);
            }

            $employmentType = $this->stringOrNull($values['employment_type'] ?? null);
            if ($employmentType !== null && $this->resolveConfigKey(config('hrms.employment_types', []), $employmentType) === null) {
                $errors[] = $this->error($rowNumber, 'employment_type', 'Unknown employment type.', $employmentType);
            }

            $status = $this->stringOrNull($values['status'] ?? null);
            if ($status !== null && $this->resolveConfigKey(config('hrms.employment_statuses', []), $status) === null) {
                $errors[] = $this->error($rowNumber, 'status', 'Unknown employment status.', $status);
            }

            $role = $this->stringOrNull($values['role'] ?? null);
            if ($role !== null && ! array_key_exists(strtolower($role), array_change_key_case(config('rbac.roles', []), CASE_LOWER))) {
                $errors[] = $this->error($rowNumber, 'role', 'Unknown role.', $role);
            }

            $createLogin = $this->parseBoolean($values['create_login'] ?? null, false);
            if ($createLogin === true && $email === null) {
                $errors[] = $this->error($rowNumber, 'email', 'Email is required when create login is enabled.', null);
            }

            if ($this->shouldReportDatabaseDuplicates($session) && $duplicateKey !== null) {
                $existing = $this->findExisting($organization, $employeeCode, $email);
                if ($existing) {
                    $field = $employeeCode !== null ? 'employee_code' : 'email';
                    $errors[] = $this->error(
                        $rowNumber,
                        $field,
                        'Duplicate employee already exists in this organization.',
                        $values[$field] ?? null
                    );
                }
            }
        }

        return $errors;
    }

    public function persistRow(array $mappedRow, ImportSession $session): array
    {
        $organization = $this->organizationFor($session);
        $actor = $this->actorFor($session);
        $employeeCode = $this->stringOrNull($mappedRow['employee_code'] ?? null);
        $email = $this->normalizeEmail($mappedRow['email'] ?? null);
        $existing = $this->findExisting($organization, $employeeCode, $email);
        $strategy = $this->duplicateStrategy($session);

        if ($existing !== null) {
            if ($strategy === 'skip') {
                return ['action' => 'skipped', 'id' => $existing->id];
            }

            if ($strategy === 'update') {
                $employee = $this->employees->updateEmployee($existing, $this->buildPayload($organization, $mappedRow, forUpdate: true), $actor);

                return ['action' => 'updated', 'id' => $employee->id];
            }
        }

        $createLogin = $this->parseBoolean($mappedRow['create_login'] ?? null, false) === true;
        $payload = $this->buildPayload($organization, $mappedRow, forUpdate: false);

        if ($createLogin) {
            $employee = $this->provisioning->provisionFromImport([
                ...$payload,
                'create_user' => true,
                'send_invitation' => $this->parseBoolean($mappedRow['send_invitation'] ?? null, true),
                'portal_access' => $this->parseBoolean($mappedRow['portal_access'] ?? null, true),
                'role' => $this->stringOrNull($mappedRow['role'] ?? null),
            ], $actor, $organization);
        } else {
            $employee = $this->employees->createEmployee($payload, $actor);
        }

        if ($employeeCode !== null && $employee->employee_code !== $employeeCode) {
            $employee = DB::transaction(function () use ($employee, $employeeCode): Employee {
                $employee->forceFill(['employee_code' => $employeeCode])->save();

                return $employee->fresh();
            });
        }

        return ['action' => 'created', 'id' => $employee->id];
    }

    protected function duplicateKey(?string $employeeCode, ?string $email): ?string
    {
        if ($employeeCode !== null) {
            return 'code:'.strtolower($employeeCode);
        }

        if ($email !== null) {
            return 'email:'.$email;
        }

        return null;
    }

    protected function findExisting(Organization $organization, ?string $employeeCode, ?string $email): ?Employee
    {
        $query = Employee::query()->where('organization_id', $organization->id);

        if ($employeeCode !== null) {
            return (clone $query)->where('employee_code', $employeeCode)->first();
        }

        if ($email !== null) {
            return (clone $query)->where('email', $email)->first();
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $mappedRow
     * @return array<string, mixed>
     */
    protected function buildPayload(Organization $organization, array $mappedRow, bool $forUpdate): array
    {
        $departmentCode = $this->stringOrNull($mappedRow['department_code'] ?? null);
        $designationCode = $this->stringOrNull($mappedRow['designation_code'] ?? null);
        $branchCode = $this->stringOrNull($mappedRow['branch_code'] ?? null);
        $joiningDate = $this->parseDate($mappedRow['joining_date'] ?? null);

        $employmentType = $this->resolveConfigKey(
            config('hrms.employment_types', []),
            $this->stringOrNull($mappedRow['employment_type'] ?? null)
        ) ?? 'full_time';

        $status = $this->resolveConfigKey(
            config('hrms.employment_statuses', []),
            $this->stringOrNull($mappedRow['status'] ?? null)
        ) ?? 'active';

        $payload = array_filter([
            'organization_id' => $organization->id,
            'first_name' => $this->stringOrNull($mappedRow['first_name'] ?? null),
            'last_name' => $this->stringOrNull($mappedRow['last_name'] ?? null),
            'email' => $this->normalizeEmail($mappedRow['email'] ?? null),
            'mobile' => $this->stringOrNull($mappedRow['mobile'] ?? null),
            'employment_type' => $employmentType,
            'status' => $status,
            'joining_date' => $joiningDate?->toDateString(),
            'department_id' => $departmentCode !== null ? $this->resolveDepartmentByCode($organization, $departmentCode)?->id : null,
            'designation_id' => $designationCode !== null ? $this->resolveDesignationByCode($organization, $designationCode)?->id : null,
            'branch_id' => $branchCode !== null ? $this->resolveBranchByCode($organization, $branchCode)?->id : null,
        ], static fn (mixed $value): bool => $value !== null);

        if (! $forUpdate) {
            $payload['employment_type'] = $employmentType;
            $payload['status'] = $status;
        }

        return $payload;
    }
}
