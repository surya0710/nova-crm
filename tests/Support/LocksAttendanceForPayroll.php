<?php

namespace Tests\Support;

use App\Models\Organization;
use App\Models\PayrollPeriod;
use App\Models\User;
use App\Services\Hrms\AttendanceLockService;
use App\Services\TenantContext;

trait LocksAttendanceForPayroll
{
    protected function lockAttendanceForPayrollPeriod(PayrollPeriod $period, User $actor): void
    {
        $tenant = app(TenantContext::class);
        $previous = $tenant->get();
        $organization = Organization::query()->find($period->organization_id);
        if ($organization !== null) {
            $tenant->set($organization);
        }

        try {
            $lockService = app(AttendanceLockService::class);
            $attendancePeriod = $lockService->createPeriodForPayroll($period, $actor);
            if ($attendancePeriod->isOpen() || $attendancePeriod->isFrozen()) {
                $lockService->lock($attendancePeriod, $actor);
            }
        } finally {
            $tenant->set($previous ?? $organization);
        }
    }
}
