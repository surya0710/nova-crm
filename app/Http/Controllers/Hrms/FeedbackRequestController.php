<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\SubmitFeedbackRequest;
use App\Models\Employee;
use App\Models\FeedbackRequest;
use App\Services\Hrms\FeedbackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeedbackRequestController extends Controller
{
    public function __construct(protected FeedbackService $service) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', FeedbackRequest::class);

        $query = FeedbackRequest::query()
            ->with(['campaign', 'subjectEmployee', 'participantEmployee'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('campaign_id')) {
            $query->where('feedback_campaign_id', $request->integer('campaign_id'));
        }

        return view('hrms.performance.feedback.requests.index', [
            'requests' => $query->paginate(20)->withQueryString(),
            'statuses' => config('hrms.feedback_request_statuses', []),
            'participantTypes' => config('hrms.feedback_participant_types', []),
        ]);
    }

    public function show(FeedbackRequest $feedbackRequest): View
    {
        $this->authorize('view', $feedbackRequest);

        $feedbackRequest->load([
            'campaign.template.questions',
            'subjectEmployee',
            'responses.question',
        ]);

        $hideIdentity = $feedbackRequest->is_anonymous
            && ! request()->user()->hasPermission('performance.feedback.manage', $feedbackRequest->organization);

        return view('hrms.performance.feedback.requests.show', [
            'feedbackRequest' => $feedbackRequest,
            'questions' => $feedbackRequest->campaign->template->questions,
            'statuses' => config('hrms.feedback_request_statuses', []),
            'participantTypes' => config('hrms.feedback_participant_types', []),
            'hideIdentity' => $hideIdentity,
        ]);
    }

    public function start(FeedbackRequest $feedbackRequest): RedirectResponse
    {
        $this->authorize('submit', $feedbackRequest);
        $this->service->startFeedback($feedbackRequest, request()->user());

        return redirect()->route('hrms.performance.feedback.requests.show', $feedbackRequest)
            ->with('status', 'hrms-feedback-started');
    }

    public function submit(SubmitFeedbackRequest $request, FeedbackRequest $feedbackRequest): RedirectResponse
    {
        $this->service->submitFeedback($feedbackRequest, $request->validated('responses'), $request->user());

        return redirect()->route('hrms.performance.feedback.requests.show', $feedbackRequest)
            ->with('status', 'hrms-feedback-submitted');
    }

    public function myFeedback(Request $request): View
    {
        $this->authorize('viewAny', FeedbackRequest::class);

        $employee = Employee::query()
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $requests = FeedbackRequest::query()
            ->where('participant_employee_id', $employee->id)
            ->with(['campaign', 'subjectEmployee'])
            ->latest()
            ->paginate(20);

        return view('hrms.performance.feedback.my-feedback.index', [
            'requests' => $requests,
            'statuses' => config('hrms.feedback_request_statuses', []),
            'participantTypes' => config('hrms.feedback_participant_types', []),
        ]);
    }
}
