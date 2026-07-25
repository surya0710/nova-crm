<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Models\FeedbackCampaign;
use App\Services\Hrms\FeedbackService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeedbackReportController extends Controller
{
    public function __construct(protected FeedbackService $service) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', FeedbackCampaign::class);

        $campaigns = FeedbackCampaign::query()
            ->whereIn('status', ['closed', 'archived', 'active'])
            ->with('cycle')
            ->withCount('requests')
            ->latest()
            ->paginate(20);

        return view('hrms.performance.feedback.reports.index', [
            'campaigns' => $campaigns,
            'statuses' => config('hrms.feedback_campaign_statuses', []),
        ]);
    }

    public function show(FeedbackCampaign $campaign): View
    {
        $this->authorize('view', $campaign);

        $aggregation = $this->service->aggregateFeedback($campaign);
        $summary = $campaign->summary ?? $aggregation;

        return view('hrms.performance.feedback.reports.show', [
            'campaign' => $campaign->load('cycle', 'template'),
            'aggregation' => $aggregation,
            'summary' => $summary,
            'participantTypes' => config('hrms.feedback_participant_types', []),
        ]);
    }
}
