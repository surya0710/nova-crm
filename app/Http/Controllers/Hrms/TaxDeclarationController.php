<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\RejectTaxDeclarationRequest;
use App\Http\Requests\Hrms\StoreTaxDeclarationRequest;
use App\Models\Employee;
use App\Models\TaxDeclaration;
use App\Models\TaxFinancialYear;
use App\Services\Hrms\TaxFacadeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaxDeclarationController extends Controller
{
    public function __construct(protected TaxFacadeService $taxFacade) {}

    public function index(): View
    {
        $this->authorize('viewAny', TaxDeclaration::class);

        $fy = $this->taxFacade->ensureFinancialYear(request()->user());

        return view('hrms.payroll.tax.declarations.index', [
            'financialYear' => $fy,
            'declarations' => TaxDeclaration::query()
                ->with(['employee', 'financialYear', 'items'])
                ->when($fy, fn ($q) => $q->where('tax_financial_year_id', $fy->id))
                ->latest()
                ->paginate(20),
            'employees' => Employee::query()->orderBy('first_name')->limit(500)->get(),
            'financialYears' => TaxFinancialYear::query()->orderByDesc('start_date')->get(),
            'categories' => config('hrms.income_tax.declaration_categories', []),
        ]);
    }

    public function store(StoreTaxDeclarationRequest $request): RedirectResponse
    {
        $employee = Employee::query()->findOrFail($request->integer('employee_id'));
        $fy = TaxFinancialYear::query()->findOrFail($request->integer('tax_financial_year_id'));

        $this->taxFacade->createDeclaration($employee, $fy, $request->validated('items'), $request->user());

        return redirect()->route('hrms.payroll.tax.declarations.index')
            ->with('status', 'hrms-tax-declaration-created');
    }

    public function submit(Request $request, TaxDeclaration $declaration): RedirectResponse
    {
        $this->authorize('submit', $declaration);
        $this->taxFacade->submitDeclaration($declaration, $request->user());

        return redirect()->route('hrms.payroll.tax.declarations.index')
            ->with('status', 'hrms-tax-declaration-submitted');
    }

    public function verify(Request $request, TaxDeclaration $declaration): RedirectResponse
    {
        $this->authorize('verify', $declaration);
        $this->taxFacade->verifyDeclaration($declaration, $request->user(), $request->input('comments'));

        return redirect()->route('hrms.payroll.tax.declarations.index')
            ->with('status', 'hrms-tax-declaration-verified');
    }

    public function reject(RejectTaxDeclarationRequest $request, TaxDeclaration $declaration): RedirectResponse
    {
        $this->taxFacade->rejectDeclaration($declaration, $request->validated('reason'), $request->user());

        return redirect()->route('hrms.payroll.tax.declarations.index')
            ->with('status', 'hrms-tax-declaration-rejected');
    }
}
