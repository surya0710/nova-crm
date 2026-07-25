<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\CreateInterviewStageRequest;
use App\Models\InterviewStage;
use App\Models\Organization;
use App\Services\Recruitment\InterviewStageService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InterviewStageController extends Controller
{
    public function __construct(protected InterviewStageService $service)
    {
        $this->authorizeResource(InterviewStage::class, 'interview_stage');
    }

    public function index(Request $request): View
    {
        $org = app(TenantContext::class)->get();

        if ($org instanceof Organization) {
            $this->service->ensureDefaultStages($org, $request->user());
        }

        return view('hrms.recruitment.interview-stages.index', [
            'stages' => InterviewStage::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function store(CreateInterviewStageRequest $request): RedirectResponse
    {
        $org = app(TenantContext::class)->get();
        $data = array_merge($request->validated(), ['organization_id' => $org?->id]);

        $this->service->createStage($data, $request->user());

        return redirect()->route('hrms.recruitment.interview-stages.index')
            ->with('status', 'recruitment-interview-stage-created');
    }

    public function update(Request $request, InterviewStage $interviewStage): RedirectResponse
    {
        $this->authorize('update', $interviewStage);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $this->service->updateStage($interviewStage, $request->only(['name', 'sort_order', 'is_active']), $request->user());

        return redirect()->route('hrms.recruitment.interview-stages.index')
            ->with('status', 'recruitment-interview-stage-updated');
    }

    public function destroy(InterviewStage $interviewStage): RedirectResponse
    {
        $this->service->deleteStage($interviewStage, request()->user());

        return redirect()->route('hrms.recruitment.interview-stages.index')
            ->with('status', 'recruitment-interview-stage-deleted');
    }
}
