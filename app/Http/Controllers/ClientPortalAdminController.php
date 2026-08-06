<?php

namespace App\Http\Controllers;

use App\Models\ClientUser;
use App\Models\Customer;
use App\Models\Project;
use App\Services\ClientAccessService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientPortalAdminController extends Controller
{
    public function __construct(protected ClientAccessService $access) {}

    public function index(Request $request, Project $project): View
    {
        $this->authorize('managePortal', $project);

        $clients = ClientUser::query()
            ->where('organization_id', $project->organization_id)
            ->where('customer_id', $project->client_id)
            ->with(['projectAccess' => fn ($q) => $q->where('project_id', $project->id)])
            ->orderBy('name')
            ->get();

        $customers = Customer::query()
            ->where('organization_id', $project->organization_id)
            ->orderBy('name')
            ->limit(100)
            ->get();

        return view('projects.portal.clients', [
            'project' => $project,
            'clients' => $clients,
            'customers' => $customers,
        ]);
    }

    public function invite(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('managePortal', $project);

        $data = $request->validate([
            'customer_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['nullable', 'string', 'min:8'],
            'scopes' => ['nullable', 'array'],
            'scopes.*' => ['string'],
        ]);

        app(TenantContext::class)->set($project->organization);

        $customer = Customer::query()
            ->where('organization_id', $project->organization_id)
            ->whereKey($data['customer_id'])
            ->firstOrFail();

        $client = $this->access->invite($project->organization, $customer, $data, $request->user());
        $this->access->grantProjectAccess($client, $project, $data['scopes'] ?? null, $request->user());

        return back()->with('status', __('Client invited and granted project access.'));
    }

    public function grant(Request $request, Project $project, ClientUser $client): RedirectResponse
    {
        $this->authorize('managePortal', $project);
        abort_unless((int) $client->organization_id === (int) $project->organization_id, 404);

        $data = $request->validate([
            'scopes' => ['nullable', 'array'],
            'scopes.*' => ['string'],
        ]);

        $this->access->grantProjectAccess($client, $project, $data['scopes'] ?? null, $request->user());

        return back()->with('status', __('Access granted.'));
    }

    public function revoke(Request $request, Project $project, ClientUser $client): RedirectResponse
    {
        $this->authorize('managePortal', $project);
        abort_unless((int) $client->organization_id === (int) $project->organization_id, 404);

        $this->access->revokeProjectAccess($client, $project, $request->user());

        return back()->with('status', __('Access revoked.'));
    }
}
