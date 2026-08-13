<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\EmployeeWfhAssignment;
use App\Models\User;
use App\Models\WfhRequest;

class WfhPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('wfh.view') || $user->hasPermission('ess.access');
    }

    public function view(User $user, WfhRequest|EmployeeWfhAssignment $model): bool
    {
        if ($model instanceof WfhRequest
            && $user->hasPermission('ess.access', $model->organization)
            && (int) $model->employee?->user_id === (int) $user->id) {
            return true;
        }

        return $user->hasPermission('wfh.view', $model->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('wfh.manage');
    }

    public function applyOwn(User $user, Employee $employee): bool
    {
        return $user->hasPermission('ess.access', $employee->organization)
            && (int) $employee->user_id === (int) $user->id;
    }

    public function update(User $user, WfhRequest|EmployeeWfhAssignment $model): bool
    {
        return $user->hasPermission('wfh.manage', $model->organization);
    }

    public function delete(User $user, WfhRequest|EmployeeWfhAssignment $model): bool
    {
        if ($user->hasPermission('wfh.manage', $model->organization)) {
            return true;
        }

        return $model instanceof WfhRequest && $this->withdrawOwn($user, $model);
    }

    public function withdrawOwn(User $user, WfhRequest $wfhRequest): bool
    {
        return $user->hasPermission('ess.access', $wfhRequest->organization)
            && (int) $wfhRequest->employee?->user_id === (int) $user->id
            && in_array($wfhRequest->status, ['draft', 'pending'], true);
    }

    public function manage(User $user, WfhRequest|EmployeeWfhAssignment|null $model = null): bool
    {
        $organization = $model?->organization;

        return $user->hasPermission('wfh.manage', $organization);
    }

    public function approve(User $user, WfhRequest $wfhRequest): bool
    {
        if (! $user->hasPermission('wfh.approve', $wfhRequest->organization)) {
            return false;
        }

        if ($user->hasPermission('wfh.manage', $wfhRequest->organization)) {
            return true;
        }

        $pendingStep = $wfhRequest->approvalSteps()
            ->where('status', 'pending')
            ->orderBy('step_order')
            ->first();

        if ($pendingStep === null) {
            return false;
        }

        return (int) $pendingStep->approver_user_id === (int) $user->id;
    }

    public function cancel(User $user, WfhRequest $wfhRequest): bool
    {
        if ($user->hasPermission('wfh.manage', $wfhRequest->organization)) {
            return true;
        }

        return $user->hasPermission('ess.access', $wfhRequest->organization)
            && (int) $wfhRequest->employee?->user_id === (int) $user->id
            && $wfhRequest->status === 'approved';
    }
}
