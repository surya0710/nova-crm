<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttachProjectToProgramRequest;
use App\Http\Requests\StoreProgramRequest;
use App\Http\Requests\UpdateProgramRequest;
use App\Http\Resources\ProgramResource;
use App\Models\Program;
use App\Models\Project;
use App\Services\ProgramService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class ProgramController extends Controller
{
    public function __construct(protected ProgramService $programService) {}

    public function index(Request $request, TenantContext $tenant): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Program::class);

        $programs = $this->programService->list($tenant->id(), [
            'search' => $request->string('search')->trim()->toString() ?: null,
            'status' => $request->string('status')->toString() ?: null,
            'portfolio_id' => $request->integer('portfolio_id') ?: null,
            'manager_id' => $request->integer('manager_id') ?: null,
        ]);

        return ProgramResource::collection($programs);
    }

    public function store(StoreProgramRequest $request, TenantContext $tenant): JsonResponse
    {
        $program = $this->programService->create([
            ...$request->validated(),
            'organization_id' => $tenant->id(),
        ], $request->user());

        return (new ProgramResource($program->load(['manager', 'portfolio', 'projects'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Program $program): ProgramResource
    {
        $this->authorize('view', $program);

        $program->load(['manager', 'portfolio', 'projects.status']);

        return new ProgramResource($program);
    }

    public function update(UpdateProgramRequest $request, Program $program): ProgramResource|JsonResponse
    {
        try {
            $program = $this->programService->update($program, $request->validated(), $request->user());
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        return new ProgramResource($program->load(['manager', 'portfolio', 'projects']));
    }

    public function destroy(Request $request, Program $program): JsonResponse
    {
        $this->authorize('delete', $program);

        try {
            $this->programService->delete($program, $request->user());
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json(['success' => true]);
    }

    public function dashboard(Program $program): JsonResponse
    {
        $this->authorize('viewDashboard', $program);

        $program->load(['manager', 'portfolio', 'projects.status']);

        return response()->json([
            'data' => [
                'program' => new ProgramResource($program),
            ],
        ]);
    }

    public function attachProject(AttachProjectToProgramRequest $request, Program $program): ProgramResource|JsonResponse
    {
        $project = Project::query()->findOrFail($request->validated('project_id'));

        try {
            $program = $this->programService->attachProject($program, $project, $request->user());
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        return new ProgramResource($program);
    }

    public function detachProject(Request $request, Program $program, Project $project): ProgramResource
    {
        $this->authorize('attachProject', $program);

        $program = $this->programService->detachProject($program, $project, $request->user());

        return new ProgramResource($program);
    }
}
