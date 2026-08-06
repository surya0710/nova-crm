<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\PerformanceReviewAssignment;
use App\Models\User;

class PerformanceReviewAssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('performance.review.view');
    }

    public function view(User $user, PerformanceReviewAssignment $assignment): bool
    {
        if (! $user->hasPermission('performance.review.view', $assignment->organization)) {
            return false;
        }

        if ($user->hasPermission('performance.review.manage', $assignment->organization)) {
            return true;
        }

        return $this->isSubjectOrReviewer($user, $assignment);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('performance.review.manage');
    }

    public function update(User $user, PerformanceReviewAssignment $assignment): bool
    {
        return $user->hasPermission('performance.review.manage', $assignment->organization);
    }

    public function delete(User $user, PerformanceReviewAssignment $assignment): bool
    {
        return $user->hasPermission('performance.review.manage', $assignment->organization);
    }

    public function cancel(User $user, PerformanceReviewAssignment $assignment): bool
    {
        return $this->update($user, $assignment);
    }

    public function activate(User $user, PerformanceReviewAssignment $assignment): bool
    {
        return $this->update($user, $assignment);
    }

    protected function isSubjectOrReviewer(User $user, PerformanceReviewAssignment $assignment): bool
    {
        $employee = Employee::query()
            ->where('organization_id', $assignment->organization_id)
            ->where('user_id', $user->id)
            ->first();

        if (! $employee) {
            return false;
        }

        return (int) $assignment->employee_id === (int) $employee->id
            || (int) $assignment->primary_reviewer_id === (int) $employee->id;
    }
}
