<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Models\Competency;
use App\Models\CompetencyCategory;
use App\Models\PerformanceCycle;
use App\Models\PerformanceRatingScale;
use App\Models\PerformanceReviewTemplate;
use App\Services\Hrms\PerformanceService;
use Illuminate\View\View;

class PerformanceDashboardController extends Controller
{
    public function __construct(protected PerformanceService $service) {}

    public function __invoke(): View
    {
        $this->authorize('viewAny', PerformanceCycle::class);

        return view('hrms.performance.index', [
            'activeCycle' => $this->service->resolveActiveCycle(),
            'cycleCount' => PerformanceCycle::query()->count(),
            'competencyCount' => Competency::query()->count(),
            'categoryCount' => CompetencyCategory::query()->count(),
            'templateCount' => PerformanceReviewTemplate::query()->count(),
            'scaleCount' => PerformanceRatingScale::query()->count(),
            'latestCycles' => PerformanceCycle::query()->latest()->limit(5)->get(),
        ]);
    }
}
