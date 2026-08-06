<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Models\EmployeeSalaryAssignment;
use App\Models\PayrollPeriod;
use App\Models\PayrollResult;
use App\Models\PayrollRun;
use App\Models\SalaryComponent;
use App\Models\SalaryStructure;
use App\Services\Hrms\PayrollEnterpriseDashboardService;
use Illuminate\View\View;

class PayrollDashboardController extends Controller
{
    public function __construct(protected PayrollEnterpriseDashboardService $enterpriseDashboard) {}

    public function __invoke(): View
    {
        $this->authorize('viewAny', SalaryComponent::class);

        return view('hrms.payroll.index', [
            'componentCount' => SalaryComponent::query()->count(),
            'structureCount' => SalaryStructure::query()->where('is_active', true)->count(),
            'assignmentCount' => EmployeeSalaryAssignment::query()->whereNull('effective_until')->count(),
            'openPeriods' => PayrollPeriod::query()->whereIn('status', ['draft', 'open'])->count(),
            'runCount' => PayrollRun::query()->count(),
            'resultCount' => PayrollResult::query()->count(),
            'latestRuns' => PayrollRun::query()->with('period')->latest()->limit(5)->get(),
            'enterprise' => $this->enterpriseDashboard->widgets(),
        ]);
    }
}
