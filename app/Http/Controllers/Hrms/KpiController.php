<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\CreateKpiRequest;
use App\Http\Requests\Hrms\UpdateKpiRequest;
use App\Models\Kpi;
use App\Services\Hrms\GoalManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class KpiController extends Controller
{
    public function __construct(protected GoalManagementService $service)
    {
        $this->authorizeResource(Kpi::class, 'kpi');
    }

    public function index(): View
    {
        return view('hrms.performance.kpis.index', [
            'kpis' => Kpi::query()->latest()->paginate(20),
            'measurementTypes' => config('hrms.goal_measurement_types', []),
        ]);
    }

    public function store(CreateKpiRequest $request): RedirectResponse
    {
        $this->service->createKpi($request->validated(), $request->user());

        return redirect()->route('hrms.performance.kpis.index')
            ->with('status', 'hrms-kpi-created');
    }

    public function update(UpdateKpiRequest $request, Kpi $kpi): RedirectResponse
    {
        $this->service->updateKpi($kpi, $request->validated(), $request->user());

        return redirect()->route('hrms.performance.kpis.index')
            ->with('status', 'hrms-kpi-updated');
    }

    public function destroy(Kpi $kpi): RedirectResponse
    {
        $this->service->deleteKpi($kpi, request()->user());

        return redirect()->route('hrms.performance.kpis.index')
            ->with('status', 'hrms-kpi-deleted');
    }
}
