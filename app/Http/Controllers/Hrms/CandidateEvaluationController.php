<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\SubmitCandidateEvaluationRequest;
use App\Models\CandidateEvaluation;
use App\Models\InterviewParticipant;
use App\Models\InterviewRound;
use App\Services\Recruitment\CandidateEvaluationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CandidateEvaluationController extends Controller
{
    public function __construct(protected CandidateEvaluationService $service)
    {
        $this->authorizeResource(CandidateEvaluation::class, 'evaluation');
    }

    public function index(): View
    {
        return view('hrms.recruitment.evaluations.index', [
            'evaluations' => CandidateEvaluation::query()
                ->with(['interviewRound.jobApplication.candidate', 'participant.employee'])
                ->latest()
                ->paginate(15),
            'recommendations' => config('hrms.recruitment.evaluation_recommendations', []),
        ]);
    }

    public function show(CandidateEvaluation $evaluation): View
    {
        return view('hrms.recruitment.evaluations.show', [
            'evaluation' => $evaluation->load([
                'interviewRound.jobApplication.candidate',
                'interviewRound.jobApplication.jobOpening',
                'participant.employee',
                'evaluationTemplate.sections.questions',
                'responses.question',
            ]),
            'recommendations' => config('hrms.recruitment.evaluation_recommendations', []),
        ]);
    }

    public function create(InterviewRound $interviewRound): View
    {
        $this->authorize('create', CandidateEvaluation::class);

        return view('hrms.recruitment.evaluations.create', [
            'round' => $interviewRound->load([
                'jobApplication.candidate',
                'evaluationTemplate.sections.questions',
                'participants.employee',
            ]),
            'participants' => $interviewRound->participants,
            'recommendations' => config('hrms.recruitment.evaluation_recommendations', []),
        ]);
    }

    public function store(SubmitCandidateEvaluationRequest $request): RedirectResponse
    {
        $evaluation = $this->service->submitEvaluation($request->validated(), $request->user());

        return redirect()->route('hrms.recruitment.evaluations.show', $evaluation)
            ->with('status', 'recruitment-evaluation-submitted');
    }
}
