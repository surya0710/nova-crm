<?php

namespace App\Http\Controllers\Careers;

use App\Http\Controllers\Controller;
use App\Models\JobOpening;
use App\Models\Organization;
use App\Services\Recruitment\SavedJobService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CandidateSavedJobController extends Controller
{
    public function __construct(protected SavedJobService $savedJobService) {}

    public function index(Organization $organization): View
    {
        $account = auth('candidate')->user();

        return view('careers.saved-jobs.index', [
            'organization' => $organization,
            'savedJobs' => $this->savedJobService->listForAccount($account),
        ]);
    }

    public function store(Organization $organization, JobOpening $job_opening): RedirectResponse
    {
        $account = auth('candidate')->user();
        $this->savedJobService->save($account, $job_opening);

        return back()->with('status', __('Job saved.'));
    }

    public function destroy(Organization $organization, JobOpening $job_opening): RedirectResponse
    {
        $account = auth('candidate')->user();
        $this->savedJobService->remove($account, $job_opening);

        return back()->with('status', __('Job removed from saved list.'));
    }
}
