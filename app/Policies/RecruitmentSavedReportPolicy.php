<?php

namespace App\Policies;

use App\Models\RecruitmentSavedReport;
use App\Models\User;

class RecruitmentSavedReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('recruitment.reports.view');
    }

    public function view(User $user, RecruitmentSavedReport $report): bool
    {
        if (! $user->hasPermission('recruitment.reports.view', $report->organization)) {
            return false;
        }

        return $report->user_id === $user->id
            || $report->is_shared
            || $user->hasPermission('recruitment.reports.manage', $report->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('recruitment.reports.manage');
    }

    public function update(User $user, RecruitmentSavedReport $report): bool
    {
        if (! $user->hasPermission('recruitment.reports.manage', $report->organization)) {
            return false;
        }

        return $report->user_id === $user->id
            || $user->hasPermission('recruitment.manage', $report->organization);
    }

    public function delete(User $user, RecruitmentSavedReport $report): bool
    {
        return $this->update($user, $report);
    }

    public function share(User $user, RecruitmentSavedReport $report): bool
    {
        return $this->update($user, $report);
    }

    public function export(User $user): bool
    {
        return $user->hasPermission('recruitment.reports.export');
    }
}
