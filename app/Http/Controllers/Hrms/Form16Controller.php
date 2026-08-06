<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\GenerateForm16Request;
use App\Models\Employee;
use App\Models\Form16Record;
use App\Models\TaxFinancialYear;
use App\Services\Hrms\TaxFacadeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class Form16Controller extends Controller
{
    public function __construct(protected TaxFacadeService $taxFacade) {}

    public function index(): View
    {
        $this->authorize('viewAny', Form16Record::class);

        $fy = $this->taxFacade->ensureFinancialYear(request()->user());

        return view('hrms.payroll.tax.form16.index', [
            'financialYear' => $fy,
            'records' => Form16Record::query()
                ->with(['employee', 'financialYear', 'generatedBy'])
                ->when($fy, fn ($q) => $q->where('tax_financial_year_id', $fy->id))
                ->latest('generated_at')
                ->paginate(20),
            'employees' => Employee::query()->orderBy('first_name')->limit(500)->get(),
            'financialYears' => TaxFinancialYear::query()->orderByDesc('start_date')->get(),
        ]);
    }

    public function store(GenerateForm16Request $request): RedirectResponse
    {
        $employee = Employee::query()->findOrFail($request->integer('employee_id'));
        $fy = TaxFinancialYear::query()->findOrFail($request->integer('tax_financial_year_id'));

        $this->taxFacade->generateForm16($employee, $fy, $request->user());

        return redirect()->route('hrms.payroll.tax.form16.index')
            ->with('status', 'hrms-form16-generated');
    }
}
