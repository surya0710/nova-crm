<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Models\TaxFinancialYear;
use App\Services\Hrms\TaxFacadeService;
use Illuminate\View\View;

class IncomeTaxDashboardController extends Controller
{
    public function __construct(protected TaxFacadeService $taxFacade) {}

    public function __invoke(): View
    {
        $this->authorize('viewAny', TaxFinancialYear::class);

        $this->taxFacade->ensureFinancialYear(request()->user());

        return view('hrms.payroll.tax.dashboard', [
            'dashboard' => $this->taxFacade->dashboard(),
        ]);
    }
}
