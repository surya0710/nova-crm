<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use App\Models\ClientApproval;
use App\Models\Deliverable;
use App\Models\Organization;
use App\Models\Project;
use App\Services\ApprovalService;
use App\Services\ClientPortalFacadeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortalApiController extends Controller
{
    public function __construct(
        protected ClientPortalFacadeService $facade,
        protected ApprovalService $approvals,
    ) {}

    public function dashboard(Request $request, Organization $organization): JsonResponse
    {
        return response()->json($this->facade->dashboard($request->user('client')));
    }

    public function project(Request $request, Organization $organization, Project $project): JsonResponse
    {
        abort_unless((int) $project->organization_id === (int) $organization->id, 404);

        return response()->json($this->facade->project($request->user('client'), $project));
    }

    public function deliverables(Request $request, Organization $organization, Project $project): JsonResponse
    {
        $payload = $this->facade->project($request->user('client'), $project);

        return response()->json([
            'deliverables' => $payload['deliverables'] ?? [],
            'approvals' => $payload['approvals'] ?? [],
        ]);
    }

    public function approve(Request $request, Organization $organization, ClientApproval $approval): JsonResponse
    {
        abort_unless((int) $approval->organization_id === (int) $organization->id, 404);
        $data = $request->validate(['decision_notes' => ['nullable', 'string', 'max:5000']]);
        $result = $this->approvals->approve($approval, $request->user('client'), $data['decision_notes'] ?? null);

        return response()->json(['approval' => $result]);
    }

    public function reject(Request $request, Organization $organization, ClientApproval $approval): JsonResponse
    {
        abort_unless((int) $approval->organization_id === (int) $organization->id, 404);
        $data = $request->validate(['decision_notes' => ['nullable', 'string', 'max:5000']]);
        $result = $this->approvals->reject($approval, $request->user('client'), $data['decision_notes'] ?? null);

        return response()->json(['approval' => $result]);
    }
}
