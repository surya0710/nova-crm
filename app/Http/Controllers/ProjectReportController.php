<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectReportRequest;
use App\Models\Project;
use App\Models\ProjectReport;
use App\Services\ProjectReportingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectReportController extends Controller
{
    public function __construct(protected ProjectReportingService $reportingService) {}

    public function index(Project $project): View
    {
        $this->authorize('viewReports', $project);

        $reports = $project->reports()
            ->with('generator')
            ->paginate(15);

        return view('projects.reports.index', [
            'project' => $project,
            'reports' => $reports,
            'reportTypes' => config('projects.report_types', []),
            'reportFormats' => config('projects.report_formats', []),
        ]);
    }

    public function store(StoreProjectReportRequest $request, Project $project): RedirectResponse
    {
        $this->reportingService->generate(
            $project,
            $project->organization,
            $request->validated('report_type'),
            $request->validated('format'),
            $request->validated('filters', []),
            $request->user(),
        );

        return redirect()
            ->route('projects.reports.index', $project)
            ->with('status', 'project-report-generated');
    }

    public function download(Project $project, ProjectReport $report): StreamedResponse
    {
        $this->authorize('viewReports', $project);
        abort_unless((int) $report->project_id === (int) $project->id, 404);
        abort_unless($report->storage_path && Storage::exists($report->storage_path), 404);

        return Storage::download($report->storage_path);
    }
}
