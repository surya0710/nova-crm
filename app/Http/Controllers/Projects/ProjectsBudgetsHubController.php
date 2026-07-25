<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectsBudgetsHubController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless($request->user()->hasPermission('projects.view'), 403);

        $projects = Project::query()
            ->with(['status', 'owner'])
            ->where('is_archived', false)
            ->where(function ($q) {
                $q->whereNotNull('estimated_budget')
                    ->orWhereNotNull('actual_budget');
            })
            ->orderByDesc('estimated_budget')
            ->paginate(20);

        $totals = Project::query()
            ->where('is_archived', false)
            ->selectRaw('COALESCE(SUM(estimated_budget), 0) as estimated, COALESCE(SUM(actual_budget), 0) as actual')
            ->first();

        return view('projects.budgets-hub', [
            'projects' => $projects,
            'totals' => [
                'estimated' => (float) ($totals->estimated ?? 0),
                'actual' => (float) ($totals->actual ?? 0),
                'variance' => (float) ($totals->estimated ?? 0) - (float) ($totals->actual ?? 0),
            ],
        ]);
    }
}
