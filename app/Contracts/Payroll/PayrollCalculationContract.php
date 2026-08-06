<?php

namespace App\Contracts\Payroll;

use App\Models\Employee;
use App\Models\EmployeeSalaryAssignment;
use App\Models\PayrollPeriod;
use Carbon\CarbonInterface;

/**
 * Payroll calculation contract for Phase 10.3.x.
 *
 * Phase 10.3.1: context resolution only.
 * Phase 10.3.2: PayrollCalculationService consumes this contract to calculate
 * earnings/deductions.
 * Phase 10.3.3: StatutoryComplianceService applies versioned statutory components.
 */
interface PayrollCalculationContract
{
    /**
     * Resolve the salary assignment effective for an employee on a given date.
     */
    public function getActiveSalaryAssignment(Employee $employee, CarbonInterface $asOf): ?EmployeeSalaryAssignment;

    /**
     * Gather read-only payroll inputs from Employee, Attendance, Leave, and HR Operations.
     * Does not calculate salary amounts.
     *
     * @return array<string, mixed>
     */
    public function resolveCalculationContext(Employee $employee, PayrollPeriod $period): array;
}
