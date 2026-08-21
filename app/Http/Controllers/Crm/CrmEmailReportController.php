<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Services\CrmEmailMetricsService;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CrmEmailReportController extends Controller
{
    public function __invoke(Request $request, CrmEmailMetricsService $metrics, TenantContext $tenant): View
    {
        abort_unless(
            $request->user()->hasPermission('crm_email.view')
            || $request->user()->hasPermission('customers.view')
            || $request->user()->hasPermission('reports.view'),
            403
        );

        $from = $request->date('from')?->toDateString() ?? now()->subDays(30)->toDateString();
        $to = $request->date('to')?->toDateString() ?? now()->toDateString();

        return view('crm.email-report', [
            'metrics' => $metrics->summary($tenant->get(), $from, $to),
            'from' => $from,
            'to' => $to,
        ]);
    }
}
