<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\TaxProjection;
use App\Services\Hrms\TaxFacadeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaxProjectionController extends Controller
{
    public function __construct(protected TaxFacadeService $taxFacade) {}

    public function index(): View
    {
        $this->authorize('viewAny', TaxProjection::class);

        $fy = $this->taxFacade->ensureFinancialYear(request()->user());

        return view('hrms.payroll.tax.projections.index', [
            'financialYear' => $fy,
            'projections' => TaxProjection::query()
                ->with(['employee', 'financialYear'])
                ->when($fy, fn ($q) => $q->where('tax_financial_year_id', $fy->id))
                ->latest('calculated_at')
                ->paginate(20),
            'employees' => Employee::query()->orderBy('first_name')->limit(500)->get(),
        ]);
    }

    public function calculate(Request $request): RedirectResponse
    {
        $this->authorize('calculate', TaxProjection::class);

        $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
        ]);

        $fy = $this->taxFacade->ensureFinancialYear($request->user());
        $employee = Employee::query()->findOrFail($request->integer('employee_id'));

        $this->taxFacade->projectEmployee($employee, $fy, null, null, $request->user());

        return redirect()->route('hrms.payroll.tax.projections.index')
            ->with('status', 'hrms-tax-projection-calculated');
    }
}
