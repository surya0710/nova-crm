<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttachProjectToProgramRequest;
use App\Http\Requests\StoreProgramRequest;
use App\Http\Requests\UpdateProgramRequest;
use App\Models\Program;
use App\Models\Project;
use App\Services\ProgramService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProgramController extends Controller
{
    public function __construct(protected ProgramService $programService)
    {
        $this->authorizeResource(Program::class, 'program');
    }

    public function index(Request $request, TenantContext $tenant): View
    {
        $programs = $this->programService->list($tenant->id(), [
            'search' => $request->string('search')->trim()->toString() ?: null,
            'status' => $request->string('status')->toString() ?: null,
            'portfolio_id' => $request->integer('portfolio_id') ?: null,
            'manager_id' => $request->integer('manager_id') ?: null,
        ]);

        return view('programs.index', [
            'programs' => $programs,
            'organization' => $tenant->get(),
        ]);
    }

    public function create(TenantContext $tenant): View
    {
        return view('programs.create', [
            'program' => new Program(['status' => 'active']),
            'organization' => $tenant->get(),
        ]);
    }

    public function store(StoreProgramRequest $request, TenantContext $tenant): RedirectResponse
    {
        $program = $this->programService->create([
            ...$request->validated(),
            'organization_id' => $tenant->id(),
        ], $request->user());

        return redirect()
            ->route('programs.show', $program)
            ->with('status', 'program-created');
    }

    public function show(Program $program): View
    {
        $program->load(['manager', 'portfolio', 'projects.status']);

        return view('programs.show', [
            'program' => $program,
        ]);
    }

    public function edit(Program $program): View
    {
        $program->load(['portfolio', 'projects']);

        return view('programs.edit', [
            'program' => $program,
        ]);
    }

    public function update(UpdateProgramRequest $request, Program $program): RedirectResponse
    {
        try {
            $this->programService->update($program, $request->validated(), $request->user());
        } catch (ValidationException $e) {
            throw $e;
        }

        return redirect()
            ->route('programs.show', $program)
            ->with('status', 'program-updated');
    }

    public function destroy(Program $program, Request $request): RedirectResponse
    {
        try {
            $this->programService->delete($program, $request->user());
        } catch (ValidationException $e) {
            return redirect()
                ->route('programs.index')
                ->withErrors($e->errors());
        }

        return redirect()
            ->route('programs.index')
            ->with('status', 'program-deleted');
    }

    public function dashboard(Program $program): View
    {
        $this->authorize('viewDashboard', $program);

        $program->load(['manager', 'portfolio', 'projects.status']);

        return view('programs.dashboard', [
            'program' => $program,
        ]);
    }

    public function attachProject(AttachProjectToProgramRequest $request, Program $program): RedirectResponse
    {
        $project = Project::query()->findOrFail($request->validated('project_id'));

        try {
            $this->programService->attachProject($program, $project, $request->user());
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        }

        return redirect()
            ->back()
            ->with('status', 'program-project-attached');
    }

    public function detachProject(Program $program, Project $project, Request $request): RedirectResponse
    {
        $this->authorize('attachProject', $program);

        $this->programService->detachProject($program, $project, $request->user());

        return redirect()
            ->back()
            ->with('status', 'program-project-detached');
    }
}
