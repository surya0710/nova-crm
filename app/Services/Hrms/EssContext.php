<?php

namespace App\Services\Hrms;

use App\Exceptions\MissingEmployeeRecordException;
use App\Models\Employee;
use App\Models\User;
use App\Services\TenantContext;

/**
 * Resolves the authenticated user's Employee record within the current tenant.
 */
class EssContext
{
    public function __construct(
        protected TenantContext $tenantContext,
    ) {}

    public function employeeFor(?User $user = null): ?Employee
    {
        $user ??= auth()->user();
        $organizationId = $this->tenantContext->id();

        if (! $user || $organizationId === null) {
            return null;
        }

        return Employee::query()
            ->where('organization_id', $organizationId)
            ->where('user_id', $user->id)
            ->first();
    }

    /**
     * Require a linked employee, or surface a soft empty state (not 403).
     *
     * @throws MissingEmployeeRecordException
     */
    public function requireEmployee(?User $user = null, string $audience = 'employee'): Employee
    {
        $employee = $this->employeeFor($user);

        if ($employee === null) {
            throw new MissingEmployeeRecordException($audience);
        }

        return $employee;
    }

    public function managesEmployee(Employee $manager, Employee $employee): bool
    {
        return (int) $employee->reporting_manager_id === (int) $manager->id;
    }
}
