<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\UpdatePayrollConfigurationRequest;
use App\Models\PayrollConfiguration;
use App\Services\Hrms\PayrollService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PayrollConfigurationController extends Controller
{
    public function __construct(protected PayrollService $service) {}

    public function edit(): View
    {
        $this->authorize('viewAny', PayrollConfiguration::class);

        return view('hrms.payroll.configuration.edit', [
            'configuration' => $this->service->getOrCreateConfiguration(),
            'frequencies' => config('hrms.payroll_frequencies', []),
            'overtimeOptions' => config('hrms.payroll_overtime_handling', []),
            'roundingPolicies' => config('hrms.payroll_rounding_policies', []),
            'salaryModes' => config('hrms.payroll.salary_modes', []),
        ]);
    }

    public function update(UpdatePayrollConfigurationRequest $request): RedirectResponse
    {
        $this->authorize('update', PayrollConfiguration::class);
        $this->service->updateConfiguration($request->validated(), $request->user());

        return redirect()->route('hrms.payroll.configuration.edit')
            ->with('status', 'hrms-payroll-configuration-updated');
    }
}
