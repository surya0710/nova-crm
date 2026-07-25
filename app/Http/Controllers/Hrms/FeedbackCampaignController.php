<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\AddFeedbackParticipantRequest;
use App\Http\Requests\Hrms\CreateFeedbackCampaignRequest;
use App\Http\Requests\Hrms\UpdateFeedbackCampaignRequest;
use App\Models\Employee;
use App\Models\FeedbackCampaign;
use App\Models\FeedbackTemplate;
use App\Models\PerformanceCycle;
use App\Services\Hrms\FeedbackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeedbackCampaignController extends Controller
{
    public function __construct(protected FeedbackService $service)
    {
        $this->authorizeResource(FeedbackCampaign::class, 'campaign');
    }

    public function index(Request $request): View
    {
        $query = FeedbackCampaign::query()
            ->with(['cycle', 'template'])
            ->withCount(['participants', 'requests'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return view('hrms.performance.feedback.campaigns.index', [
            'campaigns' => $query->paginate(20)->withQueryString(),
            'cycles' => PerformanceCycle::query()->orderByDesc('start_date')->get(),
            'templates' => FeedbackTemplate::query()->where('is_active', true)->orderBy('name')->get(),
            'statuses' => config('hrms.feedback_campaign_statuses', []),
        ]);
    }

    public function store(CreateFeedbackCampaignRequest $request): RedirectResponse
    {
        $campaign = $this->service->createCampaign($request->validated(), $request->user());

        return redirect()->route('hrms.performance.feedback.campaigns.show', $campaign)
            ->with('status', 'hrms-feedback-campaign-created');
    }

    public function show(FeedbackCampaign $campaign): View
    {
        $campaign->load([
            'cycle', 'template.questions', 'participants.subjectEmployee',
            'participants.participantEmployee', 'participants.request',
        ]);

        return view('hrms.performance.feedback.campaigns.show', [
            'campaign' => $campaign,
            'employees' => Employee::query()->orderBy('first_name')->limit(200)->get(),
            'statuses' => config('hrms.feedback_campaign_statuses', []),
            'participantTypes' => config('hrms.feedback_participant_types', []),
            'requestStatuses' => config('hrms.feedback_request_statuses', []),
        ]);
    }

    public function update(UpdateFeedbackCampaignRequest $request, FeedbackCampaign $campaign): RedirectResponse
    {
        $this->service->updateCampaign($campaign, $request->validated(), $request->user());

        return redirect()->route('hrms.performance.feedback.campaigns.show', $campaign)
            ->with('status', 'hrms-feedback-campaign-updated');
    }

    public function destroy(FeedbackCampaign $campaign): RedirectResponse
    {
        if (! $campaign->isEditable()) {
            return back()->withErrors(['campaign' => __('Only draft campaigns can be deleted.')]);
        }

        $campaign->delete();

        return redirect()->route('hrms.performance.feedback.campaigns.index')
            ->with('status', 'hrms-feedback-campaign-deleted');
    }

    public function activate(FeedbackCampaign $campaign): RedirectResponse
    {
        $this->authorize('activate', $campaign);
        $this->service->activateCampaign($campaign, request()->user());

        return redirect()->route('hrms.performance.feedback.campaigns.show', $campaign)
            ->with('status', 'hrms-feedback-campaign-activated');
    }

    public function close(FeedbackCampaign $campaign): RedirectResponse
    {
        $this->authorize('close', $campaign);
        $this->service->closeCampaign($campaign, request()->user());

        return redirect()->route('hrms.performance.feedback.campaigns.show', $campaign)
            ->with('status', 'hrms-feedback-campaign-closed');
    }

    public function addParticipant(AddFeedbackParticipantRequest $request, FeedbackCampaign $campaign): RedirectResponse
    {
        $this->service->addParticipant($campaign, $request->validated(), $request->user());

        return redirect()->route('hrms.performance.feedback.campaigns.show', $campaign)
            ->with('status', 'hrms-feedback-participant-added');
    }

    public function generateRequests(FeedbackCampaign $campaign): RedirectResponse
    {
        $this->authorize('generateRequests', $campaign);
        $requests = $this->service->generateRequests($campaign, request()->user());

        return redirect()->route('hrms.performance.feedback.campaigns.show', $campaign)
            ->with('status', 'hrms-feedback-requests-generated')
            ->with('generated_count', $requests->count());
    }
}
