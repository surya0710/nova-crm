<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\PlanningReportExportService;
use App\Services\PlanningReportService;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlanningReportController extends Controller
{
    public function __construct(
        protected PlanningReportService $reports,
        protected PlanningReportExportService $exports,
    ) {}

    public function index(Request $request, TenantContext $tenant): View
    {
        abort_unless(
            $request->user()?->hasAnyPermission(['resources.view', 'projects.view', 'reports.view']),
            403
        );

        $organization = $tenant->get();
        abort_unless($organization, 422);

        $reportType = $request->string('report_type')->toString() ?: 'resource_allocation';
        $filters = [
            'from' => $request->string('from')->toString() ?: now()->startOfMonth()->toDateString(),
            'to' => $request->string('to')->toString() ?: now()->endOfMonth()->toDateString(),
            'project_id' => $request->integer('project_id') ?: null,
            'department_id' => $request->integer('department_id') ?: null,
            'branch_id' => $request->integer('branch_id') ?: null,
        ];

        return view('projects.planning.reports.index', [
            'availableReports' => $this->reports->availableReports(),
            'exportFormats' => config('projects.planning_reports.export_formats', []),
            'reportType' => $reportType,
            'report' => $this->reports->compile($organization, $reportType, $filters),
            'filters' => $filters,
            'projects' => Project::query()->where('is_archived', false)->orderBy('name')->limit(200)->get(['id', 'name']),
        ]);
    }

    public function export(Request $request, TenantContext $tenant): StreamedResponse|BinaryFileResponse
    {
        abort_unless(
            $request->user()?->hasAnyPermission(['resources.view', 'projects.view', 'reports.view']),
            403
        );

        $organization = $tenant->get();
        abort_unless($organization, 422);

        $reportType = $request->string('report_type')->toString() ?: 'resource_allocation';
        $format = $request->string('format')->toString() ?: 'csv';
        $filters = [
            'from' => $request->string('from')->toString() ?: now()->startOfMonth()->toDateString(),
            'to' => $request->string('to')->toString() ?: now()->endOfMonth()->toDateString(),
            'project_id' => $request->integer('project_id') ?: null,
            'department_id' => $request->integer('department_id') ?: null,
            'branch_id' => $request->integer('branch_id') ?: null,
        ];

        return $this->exports->export($organization, $reportType, $format, $filters, $request->user());
    }
}
