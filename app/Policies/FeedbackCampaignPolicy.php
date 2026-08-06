<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\FeedbackCampaign;
use App\Models\User;

class FeedbackCampaignPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('performance.feedback.view');
    }

    public function view(User $user, FeedbackCampaign $campaign): bool
    {
        if (! $user->hasPermission('performance.feedback.view', $campaign->organization)) {
            return false;
        }

        if ($user->hasPermission('performance.feedback.manage', $campaign->organization)) {
            return true;
        }

        return $this->hasAssignedRequest($user, $campaign);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('performance.feedback.manage');
    }

    public function update(User $user, FeedbackCampaign $campaign): bool
    {
        return $user->hasPermission('performance.feedback.manage', $campaign->organization);
    }

    public function delete(User $user, FeedbackCampaign $campaign): bool
    {
        return $user->hasPermission('performance.feedback.manage', $campaign->organization);
    }

    public function activate(User $user, FeedbackCampaign $campaign): bool
    {
        return $this->update($user, $campaign);
    }

    public function close(User $user, FeedbackCampaign $campaign): bool
    {
        return $this->update($user, $campaign);
    }

    public function archive(User $user, FeedbackCampaign $campaign): bool
    {
        return $this->update($user, $campaign);
    }

    public function manageParticipants(User $user, FeedbackCampaign $campaign): bool
    {
        return $this->update($user, $campaign);
    }

    public function generateRequests(User $user, FeedbackCampaign $campaign): bool
    {
        return $this->update($user, $campaign);
    }

    protected function hasAssignedRequest(User $user, FeedbackCampaign $campaign): bool
    {
        $employee = Employee::query()
            ->where('organization_id', $campaign->organization_id)
            ->where('user_id', $user->id)
            ->first();

        if (! $employee) {
            return false;
        }

        return $campaign->requests()
            ->where('participant_employee_id', $employee->id)
            ->exists();
    }
}
