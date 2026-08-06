<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class CrmExportsController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless(
            $request->user()->hasAnyPermission(['reports.view', 'invoices.view', 'customers.view']),
            403
        );

        $exports = collect([
            [
                'label' => __('Outstanding receivables'),
                'description' => __('Export outstanding invoice balances.'),
                'href' => Route::has('reports.export.outstanding') ? route('reports.export.outstanding') : null,
                'permission' => 'reports.view',
            ],
            [
                'label' => __('Revenue export'),
                'description' => __('Export revenue for the current reporting period.'),
                'href' => Route::has('reports.export.revenue') ? route('reports.export.revenue') : null,
                'permission' => 'reports.view',
            ],
            [
                'label' => __('Finance report'),
                'description' => __('Open finance analytics and export options.'),
                'href' => Route::has('reports.finance') ? route('reports.finance') : null,
                'permission' => 'reports.view',
            ],
        ])->filter(fn ($item) => $item['href'] && $request->user()->hasPermission($item['permission']))
            ->values();

        return view('crm.exports', [
            'exports' => $exports,
        ]);
    }
}
