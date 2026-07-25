<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\RecruitmentAnalyticsFilterRequest;
use App\Services\Recruitment\RecruitmentAnalyticsService;
use App\Services\Recruitment\RecruitmentKpiService;
use App\Services\Recruitment\RecruitmentTrendService;
use Illuminate\View\View;

class RecruitmentAnalyticsController extends Controller
{
    public function __construct(
        protected RecruitmentAnalyticsService $analytics,
        protected RecruitmentKpiService $kpis,
        protected RecruitmentTrendService $trends,
    ) {}

    public function __invoke(RecruitmentAnalyticsFilterRequest $request): View
    {
        $filters = $request->filters();
        $filters['period'] = $filters['period'] ?? 'month';
        $section = $request->string('section')->toString() ?: 'funnel';
        $actor = $request->user();

        $payload = match ($section) {
            'sources' => ['sources' => $this->analytics->sourceEffectiveness($filters, $actor)],
            'recruiters' => ['recruiters' => $this->analytics->recruiterPerformance($filters, $actor)],
            'candidates' => ['candidates' => $this->analytics->candidateAnalytics($filters, $actor)],
            'openings' => ['openings' => $this->analytics->jobOpeningAnalytics($filters, $actor)],
            'departments' => ['departments' => $this->analytics->departmentAnalytics($filters, $actor)],
            'trends' => ['trends' => $this->trends->trends($filters, $actor)],
            'time' => ['time' => $this->kpis->timeMetrics($filters, $actor)],
            default => ['funnel' => $this->analytics->funnel($filters, $actor)],
        };

        return view('hrms.recruitment.analytics.index', array_merge([
            'section' => $section,
            'filters' => $filters,
            'periods' => config('hrms.recruitment.analytics.periods', []),
            'leaderboardPeriods' => config('hrms.recruitment.analytics.leaderboard_periods', []),
        ], $payload));
    }
}
