<?php

namespace App\Services\Hrms;

use App\Models\Employee;
use App\Models\EmployeeSalaryAssignment;
use App\Models\PayrollConfiguration;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\PayrollValidationError;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class PayrollEnterpriseDashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function widgets(): array
    {
        $pending = PayrollRun::query()->whereIn('status', ['draft', 'running', 'calculated', 'approved'])->count();
        $generated = PayrollRun::query()->whereIn('status', ['calculated', 'approved', 'published', 'paid'])->count();
        $paid = PayrollRun::query()->where('status', 'paid')->count();
        $published = PayrollRun::query()->where('status', 'published')->count();

        $config = PayrollConfiguration::query()->first();
        $creditDay = $config?->salary_credit_day;
        $upcomingSalaryDate = null;
        if ($creditDay) {
            $candidate = Carbon::now()->day($creditDay);
            if ($candidate->isPast()) {
                $candidate->addMonthNoOverflow();
            }
            $upcomingSalaryDate = $candidate->toDateString();
        }

        $eligibleStatuses = config('hrms.leave_applicable_employee_statuses', ['active', 'probation', 'notice_period']);
        $activeEmployees = Employee::query()->whereIn('status', $eligibleStatuses)->pluck('id');
        $withActiveAssignment = EmployeeSalaryAssignment::query()
            ->whereIn('employee_id', $activeEmployees)
            ->whereNull('effective_until')
            ->pluck('employee_id')
            ->unique();
        $missingStructure = $activeEmployees->diff($withActiveAssignment)->count();

        $openPeriods = PayrollPeriod::query()->whereIn('status', ['draft', 'open'])->count();
        $recentErrors = Schema::hasTable('payroll_validation_errors')
            ? PayrollValidationError::query()->where('created_at', '>=', now()->subDays(30))->count()
            : 0;

        $healthScore = 100;
        $healthScore -= min(40, $missingStructure * 5);
        $healthScore -= min(30, $recentErrors * 2);
        $healthScore -= $openPeriods > 2 ? 10 : 0;
        $healthScore = max(0, $healthScore);

        $health = match (true) {
            $healthScore >= 80 => 'healthy',
            $healthScore >= 50 => 'attention',
            default => 'critical',
        };

        return [
            'pending_payroll' => $pending,
            'generated_payroll' => $generated,
            'paid_payroll' => $paid,
            'published_awaiting_payment' => $published,
            'upcoming_salary_date' => $upcomingSalaryDate,
            'salary_credit_day' => $creditDay,
            'missing_salary_structure' => $missingStructure,
            'payroll_health' => [
                'status' => $health,
                'score' => $healthScore,
                'open_periods' => $openPeriods,
                'recent_validation_errors' => $recentErrors,
            ],
        ];
    }
}
