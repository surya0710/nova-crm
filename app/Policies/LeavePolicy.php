<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\User;

class LeavePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('leave.view') || $user->hasPermission('ess.access');
    }

    public function view(User $user, LeaveApplication $leaveApplication): bool
    {
        if ($user->hasPermission('ess.access', $leaveApplication->organization)
            && (int) $leaveApplication->employee?->user_id === (int) $user->id) {
            return true;
        }

        return $user->hasPermission('leave.view', $leaveApplication->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('leave.manage');
    }

    public function applyOwn(User $user, Employee $employee): bool
    {
        return $user->hasPermission('ess.access', $employee->organization)
            && (int) $employee->user_id === (int) $user->id;
    }

    public function update(User $user, LeaveApplication $leaveApplication): bool
    {
        return $user->hasPermission('leave.manage', $leaveApplication->organization);
    }

    public function delete(User $user, LeaveApplication $leaveApplication): bool
    {
        if ($user->hasPermission('leave.manage', $leaveApplication->organization)) {
            return true;
        }

        return $this->withdrawOwn($user, $leaveApplication);
    }

    public function withdrawOwn(User $user, LeaveApplication $leaveApplication): bool
    {
        return $user->hasPermission('ess.access', $leaveApplication->organization)
            && (int) $leaveApplication->employee?->user_id === (int) $user->id
            && in_array($leaveApplication->status, ['draft', 'pending'], true);
    }

    public function manage(User $user, ?LeaveApplication $leaveApplication = null): bool
    {
        $organization = $leaveApplication?->organization;

        return $user->hasPermission('leave.manage', $organization);
    }

    public function approve(User $user, LeaveApplication $leaveApplication): bool
    {
        if (! $user->hasPermission('leave.approve', $leaveApplication->organization)) {
            return false;
        }

        if ($user->hasPermission('leave.manage', $leaveApplication->organization)) {
            return true;
        }

        $pendingStep = $leaveApplication->approvalSteps()
            ->where('status', 'pending')
            ->orderBy('step_order')
            ->first();

        if ($pendingStep === null) {
            return false;
        }

        if ((int) $pendingStep->approver_user_id === (int) $user->id) {
            return true;
        }

        return false;
    }

    public function cancel(User $user, LeaveApplication $leaveApplication): bool
    {
        return $user->hasPermission('leave.manage', $leaveApplication->organization);
    }

    public function viewBalances(User $user): bool
    {
        return $user->hasPermission('leave.view') || $user->hasPermission('ess.access');
    }

    public function adjustBalance(User $user, ?LeaveBalance $leaveBalance = null): bool
    {
        $organization = $leaveBalance?->organization;

        return $user->hasPermission('leave.manage', $organization);
    }
}
