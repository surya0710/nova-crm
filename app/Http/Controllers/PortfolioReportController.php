<?php

namespace App\Http\Controllers;

use App\Http\Requests\GeneratePortfolioReportRequest;
use App\Models\Portfolio;
use App\Models\PortfolioReport;
use App\Models\Program;
use App\Services\PortfolioReportingService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PortfolioReportController extends Controller
{
    public function __construct(protected PortfolioReportingService $reportingService) {}

    public function index(TenantContext $tenant): View
    {
        $this->authorize('viewAny', PortfolioReport::class);

        $reports = PortfolioReport::query()
            ->where('organization_id', $tenant->id())
            ->with(['generator', 'portfolio', 'program'])
            ->latest('generated_at')
            ->paginate(20);

        return view('portfolio-reports.index', [
            'reports' => $reports,
            'organization' => $tenant->get(),
            'reportTypes' => config('projects.portfolio_report_types', PortfolioReportingService::REPORT_TYPES),
            'reportFormats' => config('projects.report_formats', []),
        ]);
    }

    public function store(GeneratePortfolioReportRequest $request, TenantContext $tenant): RedirectResponse
    {
        $validated = $request->validated();

        $portfolio = ! empty($validated['portfolio_id'])
            ? Portfolio::query()->where('organization_id', $tenant->id())->findOrFail($validated['portfolio_id'])
            : null;

        $program = ! empty($validated['program_id'])
            ? Program::query()->where('organization_id', $tenant->id())->findOrFail($validated['program_id'])
            : null;

        try {
            $this->reportingService->generate(
                $tenant->get(),
                $validated['report_type'],
                $validated['format'],
                $validated['filters'] ?? [],
                $request->user(),
                $portfolio,
                $program,
            );
        } catch (ValidationException $e) {
            return redirect()->back()->withInput()->withErrors($e->errors());
        }

        return redirect()
            ->route('portfolio-reports.index')
            ->with('status', 'portfolio-report-generated');
    }

    public function download(PortfolioReport $report): StreamedResponse
    {
        $this->authorize('download', $report);
        abort_unless($report->storage_path && Storage::exists($report->storage_path), 404);

        return Storage::download($report->storage_path);
    }
}
