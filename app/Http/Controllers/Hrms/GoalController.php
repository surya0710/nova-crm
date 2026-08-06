<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\AssignGoalRequest;
use App\Http\Requests\Hrms\UpdateGoalProgressRequest;
use App\Http\Requests\Hrms\UpdateGoalRequest;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Goal;
use App\Models\GoalCategory;
use App\Models\GoalTemplate;
use App\Models\HrmsTeam;
use App\Models\Kpi;
use App\Models\PerformanceCycle;
use App\Services\Hrms\GoalManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GoalController extends Controller
{
    public function __construct(protected GoalManagementService $service)
    {
        $this->authorizeResource(Goal::class, 'goal');
    }

    public function index(Request $request): View
    {
        $query = Goal::query()
            ->with(['employee', 'team', 'department', 'cycle', 'kpi', 'template'])
            ->latest();

        if ($request->filled('assignee_type')) {
            $query->where('assignee_type', $request->string('assignee_type'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('cycle_id')) {
            $query->where('performance_cycle_id', $request->integer('cycle_id'));
        }

        return view('hrms.performance.goals.index', [
            'goals' => $query->paginate(20)->withQueryString(),
            'cycles' => PerformanceCycle::query()->orderByDesc('start_date')->get(),
            'employees' => Employee::query()->orderBy('first_name')->limit(200)->get(),
            'teams' => HrmsTeam::query()->where('is_active', true)->orderBy('name')->get(),
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(),
            'templates' => GoalTemplate::query()->where('is_active', true)->orderBy('title')->get(),
            'kpis' => Kpi::query()->where('is_active', true)->orderBy('name')->get(),
            'categories' => GoalCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'assigneeTypes' => config('hrms.goal_assignee_types', []),
            'goalTypes' => config('hrms.goal_types', []),
            'measurementTypes' => config('hrms.goal_measurement_types', []),
            'statuses' => config('hrms.goal_statuses', []),
        ]);
    }

    public function store(AssignGoalRequest $request): RedirectResponse
    {
        $this->service->assignGoal($request->validated(), $request->user());

        return redirect()->route('hrms.performance.goals.index')
            ->with('status', 'hrms-goal-assigned');
    }

    public function show(Goal $goal): View
    {
        $goal->load([
            'employee', 'team', 'department', 'cycle', 'kpi', 'template', 'category',
            'progressUpdates.updater', 'checkins.author',
        ]);

        return view('hrms.performance.goals.show', [
            'goal' => $goal,
            'statuses' => config('hrms.goal_statuses', []),
        ]);
    }

    public function update(UpdateGoalRequest $request, Goal $goal): RedirectResponse
    {
        $this->service->updateGoal($goal, $request->validated(), $request->user());

        return redirect()->route('hrms.performance.goals.show', $goal)
            ->with('status', 'hrms-goal-updated');
    }

    public function destroy(Goal $goal): RedirectResponse
    {
        $this->service->cancelGoal($goal, request()->user());

        return redirect()->route('hrms.performance.goals.index')
            ->with('status', 'hrms-goal-cancelled');
    }

    public function complete(Goal $goal): RedirectResponse
    {
        $this->authorize('update', $goal);
        $this->service->completeGoal($goal, request()->user());

        return redirect()->route('hrms.performance.goals.show', $goal)
            ->with('status', 'hrms-goal-completed');
    }

    public function progress(UpdateGoalProgressRequest $request, Goal $goal): RedirectResponse
    {
        $this->service->updateProgress($goal, $request->validated(), $request->user());

        return redirect()->route('hrms.performance.goals.show', $goal)
            ->with('status', 'hrms-goal-progress-updated');
    }
}
