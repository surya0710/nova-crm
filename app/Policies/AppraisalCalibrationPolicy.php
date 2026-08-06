<?php

namespace App\Policies;

use App\Models\AppraisalCalibration;
use App\Models\User;

class AppraisalCalibrationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('performance.calibration.manage')
            || $user->hasPermission('performance.appraisal.view');
    }

    public function view(User $user, AppraisalCalibration $calibration): bool
    {
        return $user->hasPermission('performance.calibration.manage', $calibration->organization)
            || $user->hasPermission('performance.appraisal.view', $calibration->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('performance.calibration.manage');
    }

    public function update(User $user, AppraisalCalibration $calibration): bool
    {
        return $user->hasPermission('performance.calibration.manage', $calibration->organization);
    }

    public function approve(User $user, AppraisalCalibration $calibration): bool
    {
        return $user->hasPermission('performance.calibration.manage', $calibration->organization);
    }
}
