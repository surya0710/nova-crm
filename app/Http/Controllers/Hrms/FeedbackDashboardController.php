<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\FeedbackCampaign;
use App\Models\FeedbackRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeedbackDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $this->authorize('viewAny', FeedbackCampaign::class);

        $campaigns = FeedbackCampaign::query()
            ->with(['cycle', 'template'])
            ->withCount(['participants', 'requests'])
            ->latest()
            ->paginate(10);

        $activeCampaign = FeedbackCampaign::query()
            ->where('status', 'active')
            ->with('cycle')
            ->first();

        $pendingCount = 0;
        if ($request->user()->hasPermission('performance.feedback.submit')) {
            $employee = Employee::query()
                ->where('user_id', $request->user()->id)
                ->first();

            if ($employee) {
                $pendingCount = FeedbackRequest::query()
                    ->where('participant_employee_id', $employee->id)
                    ->whereIn('status', ['pending', 'started'])
                    ->count();
            }
        }

        return view('hrms.performance.feedback.index', [
            'campaigns' => $campaigns,
            'activeCampaign' => $activeCampaign,
            'pendingCount' => $pendingCount,
            'statuses' => config('hrms.feedback_campaign_statuses', []),
        ]);
    }
}
