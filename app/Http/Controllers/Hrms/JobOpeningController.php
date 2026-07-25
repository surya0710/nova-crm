<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\CreateJobOpeningRequest;
use App\Models\JobOpening;
use App\Models\JobRequisition;
use App\Services\Recruitment\JobOpeningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JobOpeningController extends Controller
{
    public function __construct(protected JobOpeningService $service)
    {
        $this->authorizeResource(JobOpening::class, 'job_opening');
    }

    public function index(Request $request): View
    {
        $query = JobOpening::query()
            ->with(['requisition', 'department', 'designation'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return view('hrms.recruitment.openings.index', [
            'openings' => $query->paginate(15)->withQueryString(),
            'requisitions' => JobRequisition::query()->where('status', 'approved')->with('designation')->latest()->get(),
            'statuses' => config('hrms.recruitment.opening_statuses', []),
            'filterStatus' => $request->string('status')->toString(),
        ]);
    }

    public function show(JobOpening $jobOpening): View
    {
        return view('hrms.recruitment.openings.show', [
            'opening' => $jobOpening->load(['requisition', 'department', 'designation', 'applications.candidate']),
            'statuses' => config('hrms.recruitment.opening_statuses', []),
        ]);
    }

    public function store(CreateJobOpeningRequest $request): RedirectResponse
    {
        $requisition = JobRequisition::query()->findOrFail($request->validated('job_requisition_id'));
        $opening = $this->service->createOpeningFromRequisition($requisition, $request->validated(), $request->user());

        return redirect()->route('hrms.recruitment.openings.show', $opening)
            ->with('status', 'recruitment-opening-created');
    }

    public function destroy(JobOpening $jobOpening): RedirectResponse
    {
        $this->service->deleteOpening($jobOpening, request()->user());

        return redirect()->route('hrms.recruitment.openings.index')
            ->with('status', 'recruitment-opening-deleted');
    }

    public function publish(Request $request, JobOpening $jobOpening): RedirectResponse
    {
        $this->authorize('publish', $jobOpening);
        $this->service->publishOpening($jobOpening, $request->user());

        return redirect()->route('hrms.recruitment.openings.show', $jobOpening)
            ->with('status', 'recruitment-opening-published');
    }
}
