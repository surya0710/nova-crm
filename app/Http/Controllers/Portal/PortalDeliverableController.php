<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\ClientApproval;
use App\Models\Deliverable;
use App\Models\Organization;
use App\Services\ApprovalService;
use App\Services\ClientAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalDeliverableController extends Controller
{
    public function __construct(
        protected ClientAccessService $access,
        protected ApprovalService $approvals,
    ) {}

    public function show(Request $request, Organization $organization, Deliverable $deliverable): View
    {
        abort_unless((int) $deliverable->organization_id === (int) $organization->id, 404);
        $this->access->assertCanAccessProject($request->user('client'), $deliverable->project, 'deliverables');

        $approval = ClientApproval::query()
            ->where('approvable_type', $deliverable->getMorphClass())
            ->where('approvable_id', $deliverable->id)
            ->where('status', 'client_review')
            ->latest()
            ->first();

        return view('portal.deliverables.show', [
            'deliverable' => $deliverable->load('versions'),
            'approval' => $approval,
        ]);
    }

    public function approve(Request $request, Organization $organization, ClientApproval $approval): RedirectResponse
    {
        abort_unless((int) $approval->organization_id === (int) $organization->id, 404);

        $data = $request->validate(['decision_notes' => ['nullable', 'string', 'max:5000']]);
        $this->approvals->approve($approval, $request->user('client'), $data['decision_notes'] ?? null);

        return back()->with('status', __('Deliverable approved.'));
    }

    public function reject(Request $request, Organization $organization, ClientApproval $approval): RedirectResponse
    {
        abort_unless((int) $approval->organization_id === (int) $organization->id, 404);

        $data = $request->validate(['decision_notes' => ['nullable', 'string', 'max:5000']]);
        $this->approvals->reject($approval, $request->user('client'), $data['decision_notes'] ?? null);

        return back()->with('status', __('Deliverable rejected.'));
    }
}
