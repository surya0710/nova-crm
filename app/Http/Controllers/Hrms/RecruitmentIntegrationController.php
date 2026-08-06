<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Models\RecruitmentProvider;
use App\Services\Recruitment\RecruitmentIntegrationService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RecruitmentIntegrationController extends Controller
{
    public function __construct(protected RecruitmentIntegrationService $integrations)
    {
    }

    public function index(TenantContext $tenant): View
    {
        $user = request()->user();
        $organization = $tenant->get();
        abort_unless($user && $organization && $user->hasPermission('recruitment.integration.view', $organization), 403);

        return view('hrms.recruitment.integrations.index', [
            'diagnostics' => $this->integrations->diagnostics($organization),
            'cards' => $this->integrations->providers()->integrationCardsForOrganization($organization),
        ]);
    }

    public function connect(string $provider, TenantContext $tenant): RedirectResponse
    {
        $user = request()->user();
        $organization = $tenant->get();
        abort_unless($user && $organization && $user->hasPermission('recruitment.integration.manage', $organization), 403);

        $this->integrations->connectProvider($organization, $provider, $user);

        return back()->with('status', 'recruitment-integration-connected');
    }

    public function disconnect(RecruitmentProvider $recruitment_provider, TenantContext $tenant): RedirectResponse
    {
        $user = request()->user();
        $organization = $tenant->get();
        abort_unless($user && $organization && $user->hasPermission('recruitment.integration.manage', $organization), 403);
        abort_unless((int) $recruitment_provider->organization_id === (int) $organization->id, 404);

        $this->integrations->providers()->disconnect($recruitment_provider, $user);

        return back()->with('status', 'recruitment-integration-disconnected');
    }

    public function healthCheck(RecruitmentProvider $recruitment_provider, TenantContext $tenant): RedirectResponse
    {
        $user = request()->user();
        $organization = $tenant->get();
        abort_unless($user && $organization && $user->hasPermission('recruitment.integration.manage', $organization), 403);
        abort_unless((int) $recruitment_provider->organization_id === (int) $organization->id, 404);

        $result = $this->integrations->providers()->checkHealth($recruitment_provider);

        return back()->with('status', ($result['healthy'] ?? false) ? 'recruitment-integration-healthy' : 'recruitment-integration-unhealthy');
    }

    public function processRetries(TenantContext $tenant): RedirectResponse
    {
        $user = request()->user();
        $organization = $tenant->get();
        abort_unless($user && $organization && $user->hasPermission('recruitment.integration.manage', $organization), 403);

        $this->integrations->processRetries($organization);

        return back()->with('status', 'recruitment-integration-retries');
    }
}