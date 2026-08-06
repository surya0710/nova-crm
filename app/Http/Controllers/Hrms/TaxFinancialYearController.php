<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\StoreTaxFinancialYearRequest;
use App\Models\TaxFinancialYear;
use App\Services\Hrms\TaxFacadeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TaxFinancialYearController extends Controller
{
    public function __construct(protected TaxFacadeService $taxFacade) {}

    public function index(): View
    {
        $this->authorize('viewAny', TaxFinancialYear::class);

        return view('hrms.payroll.tax.financial-years.index', [
            'financialYears' => TaxFinancialYear::query()
                ->withCount('slabs')
                ->orderByDesc('start_date')
                ->paginate(20),
        ]);
    }

    public function store(StoreTaxFinancialYearRequest $request): RedirectResponse
    {
        $this->taxFacade->createFinancialYear($request->validated(), $request->user());

        return redirect()->route('hrms.payroll.tax.financial-years.index')
            ->with('status', 'hrms-tax-financial-year-created');
    }

    public function activate(TaxFinancialYear $financialYear): RedirectResponse
    {
        $this->authorize('activate', $financialYear);
        $this->taxFacade->activateFinancialYear($financialYear, request()->user());

        return redirect()->route('hrms.payroll.tax.financial-years.index')
            ->with('status', 'hrms-tax-financial-year-activated');
    }
}
