<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreResourceCalendarRequest;
use App\Http\Requests\UpdateResourceCalendarRequest;
use App\Models\Employee;
use App\Models\ResourceCalendar;
use App\Services\ResourceCalendarService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResourceCalendarController extends Controller
{
    public function __construct(protected ResourceCalendarService $calendars)
    {
        $this->authorizeResource(ResourceCalendar::class, 'calendar');
    }

    public function index(Request $request, TenantContext $tenant): View
    {
        $query = ResourceCalendar::query()
            ->with('employee')
            ->latest('effective_from');

        if ($employeeId = $request->integer('employee_id')) {
            $query->where('employee_id', $employeeId);
        }

        return view('resources.calendars.index', [
            'calendars' => $query->paginate(20)->withQueryString(),
            'employees' => Employee::query()->orderBy('first_name')->limit(200)->get(),
            'filters' => $request->only(['employee_id']),
        ]);
    }

    public function create(TenantContext $tenant): View
    {
        return view('resources.calendars.create', [
            'calendar' => new ResourceCalendar([
                'working_hours_per_day' => config('resources.default_working_hours_per_day', 8),
                'working_days' => config('resources.default_working_days', []),
                'effective_from' => now()->toDateString(),
            ]),
            'employees' => Employee::query()->orderBy('first_name')->limit(200)->get(),
            'weekdays' => [
                'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday',
            ],
        ]);
    }

    public function store(StoreResourceCalendarRequest $request): RedirectResponse
    {
        $calendar = $this->calendars->create($request->validated(), $request->user());

        return redirect()
            ->route('resources.calendars.index')
            ->with('success', __('Resource calendar created.'));
    }

    public function edit(ResourceCalendar $calendar): View
    {
        return view('resources.calendars.edit', [
            'calendar' => $calendar,
            'employees' => Employee::query()->orderBy('first_name')->limit(200)->get(),
            'weekdays' => [
                'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday',
            ],
        ]);
    }

    public function update(UpdateResourceCalendarRequest $request, ResourceCalendar $calendar): RedirectResponse
    {
        $this->calendars->update($calendar, $request->validated(), $request->user());

        return redirect()
            ->route('resources.calendars.index')
            ->with('success', __('Resource calendar updated.'));
    }

    public function destroy(ResourceCalendar $calendar): RedirectResponse
    {
        $this->calendars->delete($calendar);

        return redirect()
            ->route('resources.calendars.index')
            ->with('success', __('Resource calendar deleted.'));
    }
}
