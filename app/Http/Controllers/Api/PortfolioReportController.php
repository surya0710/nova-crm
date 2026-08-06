<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GeneratePortfolioReportRequest;
use App\Http\Resources\PortfolioReportResource;
use App\Models\Portfolio;
use App\Models\PortfolioReport;
use App\Models\Program;
use App\Services\PortfolioReportingService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PortfolioReportController extends Controller
{
    public function __construct(protected PortfolioReportingService $reportingService) {}

    public function index(TenantContext $tenant): AnonymousResourceCollection
    {
        $this->authorize('viewAny', PortfolioReport::class);

        $reports = PortfolioReport::query()
            ->where('organization_id', $tenant->id())
            ->with(['generator', 'portfolio', 'program'])
            ->latest('generated_at')
            ->paginate(request()->integer('per_page', 20));

        return PortfolioReportResource::collection($reports);
    }

    public function store(GeneratePortfolioReportRequest $request, TenantContext $tenant): JsonResponse
    {
        $validated = $request->validated();

        $portfolio = ! empty($validated['portfolio_id'])
            ? Portfolio::query()->where('organization_id', $tenant->id())->findOrFail($validated['portfolio_id'])
            : null;

        $program = ! empty($validated['program_id'])
            ? Program::query()->where('organization_id', $tenant->id())->findOrFail($validated['program_id'])
            : null;

        try {
            $report = $this->reportingService->generate(
                $tenant->get(),
                $validated['report_type'],
                $validated['format'],
                $validated['filters'] ?? [],
                $request->user(),
                $portfolio,
                $program,
            );
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        return (new PortfolioReportResource($report->load(['generator', 'portfolio', 'program'])))
            ->response()
            ->setStatusCode(201);
    }

    public function download(PortfolioReport $report): StreamedResponse|JsonResponse
    {
        $this->authorize('download', $report);

        if (! $report->storage_path || ! Storage::exists($report->storage_path)) {
            return response()->json(['message' => __('Report file not found.')], 404);
        }

        return Storage::download($report->storage_path);
    }
}
