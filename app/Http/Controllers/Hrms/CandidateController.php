<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\CreateCandidateRequest;
use App\Models\Candidate;
use App\Models\CandidateEvaluation;
use App\Models\InterviewRound;
use App\Services\Recruitment\CandidateService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CandidateController extends Controller
{
    public function __construct(protected CandidateService $service)
    {
        $this->authorizeResource(Candidate::class, 'candidate');
    }

    public function index(Request $request): View
    {
        $query = Candidate::query()->latest();

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($builder) use ($search): void {
                $builder->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return view('hrms.recruitment.candidates.index', [
            'candidates' => $query->paginate(15)->withQueryString(),
            'sources' => config('hrms.recruitment.candidate_sources', []),
            'search' => $request->string('search')->toString(),
        ]);
    }

    public function show(Candidate $candidate): View
    {
        $candidate->load(['documents', 'applications.jobOpening', 'applications.interviewRounds.interviewStage']);

        $applicationIds = $candidate->applications->pluck('id');

        return view('hrms.recruitment.candidates.show', [
            'candidate' => $candidate,
            'documentCategories' => config('hrms.recruitment.document_categories', []),
            'interviewRounds' => InterviewRound::query()
                ->whereIn('job_application_id', $applicationIds)
                ->with(['interviewStage', 'jobApplication'])
                ->latest('scheduled_at')
                ->get(),
            'evaluations' => CandidateEvaluation::query()
                ->whereHas('interviewRound', fn ($q) => $q->whereIn('job_application_id', $applicationIds))
                ->with(['participant', 'interviewRound.interviewStage'])
                ->latest()
                ->get(),
            'offerLetters' => \App\Models\OfferLetter::query()
                ->whereIn('job_application_id', $applicationIds)
                ->with(['approvals.approver', 'negotiations'])
                ->latest()
                ->get(),
            'hiringDecisions' => \App\Models\HiringDecision::query()
                ->whereIn('job_application_id', $applicationIds)
                ->with('decisionMaker')
                ->latest('decision_date')
                ->get(),
        ]);
    }

    public function store(CreateCandidateRequest $request): RedirectResponse
    {
        $org = app(TenantContext::class)->get();
        $data = array_merge($request->validated(), ['organization_id' => $org?->id]);
        unset($data['resume']);

        $candidate = $this->service->createCandidate(
            $data,
            $request->user(),
            $request->file('resume'),
        );

        return redirect()->route('hrms.recruitment.candidates.show', $candidate)
            ->with('status', 'recruitment-candidate-created');
    }

    public function destroy(Candidate $candidate): RedirectResponse
    {
        $this->service->deleteCandidate($candidate, request()->user());

        return redirect()->route('hrms.recruitment.candidates.index')
            ->with('status', 'recruitment-candidate-deleted');
    }
}
