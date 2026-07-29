<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCalendarSyncRequest;
use App\Models\Employee;
use App\Models\Project;
use App\Services\CalendarSyncService;
use App\Services\ProjectCalendarService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProjectCalendarController extends Controller
{
    public function __construct(
        protected CalendarSyncService $calendarService,
        protected ProjectCalendarService $planningCalendar,
    ) {}

    public function index(Request $request, TenantContext $tenant): View
    {
        abort_unless(
            $request->user()?->hasAnyPermission(['projects.calendar.view', 'projects.view']),
            403
        );

        $organization = $tenant->get();
        abort_unless($organization, 422);

        $filters = [
            'view' => $request->string('view')->toString() ?: 'month',
            'year' => $request->integer('year') ?: (int) now()->year,
            'month' => $request->integer('month') ?: (int) now()->month,
            'from' => $request->string('from')->toString() ?: null,
            'to' => $request->string('to')->toString() ?: null,
            'project_id' => $request->integer('project_id') ?: null,
            'employee_id' => $request->integer('employee_id') ?: null,
            'user_id' => $request->integer('user_id') ?: null,
            'status' => $request->string('status')->toString() ?: null,
            'priority' => $request->string('priority')->toString() ?: null,
        ];

        $calendar = $this->planningCalendar->build($organization, $filters);

        return view('projects.calendar.index', [
            'calendar' => $calendar,
            'organization' => $organization,
            'projects' => Project::query()
                ->where('is_archived', false)
                ->orderBy('name')
                ->limit(200)
                ->get(['id', 'name']),
            'employees' => Employee::query()
                ->orderBy('first_name')
                ->limit(200)
                ->get(),
            'taskStatuses' => config('tasks.statuses', []),
            'priorities' => config('tasks.priorities', config('projects.priorities', [])),
        ]);
    }

    public function sync(StoreCalendarSyncRequest $request, Project $project): RedirectResponse
    {
        $provider = $request->validated('provider') ?? 'internal';

        try {
            if ($provider === 'google') {
                $this->calendarService->syncToGoogle($project);
            } elseif ($provider === 'outlook') {
                $this->calendarService->syncToOutlook($project);
            } else {
                $this->calendarService->syncProject($project);
            }
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        }

        return redirect()
            ->back()
            ->with('status', 'project-calendar-synced');
    }
}
