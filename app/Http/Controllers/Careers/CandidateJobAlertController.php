<?php

namespace App\Http\Controllers\Careers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Careers\StoreJobAlertRequest;
use App\Http\Requests\Careers\UpdateJobAlertRequest;
use App\Models\CandidateJobAlert;
use App\Models\Department;
use App\Models\Organization;
use App\Services\Recruitment\JobAlertService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CandidateJobAlertController extends Controller
{
    public function __construct(protected JobAlertService $jobAlertService) {}

    public function index(Organization $organization): View
    {
        $account = auth('candidate')->user();

        return view('careers.job-alerts.index', [
            'organization' => $organization,
            'alerts' => $this->jobAlertService->listForAccount($account),
            'departments' => Department::query()->where('organization_id', $organization->id)->orderBy('name')->get(),
            'employmentTypes' => config('hrms.employment_types', []),
        ]);
    }

    public function store(StoreJobAlertRequest $request, Organization $organization): RedirectResponse
    {
        $account = auth('candidate')->user();
        $this->jobAlertService->subscribe($account, $request->validated());

        return back()->with('status', __('Job alert created.'));
    }

    public function update(UpdateJobAlertRequest $request, Organization $organization, CandidateJobAlert $candidate_job_alert): RedirectResponse
    {
        $account = auth('candidate')->user();
        $this->jobAlertService->update($candidate_job_alert, $account, $request->validated());

        return back()->with('status', __('Job alert updated.'));
    }

    public function destroy(Organization $organization, CandidateJobAlert $candidate_job_alert): RedirectResponse
    {
        $account = auth('candidate')->user();
        $this->jobAlertService->unsubscribe($candidate_job_alert, $account);

        return back()->with('status', __('Job alert removed.'));
    }
}
