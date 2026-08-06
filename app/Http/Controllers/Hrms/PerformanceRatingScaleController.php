<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\CreatePerformanceRatingScaleRequest;
use App\Http\Requests\Hrms\UpdatePerformanceRatingScaleRequest;
use App\Models\PerformanceRatingScale;
use App\Services\Hrms\PerformanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PerformanceRatingScaleController extends Controller
{
    public function __construct(protected PerformanceService $service)
    {
        $this->authorizeResource(PerformanceRatingScale::class, 'rating_scale');
    }

    public function index(): View
    {
        return view('hrms.performance.rating-scales.index', [
            'scales' => PerformanceRatingScale::query()->with('levels')->latest()->paginate(20),
            'defaultLevels' => config('hrms.performance.default_rating_scale_levels', []),
        ]);
    }

    public function store(CreatePerformanceRatingScaleRequest $request): RedirectResponse
    {
        $this->service->createRatingScale($request->validated(), $request->user());

        return redirect()->route('hrms.performance.rating-scales.index')
            ->with('status', 'hrms-performance-rating-scale-created');
    }

    public function update(UpdatePerformanceRatingScaleRequest $request, PerformanceRatingScale $ratingScale): RedirectResponse
    {
        $this->service->updateRatingScale($ratingScale, $request->validated(), $request->user());

        return redirect()->route('hrms.performance.rating-scales.index')
            ->with('status', 'hrms-performance-rating-scale-updated');
    }

    public function destroy(PerformanceRatingScale $ratingScale): RedirectResponse
    {
        $this->service->deleteRatingScale($ratingScale, request()->user());

        return redirect()->route('hrms.performance.rating-scales.index')
            ->with('status', 'hrms-performance-rating-scale-deleted');
    }
}
