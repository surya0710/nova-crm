<?php

namespace App\Http\Controllers\Careers;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use App\Models\OfferLetter;
use App\Models\Organization;
use App\Services\Recruitment\CandidateProfileService;
use App\Services\Recruitment\SavedJobService;
use Illuminate\View\View;

class CandidateDashboardController extends Controller
{
    public function __construct(
        protected CandidateProfileService $profileService,
        protected SavedJobService $savedJobService,
    ) {}

    public function index(Organization $organization): View
    {
        $account = auth('candidate')->user();
        $candidate = $account->candidate;

        $applications = JobApplication::query()
            ->with('jobOpening')
            ->where('candidate_id', $candidate->id)
            ->where('is_draft', false)
            ->where('status', 'active')
            ->latest('applied_date')
            ->limit(5)
            ->get();

        $interviewInvitations = JobApplication::query()
            ->with(['jobOpening', 'interviewRounds'])
            ->where('candidate_id', $candidate->id)
            ->where('stage', 'interview')
            ->latest()
            ->limit(5)
            ->get();

        $pendingOffers = OfferLetter::query()
            ->with('jobApplication.jobOpening')
            ->where('candidate_id', $candidate->id)
            ->whereIn('status', ['sent', 'approved'])
            ->latest()
            ->limit(5)
            ->get();

        $savedJobs = $this->savedJobService->listForAccount($account)->take(5);

        return view('careers.dashboard', [
            'organization' => $organization,
            'account' => $account,
            'applications' => $applications,
            'interviewInvitations' => $interviewInvitations,
            'pendingOffers' => $pendingOffers,
            'savedJobs' => $savedJobs,
            'profileCompletion' => $this->profileService->profileCompletion($candidate),
        ]);
    }
}
