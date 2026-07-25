<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Services\Hrms\OrganizationCalendarService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizationCalendarController extends Controller
{
    public function __invoke(Request $request, OrganizationCalendarService $calendarService): View
    {
        abort_unless($request->user()?->hasPermission('organization.calendar'), 403);

        $year = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);

        return view('hrms.calendar.index', [
            'year' => $year,
            'month' => $month,
            'events' => $calendarService->eventsForMonth($year, $month),
        ]);
    }
}
