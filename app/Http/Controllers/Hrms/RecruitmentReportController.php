<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\RecruitmentReportFilterRequest;
use App\Services\Recruitment\RecruitmentReportService;
use Illuminate\View\View;

class RecruitmentReportController extends Controller
{
    public function __construct(protected RecruitmentReportService $reports) {}

    public function index(RecruitmentReportFilterRequest $request): View
    {
        $reportType = $request->string('report_type')->toString() ?: 'recruitment_summary';
        $filters = $request->filters();
        $filters['period'] = $filters['period'] ?? 'month';

        return view('hrms.recruitment.reports.index', [
            'availableReports' => $this->reports->availableReports(),
            'reportType' => $reportType,
            'report' => $this->reports->compile($reportType, $filters, $request->user()),
            'filters' => $filters,
            'periods' => config('hrms.recruitment.analytics.periods', []),
        ]);
    }
}
