<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\CreatePerformanceCycleRequest;
use App\Http\Requests\Hrms\UpdatePerformanceCycleRequest;
use App\Models\PerformanceCycle;
use App\Services\Hrms\PerformanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PerformanceCycleController extends Controller
{
    public function __construct(protected PerformanceService $service)
    {
        $this->authorizeResource(PerformanceCycle::class, 'cycle');
    }

    public function index(): View
    {
        return view('hrms.performance.cycles.index', [
            'cycles' => PerformanceCycle::query()->latest()->paginate(20),
            'cycleTypes' => config('hrms.performance_cycle_types', []),
            'cycleStatuses' => config('hrms.performance_cycle_statuses', []),
            'activeCycle' => $this->service->resolveActiveCycle(),
        ]);
    }

    public function store(CreatePerformanceCycleRequest $request): RedirectResponse
    {
        $this->service->createCycle($request->validated(), $request->user());

        return redirect()->route('hrms.performance.cycles.index')
            ->with('status', 'hrms-performance-cycle-created');
    }

    public function update(UpdatePerformanceCycleRequest $request, PerformanceCycle $cycle): RedirectResponse
    {
        $this->service->updateCycle($cycle, $request->validated(), $request->user());

        return redirect()->route('hrms.performance.cycles.index')
            ->with('status', 'hrms-performance-cycle-updated');
    }

    public function destroy(PerformanceCycle $cycle): RedirectResponse
    {
        $this->service->deleteCycle($cycle, request()->user());

        return redirect()->route('hrms.performance.cycles.index')
            ->with('status', 'hrms-performance-cycle-deleted');
    }

    public function activate(PerformanceCycle $cycle): RedirectResponse
    {
        $this->authorize('update', $cycle);
        $this->service->activateCycle($cycle, request()->user());

        return redirect()->route('hrms.performance.cycles.index')
            ->with('status', 'hrms-performance-cycle-activated');
    }

    public function close(PerformanceCycle $cycle): RedirectResponse
    {
        $this->authorize('update', $cycle);
        $this->service->closeCycle($cycle, request()->user());

        return redirect()->route('hrms.performance.cycles.index')
            ->with('status', 'hrms-performance-cycle-closed');
    }

    public function archive(PerformanceCycle $cycle): RedirectResponse
    {
        $this->authorize('update', $cycle);
        $this->service->archiveCycle($cycle, request()->user());

        return redirect()->route('hrms.performance.cycles.index')
            ->with('status', 'hrms-performance-cycle-archived');
    }
}
