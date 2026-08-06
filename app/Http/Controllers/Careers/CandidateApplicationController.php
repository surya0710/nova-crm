<?php

namespace App\Http\Controllers\Careers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Careers\GuestApplyRequest;
use App\Http\Requests\Careers\UpdateApplicationResumeRequest;
use App\Models\CandidateResume;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\Organization;
use App\Services\Recruitment\PublicApplicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CandidateApplicationController extends Controller
{
    public function __construct(protected PublicApplicationService $applicationService) {}

    public function index(Organization $organization): View
    {
        $account = auth('candidate')->user();
        $applications = JobApplication::query()
            ->with(['jobOpening.department', 'resume'])
            ->where('candidate_id', $account->candidate_id)
            ->latest('applied_date')
            ->get();

        return view('careers.applications.index', [
            'organization' => $organization,
            'applications' => $applications,
        ]);
    }

    public function show(Organization $organization, JobApplication $job_application): View
    {
        $account = auth('candidate')->user();
        if ((int) $job_application->candidate_id !== (int) $account->candidate_id) {
            abort(403);
        }

        $job_application->load(['jobOpening.department', 'resume', 'interviewRounds', 'offerLetters']);

        return view('careers.applications.show', [
            'organization' => $organization,
            'application' => $job_application,
            'timeline' => $job_application->portalTimeline(),
        ]);
    }

    public function guestApply(GuestApplyRequest $request, Organization $organization, JobOpening $job_opening): RedirectResponse
    {
        $this->applicationService->applyAsGuest(
            $organization,
            $job_opening,
            $request->safe()->except(['resume']),
            $request->file('resume'),
            $request->attributes->get('candidate_portal_settings'),
        );

        return redirect()->route('careers.jobs.show', [$organization, $job_opening])
            ->with('status', __('Your application has been submitted.'));
    }

    public function apply(Organization $organization, JobOpening $job_opening): RedirectResponse
    {
        $account = auth('candidate')->user();
        $asDraft = request()->boolean('draft');
        $resume = request()->filled('candidate_resume_id')
            ? CandidateResume::query()->findOrFail(request('candidate_resume_id'))
            : null;

        $application = $this->applicationService->applyAsCandidate($account, $job_opening, $resume, $asDraft);

        $message = $asDraft
            ? __('Application saved as draft.')
            : __('Your application has been submitted.');

        return redirect()->route('careers.applications.show', [$organization, $application])
            ->with('status', $message);
    }

    public function submitDraft(Organization $organization, JobApplication $job_application): RedirectResponse
    {
        $account = auth('candidate')->user();
        $resume = request()->filled('candidate_resume_id')
            ? CandidateResume::query()->findOrFail(request('candidate_resume_id'))
            : null;

        $application = $this->applicationService->submitDraft($job_application, $account, $resume);

        return redirect()->route('careers.applications.show', [$organization, $application])
            ->with('status', __('Application submitted successfully.'));
    }

    public function withdraw(Organization $organization, JobApplication $job_application): RedirectResponse
    {
        $account = auth('candidate')->user();
        $this->applicationService->withdraw($job_application, $account);

        return redirect()->route('careers.applications.index', $organization)
            ->with('status', __('Application withdrawn.'));
    }

    public function updateResume(UpdateApplicationResumeRequest $request, Organization $organization, JobApplication $job_application): RedirectResponse
    {
        $account = auth('candidate')->user();
        $resume = CandidateResume::query()->findOrFail($request->validated('candidate_resume_id'));
        $this->applicationService->updateResume($job_application, $account, $resume);

        return back()->with('status', __('Application resume updated.'));
    }
}
