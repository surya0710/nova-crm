<?php

namespace App\Services\CommandPalette;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class HrmsCommandProvider implements CommandProviderInterface
{
    public function commands(User $user, ?Organization $organization): Collection
    {
        if (! $organization) {
            return collect();
        }

        $commands = collect();
        $group = __('HR');

        if (Route::has('hrms.home') && $user->hasAnyPermission([
            'hrms.view', 'ess.access', 'hr.dashboard', 'manager.dashboard',
            'employee.directory', 'attendance.view', 'leave.view',
            'recruitment.view', 'payroll.view', 'performance.view',
        ])) {
            $commands->push([
                'id' => 'hrms.home',
                'label' => __('Open HR Home'),
                'group' => $group,
                'href' => route('hrms.home'),
                'keywords' => ['hr', 'hrms', 'home', 'people'],
            ]);
        }

        if ($user->hasPermission('hrms.view') && Route::has('hrms.employees.create')) {
            $commands->push([
                'id' => 'hrms.create-employee',
                'label' => __('Create Employee'),
                'group' => $group,
                'href' => route('hrms.employees.create'),
                'keywords' => ['employee', 'new', 'create', 'hire'],
            ]);
        }

        if ($user->hasPermission('recruitment.view') && Route::has('hrms.recruitment.openings.create')) {
            $commands->push([
                'id' => 'hrms.create-job-opening',
                'label' => __('Create Job Opening'),
                'group' => $group,
                'href' => route('hrms.recruitment.openings.create'),
                'keywords' => ['job', 'opening', 'recruitment', 'post'],
            ]);
        }

        if ($user->hasPermission('ess.access') && Route::has('ess.leave.index')) {
            $commands->push([
                'id' => 'hrms.apply-leave',
                'label' => __('Apply Leave'),
                'group' => $group,
                'href' => route('ess.leave.index'),
                'keywords' => ['leave', 'apply', 'time off'],
            ]);
        } elseif ($user->hasPermission('leave.view') && Route::has('hrms.leave-applications.create')) {
            $commands->push([
                'id' => 'hrms.apply-leave',
                'label' => __('Apply Leave'),
                'group' => $group,
                'href' => route('hrms.leave-applications.create'),
                'keywords' => ['leave', 'apply', 'time off'],
            ]);
        }

        if ($user->hasPermission('ess.access') && Route::has('ess.attendance.index')) {
            $commands->push([
                'id' => 'hrms.mark-attendance',
                'label' => __('Mark Attendance'),
                'group' => $group,
                'href' => route('ess.attendance.index'),
                'keywords' => ['attendance', 'clock', 'check in'],
            ]);
        } elseif ($user->hasPermission('attendance.view') && Route::has('hrms.attendance.index')) {
            $commands->push([
                'id' => 'hrms.mark-attendance',
                'label' => __('Mark Attendance'),
                'group' => $group,
                'href' => route('hrms.attendance.index'),
                'keywords' => ['attendance', 'clock', 'check in'],
            ]);
        }

        if ($user->hasPermission('recruitment.view') && Route::has('hrms.recruitment.dashboard')) {
            $commands->push([
                'id' => 'hrms.open-recruitment',
                'label' => __('Open Recruitment'),
                'group' => $group,
                'href' => route('hrms.recruitment.dashboard'),
                'keywords' => ['recruitment', 'hiring', 'candidates'],
            ]);
        }

        if ($user->hasPermission('hrms.view') && Route::has('hrms.employees.index')) {
            $commands->push([
                'id' => 'hrms.search-employees',
                'label' => __('Search Employees'),
                'group' => $group,
                'href' => route('hrms.employees.index'),
                'keywords' => ['employee', 'search', 'directory', 'people'],
            ]);
        }

        if ($user->hasPermission('recruitment.view') && Route::has('hrms.recruitment.candidates.index')) {
            $commands->push([
                'id' => 'hrms.search-candidates',
                'label' => __('Search Candidates'),
                'group' => $group,
                'href' => route('hrms.recruitment.candidates.index'),
                'keywords' => ['candidate', 'search', 'talent'],
            ]);
        }

        if ($user->hasPermission('leave.view') && Route::has('hrms.leave.dashboard')) {
            $commands->push([
                'id' => 'hrms.open-leave',
                'label' => __('Open Leave Dashboard'),
                'group' => $group,
                'href' => route('hrms.leave.dashboard'),
                'keywords' => ['leave', 'approval', 'dashboard'],
            ]);
        }

        if ($user->hasPermission('payroll.view') && Route::has('hrms.payroll.index')) {
            $commands->push([
                'id' => 'hrms.open-payroll',
                'label' => __('Open Payroll'),
                'group' => $group,
                'href' => route('hrms.payroll.index'),
                'keywords' => ['payroll', 'salary', 'payslip'],
            ]);
        }

        if ($user->hasAnyPermission(['recruitment.view', 'payroll.view', 'reports.view']) && Route::has('hrms.recruitment.reports.index')) {
            $commands->push([
                'id' => 'hrms.open-reports',
                'label' => __('Open Reports'),
                'group' => $group,
                'href' => route('hrms.recruitment.reports.index'),
                'keywords' => ['reports', 'hr reports', 'analytics'],
            ]);
        }

        if ($user->hasPermission('ess.access') && Route::has('ess.dashboard')) {
            $commands->push([
                'id' => 'hrms.my-hr',
                'label' => __('Open My HR'),
                'group' => $group,
                'href' => route('ess.dashboard'),
                'keywords' => ['ess', 'my hr', 'self service'],
            ]);
        }

        return $commands;
    }
}
