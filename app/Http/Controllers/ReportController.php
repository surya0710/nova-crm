<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request, TenantContext $tenant, ReportService $reports): View
    {
        abort_unless($request->user()->hasPermission('reports.view'), 403);

        $organization = $tenant->get();
        abort_unless($organization, 404);

        $period = $request->string('period', '30')->toString();
        $from = match ($period) {
            '90' => Carbon::now()->subDays(90),
            '365' => Carbon::now()->subYear(),
            'all' => null,
            default => Carbon::now()->subDays(30),
        };

        return view('reports.index', [
            'organization' => $organization,
            'data' => $reports->compile($organization, $from),
            'period' => in_array($period, ['30', '90', '365', 'all'], true) ? $period : '30',
        ]);
    }
}
