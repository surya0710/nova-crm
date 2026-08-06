<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Models\TaxFinancialYear;
use App\Services\Hrms\TaxFacadeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TaxReportController extends Controller
{
    public function __construct(protected TaxFacadeService $taxFacade) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', TaxFinancialYear::class);

        $type = $request->string('type')->toString() ?: 'tds_register';
        $financialYearId = $request->integer('tax_financial_year_id') ?: null;

        return view('hrms.payroll.tax.reports.index', [
            'reportType' => $type,
            'financialYearId' => $financialYearId,
            'reportTypes' => config('hrms.income_tax.report_types', []),
            'financialYears' => TaxFinancialYear::query()->orderByDesc('start_date')->get(),
            'report' => $this->taxFacade->report($type, $financialYearId),
        ]);
    }

    public function export(Request $request)
    {
        $this->authorize('viewAny', TaxFinancialYear::class);

        $type = $request->string('type')->toString() ?: 'tds_register';
        $format = $request->string('format')->toString() ?: 'csv';
        $financialYearId = $request->integer('tax_financial_year_id') ?: null;

        $export = $this->taxFacade->exportReport($type, $format, $financialYearId);

        return Storage::disk($export['disk'])->download($export['path'], $export['filename']);
    }
}
