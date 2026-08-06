<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCalendarSyncRequest;
use App\Http\Resources\ProjectCalendarLinkResource;
use App\Models\Project;
use App\Services\CalendarSyncService;
use App\Services\ProjectCalendarService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class ProjectCalendarController extends Controller
{
    public function __construct(
        protected CalendarSyncService $calendarService,
        protected ProjectCalendarService $planningCalendar,
    ) {}

    public function index(Request $request, TenantContext $tenant): AnonymousResourceCollection|JsonResponse
    {
        abort_unless(
            $request->user()?->hasAnyPermission(['projects.calendar.view', 'projects.view']),
            403
        );

        $organization = $tenant->get();
        abort_unless($organization, 422);

        if ($request->boolean('planning') || $request->string('source')->toString() === 'planning') {
            $calendar = $this->planningCalendar->build($organization, [
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
            ]);

            return response()->json(['data' => $calendar]);
        }

        $events = $this->calendarService->listCalendarEvents($organization, [
            'project_id' => $request->integer('project_id') ?: null,
            'user_id' => $request->integer('user_id') ?: null,
            'provider' => $request->string('provider')->toString() ?: null,
            'event_type' => $request->string('event_type')->toString() ?: null,
            'from' => $request->string('from')->toString() ?: null,
            'to' => $request->string('to')->toString() ?: null,
        ]);

        return ProjectCalendarLinkResource::collection($events);
    }

    public function sync(StoreCalendarSyncRequest $request, Project $project): AnonymousResourceCollection|JsonResponse
    {
        $provider = $request->validated('provider') ?? 'internal';

        try {
            if ($provider === 'google') {
                $this->calendarService->syncToGoogle($project);
            } elseif ($provider === 'outlook') {
                $this->calendarService->syncToOutlook($project);
            } else {
                $links = $this->calendarService->syncProject($project);

                return ProjectCalendarLinkResource::collection($links);
            }
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json(['success' => true]);
    }
}
