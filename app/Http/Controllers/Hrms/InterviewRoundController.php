<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\CreateInterviewRoundRequest;
use App\Models\Employee;
use App\Models\EvaluationTemplate;
use App\Models\InterviewRound;
use App\Models\InterviewStage;
use App\Models\JobApplication;
use App\Models\Organization;
use App\Services\Recruitment\InterviewRoundService;
use App\Services\Recruitment\InterviewStageService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InterviewRoundController extends Controller
{
    public function __construct(
        protected InterviewRoundService $service,
        protected InterviewStageService $stageService,
    ) {
        $this->authorizeResource(InterviewRound::class, 'interview_round');
    }

    public function index(Request $request): View
    {
        $org = app(TenantContext::class)->get();

        if ($org instanceof Organization) {
            $this->stageService->ensureDefaultStages($org, $request->user());
        }

        $query = InterviewRound::query()
            ->with(['jobApplication.candidate', 'jobApplication.jobOpening', 'interviewStage', 'participants.employee'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return view('hrms.recruitment.interview-rounds.index', [
            'rounds' => $query->paginate(15)->withQueryString(),
            'applications' => JobApplication::query()->with(['candidate', 'jobOpening'])->latest()->limit(50)->get(),
            'stages' => InterviewStage::query()->orderBy('sort_order')->get(),
            'templates' => EvaluationTemplate::query()->where('is_active', true)->orderBy('name')->get(),
            'employees' => Employee::query()->orderBy('first_name')->get(),
            'interviewTypes' => config('hrms.recruitment.interview_types', []),
            'statuses' => config('hrms.recruitment.interview_round_statuses', []),
            'participantTypes' => config('hrms.recruitment.participant_types', []),
            'participantRoles' => config('hrms.recruitment.participant_roles', []),
            'filterStatus' => $request->string('status')->toString(),
        ]);
    }

    public function show(InterviewRound $interviewRound): View
    {
        return view('hrms.recruitment.interview-rounds.show', [
            'round' => $interviewRound->load([
                'jobApplication.candidate',
                'jobApplication.jobOpening',
                'interviewStage',
                'evaluationTemplate.sections.questions',
                'participants.employee',
                'evaluations.participant',
            ]),
            'statuses' => config('hrms.recruitment.interview_round_statuses', []),
            'recommendations' => config('hrms.recruitment.evaluation_recommendations', []),
        ]);
    }

    public function store(CreateInterviewRoundRequest $request): RedirectResponse
    {
        $round = $this->service->createRound($request->validated(), $request->user());

        return redirect()->route('hrms.recruitment.interview-rounds.show', $round)
            ->with('status', 'recruitment-interview-round-created');
    }

    public function destroy(InterviewRound $interviewRound): RedirectResponse
    {
        $this->service->deleteRound($interviewRound, request()->user());

        return redirect()->route('hrms.recruitment.interview-rounds.index')
            ->with('status', 'recruitment-interview-round-deleted');
    }

    public function complete(Request $request, InterviewRound $interviewRound): RedirectResponse
    {
        $this->authorize('complete', $interviewRound);

        $this->service->completeRound($interviewRound, $request->user());

        return redirect()->route('hrms.recruitment.interview-rounds.show', $interviewRound)
            ->with('status', 'recruitment-interview-round-completed');
    }

    public function cancel(Request $request, InterviewRound $interviewRound): RedirectResponse
    {
        $this->authorize('cancel', $interviewRound);

        $this->service->cancelRound($interviewRound, $request->user(), $request->string('reason')->toString() ?: null);

        return redirect()->route('hrms.recruitment.interview-rounds.show', $interviewRound)
            ->with('status', 'recruitment-interview-round-cancelled');
    }
}
