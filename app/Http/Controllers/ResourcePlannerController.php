<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Project;
use App\Models\ResourceAllocation;
use App\Models\WorkloadSnapshot;
use App\Services\CapacityPlanningService;
use App\Services\TenantContext;
use App\Services\WorkloadService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResourcePlannerController extends Controller
{
    public function __construct(
        protected WorkloadService $workload,
        protected CapacityPlanningService $capacity,
    ) {}

    public function planner(Request $request, TenantContext $tenant): View
    {
        $this->authorize('viewAny', ResourceAllocation::class);

        $organization = $tenant->get();
        $from = Carbon::parse($request->input('from', now()->startOfWeek()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->input('to', now()->addWeeks(2)->toDateString()))->startOfDay();

        $allocations = ResourceAllocation::query()
            ->with(['employee', 'project', 'task'])
            ->whereDate('planned_start_date', '<=', $to->toDateString())
            ->whereDate('planned_end_date', '>=', $from->toDateString())
            ->orderBy('planned_start_date')
            ->limit(100)
            ->get();

        $team = $this->workload->calculateTeam($organization, $from, $to);

        return view('resources.planner', [
            'organization' => $organization,
            'allocations' => $allocations,
            'team' => $team,
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function capacity(Request $request, TenantContext $tenant): View
    {
        $this->authorize('viewAny', WorkloadSnapshot::class);

        $organization = $tenant->get();
        $from = Carbon::parse($request->input('from', now()->startOfWeek()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->input('to', now()->endOfWeek()->toDateString()))->startOfDay();

        $team = collect($this->workload->calculateTeam($organization, $from, $to))
            ->keyBy('employee_id');

        $employees = Employee::query()
            ->whereIn('id', $team->keys())
            ->orderBy('first_name')
            ->get()
            ->keyBy('id');

        return view('resources.capacity', [
            'organization' => $organization,
            'team' => $team,
            'employees' => $employees,
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function employeeWorkload(Request $request, Employee $employee, TenantContext $tenant): View
    {
        $this->authorize('viewAny', WorkloadSnapshot::class);
        abort_unless((int) $employee->organization_id === (int) $tenant->id(), 404);

        $from = Carbon::parse($request->input('from', now()->startOfMonth()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->input('to', now()->endOfMonth()->toDateString()))->startOfDay();

        $workload = $this->workload->calculateForEmployee($employee, $from, $to);
        $allocations = ResourceAllocation::query()
            ->with(['project', 'task'])
            ->where('employee_id', $employee->id)
            ->whereDate('planned_start_date', '<=', $to->toDateString())
            ->whereDate('planned_end_date', '>=', $from->toDateString())
            ->orderBy('planned_start_date')
            ->get();

        return view('resources.workload', [
            'employee' => $employee,
            'workload' => $workload,
            'allocations' => $allocations,
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function timeline(Request $request, TenantContext $tenant): View
    {
        $this->authorize('viewAny', ResourceAllocation::class);

        $from = Carbon::parse($request->input('from', now()->startOfMonth()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->input('to', now()->addMonths(1)->toDateString()))->startOfDay();

        $allocations = ResourceAllocation::query()
            ->with(['employee', 'project', 'task'])
            ->whereDate('planned_start_date', '<=', $to->toDateString())
            ->whereDate('planned_end_date', '>=', $from->toDateString())
            ->when($request->integer('employee_id'), fn ($q, $id) => $q->where('employee_id', $id))
            ->when($request->integer('project_id'), fn ($q, $id) => $q->where('project_id', $id))
            ->orderBy('planned_start_date')
            ->paginate(30)
            ->withQueryString();

        return view('resources.timeline', [
            'organization' => $tenant->get(),
            'allocations' => $allocations,
            'employees' => Employee::query()->orderBy('first_name')->limit(200)->get(),
            'projects' => Project::query()->where('is_archived', false)->orderBy('name')->limit(200)->get(),
            'from' => $from,
            'to' => $to,
            'filters' => $request->only(['employee_id', 'project_id', 'from', 'to']),
        ]);
    }

    public function forecast(Request $request, TenantContext $tenant): View
    {
        $this->authorize('viewAny', WorkloadSnapshot::class);

        $organization = $tenant->get();
        $from = Carbon::parse($request->input('from', now()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->input(
            'to',
            now()->addDays((int) config('resources.capacity_risk_days', 14))->toDateString()
        ))->startOfDay();

        $forecast = $this->capacity->forecast($organization, $from, $to);
        $risks = $this->capacity->upcomingRisks($organization);

        $employees = Employee::query()
            ->whereIn('id', collect($forecast['employees'] ?? [])->pluck('employee_id'))
            ->get()
            ->keyBy('id');

        return view('resources.forecast', [
            'organization' => $organization,
            'forecast' => $forecast,
            'risks' => $risks,
            'employees' => $employees,
            'from' => $from,
            'to' => $to,
        ]);
    }
}
