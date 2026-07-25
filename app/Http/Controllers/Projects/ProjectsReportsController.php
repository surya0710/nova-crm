<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class ProjectsReportsController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless(
            $request->user()->hasAnyPermission(['projects.view', 'reports.view', 'projects.portfolios.view']),
            403
        );

        $reports = collect([
            [
                'label' => __('Executive projects'),
                'description' => __('Organization-wide project health and delivery overview.'),
                'href' => Route::has('projects.executive') ? route('projects.executive') : null,
                'permission' => ['projects.view'],
            ],
            [
                'label' => __('Projects dashboard'),
                'description' => __('Active projects, progress, and delivery KPIs.'),
                'href' => Route::has('projects.dashboard') ? route('projects.dashboard') : null,
                'permission' => ['projects.view'],
            ],
            [
                'label' => __('Portfolio executive'),
                'description' => __('Portfolio health, investment mix, and strategic status.'),
                'href' => Route::has('portfolios.executive') ? route('portfolios.executive') : null,
                'permission' => ['projects.portfolios.view'],
            ],
            [
                'label' => __('Portfolio reports'),
                'description' => __('Generate and download portfolio report packs.'),
                'href' => Route::has('portfolio-reports.index') ? route('portfolio-reports.index') : null,
                'permission' => ['projects.portfolios.view'],
            ],
            [
                'label' => __('Portfolio forecasts'),
                'description' => __('Forecast outlook across portfolios.'),
                'href' => Route::has('portfolios.forecasts.index') ? route('portfolios.forecasts.index') : null,
                'permission' => ['projects.portfolios.view'],
            ],
            [
                'label' => __('Resource capacity'),
                'description' => __('Capacity, utilization, and allocation planning views.'),
                'href' => Route::has('resources.capacity') ? route('resources.capacity') : null,
                'permission' => ['resources.view'],
            ],
            [
                'label' => __('Resource forecast'),
                'description' => __('Forward-looking resource demand and supply.'),
                'href' => Route::has('resources.forecast') ? route('resources.forecast') : null,
                'permission' => ['resources.view'],
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

        return view('projects.reports-hub', [
            'reports' => $reports,
        ]);
    }
}
