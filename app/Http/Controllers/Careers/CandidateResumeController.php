<?php

namespace App\Http\Controllers\Careers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Careers\UploadCandidateResumeRequest;
use App\Models\CandidateResume;
use App\Models\Organization;
use App\Services\Recruitment\ResumeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CandidateResumeController extends Controller
{
    public function __construct(protected ResumeService $resumeService) {}

    public function index(Organization $organization): View
    {
        $account = auth('candidate')->user();

        return view('careers.resumes.index', [
            'organization' => $organization,
            'resumes' => $account->candidate?->resumes()->latest('uploaded_at')->get() ?? collect(),
        ]);
    }

    public function store(UploadCandidateResumeRequest $request, Organization $organization): RedirectResponse
    {
        $account = auth('candidate')->user();
        $this->resumeService->upload(
            $account->candidate,
            $request->validated('name'),
            $request->file('resume'),
            $request->boolean('is_default'),
        );

        return redirect()->route('careers.resumes.index', $organization)
            ->with('status', __('Resume uploaded successfully.'));
    }

    public function setDefault(Organization $organization, CandidateResume $candidate_resume): RedirectResponse
    {
        $account = auth('candidate')->user();
        if ((int) $candidate_resume->candidate_id !== (int) $account->candidate_id) {
            abort(403);
        }

        $this->resumeService->setDefault($candidate_resume);

        return back()->with('status', __('Default resume updated.'));
    }

    public function destroy(Organization $organization, CandidateResume $candidate_resume): RedirectResponse
    {
        $account = auth('candidate')->user();
        if ((int) $candidate_resume->candidate_id !== (int) $account->candidate_id) {
            abort(403);
        }

        $this->resumeService->delete($candidate_resume);

        return back()->with('status', __('Resume deleted.'));
    }
}
