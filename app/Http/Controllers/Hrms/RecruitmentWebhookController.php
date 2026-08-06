<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\StoreRecruitmentWebhookEndpointRequest;
use App\Models\RecruitmentWebhookDelivery;
use App\Models\RecruitmentWebhookEndpoint;
use App\Services\Recruitment\RecruitmentWebhookService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RecruitmentWebhookController extends Controller
{
    public function __construct(protected RecruitmentWebhookService $webhooks)
    {
    }

    public function index(TenantContext $tenant): View
    {
        $user = request()->user();
        $organization = $tenant->get();
        abort_unless($user && $organization && $user->hasPermission('recruitment.webhook.view', $organization), 403);

        return view('hrms.recruitment.integrations.webhooks', [
            'endpoints' => RecruitmentWebhookEndpoint::query()
                ->where('organization_id', $organization->id)
                ->latest()
                ->get(),
            'deliveries' => RecruitmentWebhookDelivery::query()
                ->where('organization_id', $organization->id)
                ->latest()
                ->paginate(30),
            'events' => $this->webhooks->availableEvents(),
        ]);
    }

    public function store(StoreRecruitmentWebhookEndpointRequest $request, TenantContext $tenant): RedirectResponse
    {
        $user = $request->user();
        $organization = $tenant->get();
        abort_unless($user && $organization && $user->hasPermission('recruitment.integration.manage', $organization), 403);

        $this->webhooks->createEndpoint($organization, $request->validated(), $user);

        return back()->with('status', 'recruitment-webhook-endpoint-created');
    }

    public function retry(RecruitmentWebhookDelivery $delivery, TenantContext $tenant): RedirectResponse
    {
        $user = request()->user();
        $organization = $tenant->get();
        abort_unless($user && $organization && $user->hasPermission('recruitment.integration.manage', $organization), 403);
        abort_unless((int) $delivery->organization_id === (int) $organization->id, 404);

        $this->webhooks->deliver($delivery);

        return back()->with('status', 'recruitment-webhook-retried');
    }
}