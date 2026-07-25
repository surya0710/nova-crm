<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\PerformanceReview;
use App\Models\User;

class PerformanceReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('performance.review.view');
    }

    public function view(User $user, PerformanceReview $review): bool
    {
        if (! $user->hasPermission('performance.review.view', $review->organization)) {
            return false;
        }

        if ($user->hasPermission('performance.review.manage', $review->organization)) {
            return true;
        }

        return $this->isSubjectOrReviewer($user, $review);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('performance.review.manage');
    }

    public function update(User $user, PerformanceReview $review): bool
    {
        if ($user->hasPermission('performance.review.manage', $review->organization)) {
            return true;
        }

        if (! $user->hasPermission('performance.review.submit', $review->organization)) {
            return false;
        }

        return $this->isActingReviewer($user, $review);
    }

    public function submit(User $user, PerformanceReview $review): bool
    {
        return $this->update($user, $review);
    }

    public function markReviewed(User $user, PerformanceReview $review): bool
    {
        if ($user->hasPermission('performance.review.manage', $review->organization)) {
            return true;
        }

        if (! $user->hasPermission('performance.review.submit', $review->organization)) {
            return false;
        }

        // Primary reviewer can mark their manager review as reviewed after submit.
        return $review->review_type === 'manager' && $this->isActingReviewer($user, $review);
    }

    public function close(User $user, PerformanceReview $review): bool
    {
        return $user->hasPermission('performance.review.manage', $review->organization);
    }

    protected function isSubjectOrReviewer(User $user, PerformanceReview $review): bool
    {
        $employee = $this->employeeFor($user, $review->organization_id);
        if (! $employee) {
            return false;
        }

        if ((int) $review->employee_id === (int) $employee->id) {
            return true;
        }

        if ((int) $review->reviewer_id === (int) $employee->id) {
            return true;
        }

        $assignment = $review->assignment;

        return $assignment && (int) $assignment->primary_reviewer_id === (int) $employee->id;
    }

    protected function isActingReviewer(User $user, PerformanceReview $review): bool
    {
        $employee = $this->employeeFor($user, $review->organization_id);
        if (! $employee) {
            return false;
        }

        if ($review->review_type === 'self') {
            return (int) $review->employee_id === (int) $employee->id;
        }

        return (int) $review->reviewer_id === (int) $employee->id
            || (int) ($review->assignment?->primary_reviewer_id) === (int) $employee->id;
    }

    protected function employeeFor(User $user, int $organizationId): ?Employee
    {
        return Employee::query()
            ->where('organization_id', $organizationId)
            ->where('user_id', $user->id)
            ->first();
    }
}
