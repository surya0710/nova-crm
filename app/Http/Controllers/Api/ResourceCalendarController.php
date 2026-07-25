<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreResourceCalendarRequest;
use App\Http\Requests\UpdateResourceCalendarRequest;
use App\Http\Resources\ResourceCalendarResource;
use App\Models\ResourceCalendar;
use App\Services\ResourceCalendarService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ResourceCalendarController extends Controller
{
    public function __construct(protected ResourceCalendarService $calendars) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ResourceCalendar::class);

        $query = ResourceCalendar::query()->with('employee')->latest('effective_from');

        if ($employeeId = $request->integer('employee_id')) {
            $query->where('employee_id', $employeeId);
        }

        return ResourceCalendarResource::collection(
            $query->paginate($request->integer('per_page', 15))
        );
    }

    public function show(ResourceCalendar $resource_calendar): ResourceCalendarResource
    {
        $this->authorize('view', $resource_calendar);

        $resource_calendar->load('employee');

        return new ResourceCalendarResource($resource_calendar);
    }

    public function store(StoreResourceCalendarRequest $request, TenantContext $tenant): JsonResponse
    {
        if (! $tenant->get()) {
            return response()->json(['message' => __('Organization context is required.')], 422);
        }

        $calendar = $this->calendars->create($request->validated(), $request->user());
        $calendar->load('employee');

        return (new ResourceCalendarResource($calendar))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateResourceCalendarRequest $request, ResourceCalendar $resource_calendar): ResourceCalendarResource
    {
        $calendar = $this->calendars->update($resource_calendar, $request->validated(), $request->user());
        $calendar->load('employee');

        return new ResourceCalendarResource($calendar);
    }

    public function destroy(ResourceCalendar $resource_calendar): JsonResponse
    {
        $this->authorize('delete', $resource_calendar);
        $this->calendars->delete($resource_calendar);

        return response()->json(null, 204);
    }
}
