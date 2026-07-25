<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCalendarSyncRequest;
use App\Http\Resources\ProjectCalendarLinkResource;
use App\Models\Project;
use App\Services\CalendarSyncService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class ProjectCalendarController extends Controller
{
    public function __construct(protected CalendarSyncService $calendarService) {}

    public function index(Request $request, TenantContext $tenant): AnonymousResourceCollection
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
