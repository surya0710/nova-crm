<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\ProjectMilestone;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectsMilestonesHubController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless($request->user()->hasPermission('projects.view'), 403);

        $milestones = ProjectMilestone::query()
            ->with(['project.status', 'project'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->boolean('upcoming'), function ($q) {
                $q->whereNotIn('status', ['completed', 'cancelled'])
                    ->whereNotNull('due_date')
                    ->whereDate('due_date', '>=', now()->toDateString());
            })
            ->orderByRaw('due_date is null')
            ->orderBy('due_date')
            ->paginate(20)
            ->withQueryString();

        return view('projects.milestones-hub', [
            'milestones' => $milestones,
            'filters' => [
                'status' => $request->string('status')->toString(),
                'upcoming' => $request->boolean('upcoming'),
            ],
        ]);
    }
}
