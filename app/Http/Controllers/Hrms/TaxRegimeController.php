<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\SelectTaxRegimeRequest;
use App\Models\Employee;
use App\Models\EmployeeTaxRegime;
use App\Models\TaxFinancialYear;
use App\Services\Hrms\TaxFacadeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TaxRegimeController extends Controller
{
    public function __construct(protected TaxFacadeService $taxFacade) {}

    public function index(): View
    {
        $this->authorize('viewAny', EmployeeTaxRegime::class);

        $fy = $this->taxFacade->ensureFinancialYear(request()->user());

        $employees = Employee::query()->orderBy('first_name')->paginate(20);

        $activeRegimes = EmployeeTaxRegime::query()
            ->where('tax_financial_year_id', $fy->id)
            ->where('status', 'active')
            ->whereIn('employee_id', $employees->pluck('id'))
            ->get()
            ->keyBy('employee_id');

        return view('hrms.payroll.tax.regimes.index', [
            'financialYear' => $fy,
            'employees' => $employees,
            'activeRegimes' => $activeRegimes,
            'regimes' => config('hrms.income_tax.regimes', []),
        ]);
    }

    public function store(SelectTaxRegimeRequest $request): RedirectResponse
    {
        $fy = $this->taxFacade->ensureFinancialYear($request->user());
        $employee = Employee::query()->findOrFail($request->integer('employee_id'));

        $this->taxFacade->selectRegime($employee, $fy, $request->validated(), $request->user());

        return redirect()->route('hrms.payroll.tax.regimes.index')
            ->with('status', 'hrms-tax-regime-selected');
    }
}
