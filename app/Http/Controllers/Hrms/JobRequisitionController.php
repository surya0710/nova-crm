<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\CreateJobRequisitionRequest;
use App\Http\Requests\Recruitment\UpdateJobRequisitionRequest;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\JobRequisition;
use App\Services\Recruitment\JobRequisitionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JobRequisitionController extends Controller
{
    public function __construct(protected JobRequisitionService $service)
    {
        $this->authorizeResource(JobRequisition::class, 'job_requisition');
    }

    public function index(Request $request): View
    {
        $query = JobRequisition::query()
            ->with(['department', 'designation', 'hiringManager'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return view('hrms.recruitment.requisitions.index', [
            'requisitions' => $query->paginate(15)->withQueryString(),
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(),
            'designations' => Designation::query()->where('is_active', true)->orderBy('name')->get(),
            'employees' => Employee::query()->where('status', 'active')->orderBy('first_name')->get(),
            'employmentTypes' => config('hrms.employment_types', []),
            'statuses' => config('hrms.recruitment.requisition_statuses', []),
            'filterStatus' => $request->string('status')->toString(),
        ]);
    }

    public function show(JobRequisition $jobRequisition): View
    {
        return view('hrms.recruitment.requisitions.show', [
            'requisition' => $jobRequisition->load(['department', 'designation', 'hiringManager', 'requester', 'openings']),
            'statuses' => config('hrms.recruitment.requisition_statuses', []),
        ]);
    }

    public function store(CreateJobRequisitionRequest $request): RedirectResponse
    {
        $this->service->createRequisition($request->validated(), $request->user());

        return redirect()->route('hrms.recruitment.requisitions.index')
            ->with('status', 'recruitment-requisition-created');
    }

    public function update(UpdateJobRequisitionRequest $request, JobRequisition $jobRequisition): RedirectResponse
    {
        $this->service->updateRequisition($jobRequisition, $request->validated(), $request->user());

        return redirect()->route('hrms.recruitment.requisitions.show', $jobRequisition)
            ->with('status', 'recruitment-requisition-updated');
    }

    public function destroy(JobRequisition $jobRequisition): RedirectResponse
    {
        $this->service->deleteRequisition($jobRequisition, request()->user());

        return redirect()->route('hrms.recruitment.requisitions.index')
            ->with('status', 'recruitment-requisition-deleted');
    }

    public function submit(Request $request, JobRequisition $jobRequisition): RedirectResponse
    {
        $this->authorize('submit', $jobRequisition);
        $this->service->submitForApproval($jobRequisition, $request->user());

        return redirect()->route('hrms.recruitment.requisitions.show', $jobRequisition)
            ->with('status', 'recruitment-requisition-submitted');
    }

    public function approve(Request $request, JobRequisition $jobRequisition): RedirectResponse
    {
        $this->authorize('approve', $jobRequisition);
        $this->service->approveRequisition($jobRequisition, $request->user());

        return redirect()->route('hrms.recruitment.requisitions.show', $jobRequisition)
            ->with('status', 'recruitment-requisition-approved');
    }

    public function reject(Request $request, JobRequisition $jobRequisition): RedirectResponse
    {
        $this->authorize('approve', $jobRequisition);
        $this->service->rejectRequisition($jobRequisition, $request->user(), $request->string('reason')->toString());

        return redirect()->route('hrms.recruitment.requisitions.show', $jobRequisition)
            ->with('status', 'recruitment-requisition-rejected');
    }
}
