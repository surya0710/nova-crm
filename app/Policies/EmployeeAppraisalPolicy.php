<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\EmployeeAppraisal;
use App\Models\User;

class EmployeeAppraisalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('performance.appraisal.view');
    }

    public function view(User $user, EmployeeAppraisal $appraisal): bool
    {
        if ($user->hasPermission('performance.appraisal.manage', $appraisal->organization)) {
            return true;
        }

        if ($user->hasPermission('performance.appraisal.view', $appraisal->organization)) {
            if ($appraisal->isClosed()) {
                return $this->isEmployeeOrManager($user, $appraisal);
            }

            return $this->isEmployeeOrManager($user, $appraisal)
                || $user->hasPermission('performance.calibration.manage', $appraisal->organization);
        }

        return false;
    }

    public function update(User $user, EmployeeAppraisal $appraisal): bool
    {
        if ($appraisal->isImmutable()) {
            return false;
        }

        if ($user->hasPermission('performance.appraisal.manage', $appraisal->organization)) {
            return true;
        }

        return $this->isManagerOf($user, $appraisal);
    }

    public function submit(User $user, EmployeeAppraisal $appraisal): bool
    {
        return $this->update($user, $appraisal);
    }

    public function close(User $user, EmployeeAppraisal $appraisal): bool
    {
        return $user->hasPermission('performance.appraisal.manage', $appraisal->organization);
    }

    protected function isEmployeeOrManager(User $user, EmployeeAppraisal $appraisal): bool
    {
        return $this->isEmployee($user, $appraisal) || $this->isManagerOf($user, $appraisal);
    }

    protected function isEmployee(User $user, EmployeeAppraisal $appraisal): bool
    {
        $employee = Employee::query()->where('user_id', $user->id)->first();

        return $employee && $employee->id === $appraisal->employee_id;
    }

    protected function isManagerOf(User $user, EmployeeAppraisal $appraisal): bool
    {
        $manager = Employee::query()->where('user_id', $user->id)->first();

        return $manager && $appraisal->manager_employee_id === $manager->id;
    }
}
