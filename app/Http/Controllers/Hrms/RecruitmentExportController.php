<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\ExportRecruitmentReportRequest;
use App\Services\Recruitment\RecruitmentExportService;
use App\Services\Recruitment\RecruitmentReportService;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RecruitmentExportController extends Controller
{
    public function __construct(
        protected RecruitmentExportService $exports,
        protected RecruitmentReportService $reports,
    ) {}

    public function index(): View
    {
        $this->authorize('export', \App\Models\RecruitmentSavedReport::class);

        return view('hrms.recruitment.exports.index', [
            'availableReports' => $this->reports->availableReports(),
            'formats' => config('hrms.recruitment.analytics.export_formats', []),
            'periods' => config('hrms.recruitment.analytics.periods', []),
        ]);
    }

    public function download(ExportRecruitmentReportRequest $request): StreamedResponse|BinaryFileResponse
    {
        return $this->exports->export(
            $request->string('report_type')->toString(),
            $request->string('format')->toString(),
            $request->filters(),
            $request->user(),
        );
    }
}
