<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\CreateJobApplicationRequest;
use App\Models\Candidate;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Services\Recruitment\JobApplicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JobApplicationController extends Controller
{
    public function __construct(protected JobApplicationService $service)
    {
        $this->authorizeResource(JobApplication::class, 'job_application');
    }

    public function index(Request $request): View
    {
        $query = JobApplication::query()
            ->with(['candidate', 'jobOpening', 'assignedRecruiter'])
            ->latest('applied_date');

        if ($request->filled('stage')) {
            $query->where('stage', $request->string('stage'));
        }

        return view('hrms.recruitment.applications.index', [
            'applications' => $query->paginate(15)->withQueryString(),
            'candidates' => Candidate::query()->orderBy('first_name')->get(),
            'openings' => JobOpening::query()->where('status', 'published')->orderBy('title')->get(),
            'stages' => config('hrms.recruitment.application_stages', []),
            'sources' => config('hrms.recruitment.candidate_sources', []),
            'filterStage' => $request->string('stage')->toString(),
        ]);
    }

    public function show(JobApplication $jobApplication): View
    {
        return view('hrms.recruitment.applications.show', [
            'application' => $jobApplication->load(['candidate', 'jobOpening', 'assignedRecruiter']),
            'stages' => config('hrms.recruitment.application_stages', []),
            'statuses' => config('hrms.recruitment.application_statuses', []),
        ]);
    }

    public function store(CreateJobApplicationRequest $request): RedirectResponse
    {
        $application = $this->service->createApplication($request->validated(), $request->user());

        return redirect()->route('hrms.recruitment.applications.show', $application)
            ->with('status', 'recruitment-application-created');
    }

    public function destroy(JobApplication $jobApplication): RedirectResponse
    {
        $this->service->deleteApplication($jobApplication, request()->user());

        return redirect()->route('hrms.recruitment.applications.index')
            ->with('status', 'recruitment-application-deleted');
    }

    public function updateStage(Request $request, JobApplication $jobApplication): RedirectResponse
    {
        $this->authorize('update', $jobApplication);

        $request->validate([
            'stage' => ['required', 'string', 'in:'.implode(',', array_keys(config('hrms.recruitment.application_stages', [])))],
        ]);

        $this->service->updateApplication($jobApplication, [
            'stage' => $request->string('stage')->toString(),
        ], $request->user());

        return redirect()->route('hrms.recruitment.applications.show', $jobApplication)
            ->with('status', 'recruitment-application-updated');
    }
}
