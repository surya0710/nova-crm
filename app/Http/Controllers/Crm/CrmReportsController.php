<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class CrmReportsController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless($request->user()->hasPermission('reports.view'), 403);

        $reports = collect([
            [
                'label' => __('General analytics'),
                'description' => __('Revenue, pipeline, funnel, and performance overview.'),
                'href' => Route::has('reports.index') ? route('reports.index') : null,
            ],
            [
                'label' => __('Finance & receivables'),
                'description' => __('Accounts receivable, aging, collection metrics, and filters.'),
                'href' => Route::has('reports.finance') ? route('reports.finance') : null,
            ],
            [
                'label' => __('Export revenue CSV'),
                'description' => __('Download revenue for the current reporting period.'),
                'href' => Route::has('reports.export.revenue') ? route('reports.export.revenue') : null,
                'permission' => ['reports.manage', 'finance.view'],
            ],
            [
                'label' => __('Export outstanding CSV'),
                'description' => __('Download outstanding invoice balances.'),
                'href' => Route::has('reports.export.outstanding') ? route('reports.export.outstanding') : null,
                'permission' => ['reports.manage', 'finance.view'],
            ],
            [
                'label' => __('CRM email'),
                'description' => __('Queued, sent, delivered, failed, and bounced email for this organization.'),
                'href' => Route::has('crm.email-report') ? route('crm.email-report') : route('crm.communications.index'),
                'permission' => ['crm_email.view', 'customers.view', 'reports.view'],
            ],
        ])->filter(function (array $item) use ($request) {
            if (! $item['href']) {
                return false;
            }
            if (! isset($item['permission'])) {
                return true;
            }

            return $request->user()->hasAnyPermission($item['permission']);
        })->values();

        return view('crm.reports', [
            'reports' => $reports,
        ]);
    }
}
