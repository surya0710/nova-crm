<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\CreateGoalCheckinRequest;
use App\Models\Goal;
use App\Models\GoalCheckin;
use App\Services\Hrms\GoalManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GoalCheckinController extends Controller
{
    public function __construct(protected GoalManagementService $service) {}

    public function index(): View
    {
        $this->authorize('viewAny', Goal::class);

        return view('hrms.performance.checkins.index', [
            'checkins' => GoalCheckin::query()
                ->with(['goal.employee', 'goal.team', 'author'])
                ->latest()
                ->paginate(25),
            'goals' => Goal::query()
                ->whereIn('status', ['assigned', 'in_progress', 'completed'])
                ->orderBy('title')
                ->limit(200)
                ->get(),
        ]);
    }

    public function store(CreateGoalCheckinRequest $request, Goal $goal): RedirectResponse
    {
        $this->service->recordCheckin($goal, $request->validated(), $request->user());

        return redirect()->route('hrms.performance.checkins.index')
            ->with('status', 'hrms-goal-checkin-recorded');
    }
}
