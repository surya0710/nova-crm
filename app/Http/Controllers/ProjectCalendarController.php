<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCalendarSyncRequest;
use App\Models\Project;
use App\Services\CalendarSyncService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProjectCalendarController extends Controller
{
    public function __construct(protected CalendarSyncService $calendarService) {}

    public function index(Request $request, TenantContext $tenant): View
    {
        abort_unless($request->user()?->hasPermission('projects.calendar.view'), 403);

        $organization = $tenant->get();
        abort_unless($organization, 422);

        $events = $this->calendarService->listCalendarEvents($organization, [
            'project_id' => $request->integer('project_id') ?: null,
            'user_id' => $request->integer('user_id') ?: null,
            'provider' => $request->string('provider')->toString() ?: null,
            'event_type' => $request->string('event_type')->toString() ?: null,
            'from' => $request->string('from')->toString() ?: null,
            'to' => $request->string('to')->toString() ?: null,
        ]);

        return view('projects.calendar.index', [
            'events' => $events,
            'organization' => $organization,
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
