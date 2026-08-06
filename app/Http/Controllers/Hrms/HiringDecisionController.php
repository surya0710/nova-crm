<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\CreateHiringDecisionRequest;
use App\Models\HiringDecision;
use App\Models\JobApplication;
use App\Services\Recruitment\HiringDecisionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class HiringDecisionController extends Controller
{
    public function __construct(protected HiringDecisionService $service)
    {
        $this->authorizeResource(HiringDecision::class, 'hiring_decision');
    }

    public function index(): View
    {
        return view('hrms.recruitment.hiring-decisions.index', [
            'decisions' => HiringDecision::query()
                ->with(['jobApplication.candidate', 'decisionMaker'])
                ->latest('decision_date')
                ->paginate(15),
            'applications' => JobApplication::query()
                ->with('candidate')
                ->where('status', 'active')
                ->latest()
                ->get(),
            'recommendations' => config('hrms.recruitment.hiring_recommendations', []),
        ]);
    }

    public function show(HiringDecision $hiringDecision): View
    {
        return view('hrms.recruitment.hiring-decisions.show', [
            'decision' => $hiringDecision->load(['jobApplication.candidate', 'jobApplication.jobOpening', 'decisionMaker']),
        ]);
    }

    public function store(CreateHiringDecisionRequest $request): RedirectResponse
    {
        $decision = $this->service->recordDecision($request->validated(), $request->user());

        return redirect()->route('hrms.recruitment.hiring-decisions.show', $decision)
            ->with('status', 'recruitment-hiring-decision-created');
    }
}
