<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectReportRequest;
use App\Http\Resources\ProjectReportResource;
use App\Models\Project;
use App\Services\ProjectReportingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class ProjectReportController extends Controller
{
    public function __construct(protected ProjectReportingService $reportingService) {}

    public function index(Project $project): AnonymousResourceCollection
    {
        $this->authorize('viewReports', $project);

        $reports = $project->reports()
            ->with(['generator', 'project'])
            ->paginate(request()->integer('per_page', 15));

        return ProjectReportResource::collection($reports);
    }

    public function store(StoreProjectReportRequest $request, Project $project): JsonResponse
    {
        try {
            $report = $this->reportingService->generate(
                $project,
                $project->organization,
                $request->validated('report_type'),
                $request->validated('format'),
                $request->validated('filters', []),
                $request->user(),
            );
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        return (new ProjectReportResource($report->load(['generator', 'project'])))
            ->response()
            ->setStatusCode(201);
    }
}
