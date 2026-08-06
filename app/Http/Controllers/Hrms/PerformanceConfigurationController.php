<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\UpdatePerformanceConfigurationRequest;
use App\Models\PerformanceConfiguration;
use App\Models\PerformanceRatingScale;
use App\Services\Hrms\PerformanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PerformanceConfigurationController extends Controller
{
    public function __construct(protected PerformanceService $service) {}

    public function edit(): View
    {
        $this->authorize('viewAny', PerformanceConfiguration::class);

        return view('hrms.performance.configuration.edit', [
            'configuration' => $this->service->getOrCreateConfiguration(),
            'frequencies' => config('hrms.performance_review_frequencies', []),
            'visibilities' => config('hrms.performance_review_visibilities', []),
            'ratingScales' => PerformanceRatingScale::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(UpdatePerformanceConfigurationRequest $request): RedirectResponse
    {
        $this->authorize('update', PerformanceConfiguration::class);
        $this->service->updateConfiguration($request->validated(), $request->user());

        return redirect()->route('hrms.performance.configuration.edit')
            ->with('status', 'hrms-performance-configuration-updated');
    }
}
