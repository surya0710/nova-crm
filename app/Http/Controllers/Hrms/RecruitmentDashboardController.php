<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\RecruitmentAnalyticsFilterRequest;
use App\Services\Recruitment\RecruitmentDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecruitmentDashboardController extends Controller
{
    public function __construct(protected RecruitmentDashboardService $dashboard) {}

    public function __invoke(Request $request): View
    {
        $this->authorize('viewAny', \App\Models\JobRequisition::class);

        $user = $request->user();
        $canAnalytics = $user?->hasPermission('recruitment.analytics.view') ?? false;

        $filters = [
            'period' => $request->string('period')->toString() ?: 'month',
            'from' => $request->input('from'),
            'to' => $request->input('to'),
        ];

        $executive = $canAnalytics
            ? $this->dashboard->executiveDashboard($filters, $user)
            : null;

        return view('hrms.recruitment.dashboard', [
            'canAnalytics' => $canAnalytics,
            'executive' => $executive,
            'filters' => $filters,
            'periods' => config('hrms.recruitment.analytics.periods', []),
        ]);
    }

    public function executive(RecruitmentAnalyticsFilterRequest $request): View
    {
        $filters = $request->filters();
        $filters['period'] = $filters['period'] ?? 'month';

        return view('hrms.recruitment.executive', [
            'executive' => $this->dashboard->executiveDashboard($filters, $request->user()),
            'filters' => $filters,
            'periods' => config('hrms.recruitment.analytics.periods', []),
        ]);
    }
}
