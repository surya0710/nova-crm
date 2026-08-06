<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Services\Analytics\AnalyticsDomainService;
use App\Services\Analytics\AnalyticsInsightsService;
use App\Services\Analytics\KpiLibraryService;
use App\Services\ReportService;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class AnalyticsPagesController extends Controller
{
    public function executive(Request $request, TenantContext $tenant, AnalyticsDomainService $domains): View
    {
        $this->authorizeAnalytics($request);
        $organization = $tenant->get();
        abort_unless($organization, 404);

        return view('analytics.executive', [
            'payload' => $domains->executive($request->user(), $organization),
        ]);
    }

    public function crm(Request $request, TenantContext $tenant, AnalyticsDomainService $domains): View
    {
        $this->authorizeAnalytics($request);
        $organization = $tenant->get();
        abort_unless($organization, 404);

        return view('analytics.crm', [
            'payload' => $domains->crm($request->user(), $organization),
        ]);
    }

    public function projects(Request $request, TenantContext $tenant, AnalyticsDomainService $domains): View
    {
        $this->authorizeAnalytics($request);
        $organization = $tenant->get();
        abort_unless($organization, 404);

        return view('analytics.projects', [
            'payload' => $domains->projects($request->user(), $organization),
        ]);
    }

    public function hr(Request $request, TenantContext $tenant, AnalyticsDomainService $domains): View
    {
        $this->authorizeAnalytics($request);
        $organization = $tenant->get();
        abort_unless($organization, 404);

        return view('analytics.hr', [
            'payload' => $domains->hr($request->user(), $organization),
        ]);
    }

    public function aiInsights(Request $request, TenantContext $tenant, AnalyticsInsightsService $insights): View
    {
        $this->authorizeAnalytics($request);
        $organization = $tenant->get();
        abort_unless($organization, 404);

        return view('analytics.ai-insights', [
            'insights' => $insights->build($request->user(), $organization),
        ]);
    }

    public function dashboards(Request $request): View
    {
        $this->authorizeAnalytics($request);

        $templates = [
            ['key' => 'executive', 'label' => __('Executive Dashboards'), 'href' => Route::has('analytics.executive') ? route('analytics.executive') : null],
            ['key' => 'sales', 'label' => __('Sales Dashboards'), 'href' => Route::has('analytics.crm') ? route('analytics.crm') : null],
            ['key' => 'hr', 'label' => __('HR Dashboards'), 'href' => Route::has('analytics.hr') ? route('analytics.hr') : null],
            ['key' => 'projects', 'label' => __('Project Dashboards'), 'href' => Route::has('analytics.projects') ? route('analytics.projects') : null],
            ['key' => 'marketing', 'label' => __('Marketing Dashboards'), 'href' => Route::has('marketing.home') ? route('marketing.home') : null],
        ];

        $personalHref = Route::has('dashboard') ? route('dashboard') : null;

        return view('analytics.dashboards', [
            'templates' => $templates,
            'personalHref' => $personalHref,
            'canCustomize' => $request->user()->hasAnyPermission(['dashboard.customize', 'dashboard.manage', 'reports.view']),
        ]);
    }

    public function kpis(Request $request, KpiLibraryService $library): View
    {
        $this->authorizeAnalytics($request);

        return view('analytics.kpis', [
            'catalog' => $library->catalog(),
        ]);
    }

    public function reports(Request $request, TenantContext $tenant, ReportService $reports): View
    {
        $this->authorizeAnalytics($request);
        $organization = $tenant->get();
        abort_unless($organization, 404);

        $compiled = $reports->compile($organization, user: $request->user());

        return view('analytics.reports', [
            'compiled' => $compiled,
            'links' => [
                'index' => Route::has('reports.index') ? route('reports.index') : null,
                'finance' => Route::has('reports.finance') ? route('reports.finance') : null,
                'projects' => Route::has('projects.reports.hub') ? route('projects.reports.hub') : null,
                'recruitment' => Route::has('hrms.recruitment.analytics') ? route('hrms.recruitment.analytics') : null,
            ],
        ]);
    }

    protected function authorizeAnalytics(Request $request): void
    {
        abort_unless(
            $request->user()->hasAnyPermission([
                'reports.view', 'finance.view', 'audit.view', 'projects.reports.view', 'recruitment.reports.view',
            ]),
            403
        );
    }
}
