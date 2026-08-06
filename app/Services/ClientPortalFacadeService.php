<?php

namespace App\Services;

use App\Models\ClientApproval;
use App\Models\ClientDiscussion;
use App\Models\ClientNotification;
use App\Models\ClientUploadRequest;
use App\Models\ClientUser;
use App\Models\Deliverable;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\ProjectMilestone;
use Illuminate\Support\Collection;

/**
 * Orchestrates client portal payloads. No calculations, persistence, or ACL logic.
 */
class ClientPortalFacadeService
{
    public function __construct(
        protected ClientAccessService $access,
        protected DeliverableService $deliverables,
        protected ApprovalService $approvals,
        protected DiscussionService $discussions,
        protected ProjectSharingService $sharing,
        protected PortalNotificationService $notifications,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function dashboard(ClientUser $client): array
    {
        $projects = $this->access->accessibleProjects($client);
        $projectIds = $projects->pluck('id');

        $pendingApprovals = ClientApproval::query()
            ->where('organization_id', $client->organization_id)
            ->whereIn('project_id', $projectIds)
            ->where('status', 'client_review')
            ->with('approvable')
            ->latest()
            ->limit(10)
            ->get();

        $recentDeliverables = Deliverable::query()
            ->where('organization_id', $client->organization_id)
            ->whereIn('project_id', $projectIds)
            ->latest()
            ->limit(10)
            ->get();

        $upcomingMilestones = ProjectMilestone::query()
            ->where('organization_id', $client->organization_id)
            ->whereIn('project_id', $projectIds)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '>=', now()->toDateString())
            ->orderBy('due_date')
            ->limit(10)
            ->get();

        $uploadRequests = ClientUploadRequest::query()
            ->where('organization_id', $client->organization_id)
            ->whereIn('project_id', $projectIds)
            ->where('status', 'open')
            ->latest()
            ->limit(10)
            ->get();

        $invoices = $this->sharedInvoices($client, $projects)->take(10)->values();

        $notifications = ClientNotification::query()
            ->where('client_user_id', $client->id)
            ->latest()
            ->limit(10)
            ->get();

        return [
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'email' => $client->email,
                'customer_id' => $client->customer_id,
            ],
            'projects' => $projects->map(fn (Project $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'status' => $p->status,
                'completion_percentage' => $p->completion_percentage ?? null,
            ])->values()->all(),
            'pending_approvals' => $pendingApprovals,
            'recent_deliverables' => $recentDeliverables,
            'upcoming_milestones' => $upcomingMilestones,
            'open_upload_requests' => $uploadRequests,
            'invoices' => $invoices,
            'notifications' => $notifications,
            'widgets' => [
                'active_projects' => $projects->count(),
                'pending_approvals' => $pendingApprovals->count(),
                'recent_deliverables' => $recentDeliverables->count(),
                'upcoming_milestones' => $upcomingMilestones->count(),
                'open_upload_requests' => $uploadRequests->count(),
                'invoice_count' => $invoices->count(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function project(ClientUser $client, Project $project): array
    {
        $access = $this->access->assertCanAccessProject($client, $project);

        $payload = [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $access->allows('project_summary') ? $project->description : null,
                'status' => $project->status,
                'start_date' => $project->start_date?->toDateString(),
                'planned_end_date' => $project->planned_end_date?->toDateString(),
                'completion_percentage' => $project->completion_percentage ?? null,
            ],
            'scopes' => $access->scopes ?? config('portal.default_share_scopes'),
        ];

        if ($access->allows('milestones')) {
            $payload['milestones'] = ProjectMilestone::query()
                ->where('project_id', $project->id)
                ->orderBy('sequence')
                ->get();
        }

        if ($access->allows('deliverables')) {
            $payload['deliverables'] = Deliverable::query()
                ->where('project_id', $project->id)
                ->with('versions')
                ->latest()
                ->get();
            $payload['approvals'] = ClientApproval::query()
                ->where('project_id', $project->id)
                ->where('status', 'client_review')
                ->with('approvable')
                ->latest()
                ->get();
        }

        if ($access->allows('discussions')) {
            $payload['discussions'] = ClientDiscussion::query()
                ->where('project_id', $project->id)
                ->whereNull('parent_id')
                ->where('visibility', 'client')
                ->with(['replies', 'authorUser', 'authorClient'])
                ->latest()
                ->limit(50)
                ->get();
        }

        if ($access->allows('documents')) {
            $payload['upload_requests'] = ClientUploadRequest::query()
                ->where('project_id', $project->id)
                ->latest()
                ->get();
        }

        if ($access->allows('invoices')) {
            $payload['invoices'] = $this->sharedInvoices($client, collect([$project]))->values();
        }

        return $payload;
    }

    /**
     * @param  Collection<int, Project>  $projects
     * @return Collection<int, Invoice>
     */
    protected function sharedInvoices(ClientUser $client, Collection $projects): Collection
    {
        $invoiceProjectIds = $client->projectAccess()
            ->get()
            ->filter(fn ($access) => $access->allows('invoices'))
            ->pluck('project_id');

        if ($invoiceProjectIds->isEmpty()) {
            return collect();
        }

        // CRM invoices are customer-scoped; expose by customer when invoices scope granted.
        return Invoice::query()
            ->where('organization_id', $client->organization_id)
            ->where('customer_id', $client->customer_id)
            ->latest()
            ->limit(50)
            ->get();
    }
}
