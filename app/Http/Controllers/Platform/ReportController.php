<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\Platform\PlatformReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request, PlatformReportService $reports): View
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.reports.view');

        $from = $request->filled('from') ? Carbon::parse($request->input('from')) : null;
        $to = $request->filled('to') ? Carbon::parse($request->input('to')) : null;

        return view('platform.reports.index', [
            'report' => $reports->compile($from, $to),
            'filters' => $request->only(['from', 'to']),
        ]);
    }

    public function export(Request $request, PlatformReportService $reports): Response
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.reports.view');

        $from = $request->filled('from') ? Carbon::parse($request->input('from')) : null;
        $to = $request->filled('to') ? Carbon::parse($request->input('to')) : null;

        $csv = $reports->toCsv($reports->compile($from, $to));

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="platform-report.csv"',
        ]);
    }
}
