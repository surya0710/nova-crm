<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\Goal;
use App\Models\User;

class GoalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('performance.goal.view');
    }

    public function view(User $user, Goal $goal): bool
    {
        if (! $user->hasPermission('performance.goal.view', $goal->organization)) {
            return false;
        }

        if ($user->hasPermission('performance.goal.manage', $goal->organization)) {
            return true;
        }

        return $this->isAssigneeOrManager($user, $goal);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('performance.goal.manage');
    }

    public function update(User $user, Goal $goal): bool
    {
        if ($user->hasPermission('performance.goal.manage', $goal->organization)) {
            return true;
        }

        if (! $user->hasPermission('performance.goal.update', $goal->organization)) {
            return false;
        }

        return $this->isAssigneeOrManager($user, $goal);
    }

    public function delete(User $user, Goal $goal): bool
    {
        return $user->hasPermission('performance.goal.manage', $goal->organization);
    }

    public function updateProgress(User $user, Goal $goal): bool
    {
        return $this->update($user, $goal);
    }

    public function checkin(User $user, Goal $goal): bool
    {
        return $this->update($user, $goal);
    }

    protected function isAssigneeOrManager(User $user, Goal $goal): bool
    {
        if ($goal->assignee_type === 'employee' && $goal->employee_id) {
            $employee = Employee::query()
                ->where('id', $goal->employee_id)
                ->where('organization_id', $goal->organization_id)
                ->first();

            if ($employee && (int) $employee->user_id === (int) $user->id) {
                return true;
            }

            if ($employee && (int) $employee->reporting_manager_id) {
                $manager = Employee::query()->find($employee->reporting_manager_id);
                if ($manager && (int) $manager->user_id === (int) $user->id) {
                    return true;
                }
            }
        }

        // Managers with update permission can act on team/department goals they can see.
        return $user->hasPermission('performance.goal.update', $goal->organization)
            && $user->hasPermission('performance.view', $goal->organization)
            && in_array($goal->assignee_type, ['team', 'department', 'organization'], true);
    }
}
