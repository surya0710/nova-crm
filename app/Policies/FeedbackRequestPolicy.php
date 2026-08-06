<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\FeedbackRequest;
use App\Models\User;

class FeedbackRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('performance.feedback.view');
    }

    public function view(User $user, FeedbackRequest $request): bool
    {
        if (! $user->hasPermission('performance.feedback.view', $request->organization)) {
            return false;
        }

        if ($user->hasPermission('performance.feedback.manage', $request->organization)) {
            return true;
        }

        return $this->isParticipant($user, $request);
    }

    public function submit(User $user, FeedbackRequest $request): bool
    {
        if (! $user->hasPermission('performance.feedback.submit', $request->organization)) {
            return false;
        }

        if ($user->hasPermission('performance.feedback.manage', $request->organization)) {
            return true;
        }

        return $this->isParticipant($user, $request);
    }

    protected function isParticipant(User $user, FeedbackRequest $request): bool
    {
        $employee = Employee::query()
            ->where('organization_id', $request->organization_id)
            ->where('user_id', $user->id)
            ->first();

        if (! $employee) {
            return false;
        }

        return (int) $request->participant_employee_id === (int) $employee->id;
    }
}
