<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\StoreRecruitmentSavedReportRequest;
use App\Http\Requests\Recruitment\UpdateRecruitmentSavedReportRequest;
use App\Models\RecruitmentSavedReport;
use App\Services\Recruitment\RecruitmentReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecruitmentSavedReportController extends Controller
{
    public function __construct(protected RecruitmentReportService $reports)
    {
        $this->authorizeResource(RecruitmentSavedReport::class, 'recruitment_saved_report');
    }

    public function index(Request $request): View
    {
        return view('hrms.recruitment.saved-reports.index', [
            'reports' => $this->reports->listSavedReports($request->user()),
            'reportTypes' => config('hrms.recruitment.report_types', []),
            'periods' => config('hrms.recruitment.analytics.periods', []),
        ]);
    }

    public function store(StoreRecruitmentSavedReportRequest $request): RedirectResponse
    {
        $this->reports->saveReport($request->payload(), $request->user());

        return redirect()
            ->route('hrms.recruitment.saved-reports.index')
            ->with('status', __('Saved report created.'));
    }

    public function show(RecruitmentSavedReport $recruitmentSavedReport): RedirectResponse
    {
        $filters = $recruitmentSavedReport->filters_json ?? [];

        return redirect()->route('hrms.recruitment.reports.index', array_merge($filters, [
            'report_type' => $recruitmentSavedReport->report_type,
        ]));
    }

    public function update(
        UpdateRecruitmentSavedReportRequest $request,
        RecruitmentSavedReport $recruitmentSavedReport,
    ): RedirectResponse {
        $this->reports->updateReport($recruitmentSavedReport, $request->payload(), $request->user());

        return redirect()
            ->route('hrms.recruitment.saved-reports.index')
            ->with('status', __('Saved report updated.'));
    }

    public function destroy(Request $request, RecruitmentSavedReport $recruitmentSavedReport): RedirectResponse
    {
        $this->reports->deleteReport($recruitmentSavedReport, $request->user());

        return redirect()
            ->route('hrms.recruitment.saved-reports.index')
            ->with('status', __('Saved report deleted.'));
    }

    public function share(Request $request, RecruitmentSavedReport $recruitmentSavedReport): RedirectResponse
    {
        $this->authorize('share', $recruitmentSavedReport);

        $shared = ! $recruitmentSavedReport->is_shared;
        $this->reports->shareReport($recruitmentSavedReport, $shared, $request->user());

        return redirect()
            ->route('hrms.recruitment.saved-reports.index')
            ->with('status', $shared ? __('Report shared with organization.') : __('Report sharing disabled.'));
    }
}
