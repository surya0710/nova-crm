<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Models\Goal;
use App\Models\GoalProgressUpdate;
use Illuminate\View\View;

class GoalProgressController extends Controller
{
    public function __invoke(): View
    {
        $this->authorize('viewAny', Goal::class);

        return view('hrms.performance.progress.index', [
            'updates' => GoalProgressUpdate::query()
                ->with(['goal.employee', 'goal.team', 'updater'])
                ->latest()
                ->paginate(25),
            'summary' => [
                'active' => Goal::query()->whereIn('status', ['assigned', 'in_progress'])->count(),
                'completed' => Goal::query()->where('status', 'completed')->count(),
                'avg_achievement' => round((float) Goal::query()
                    ->whereIn('status', ['assigned', 'in_progress', 'completed'])
                    ->avg('achievement_percentage'), 2),
            ],
        ]);
    }
}
