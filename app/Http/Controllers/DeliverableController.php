<?php

namespace App\Http\Controllers;

use App\Models\Deliverable;
use App\Models\Project;
use App\Services\ApprovalService;
use App\Services\DeliverableService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeliverableController extends Controller
{
    public function __construct(
        protected DeliverableService $deliverables,
        protected ApprovalService $approvals,
    ) {}

    public function index(Request $request, Project $project): View
    {
        $this->authorize('manageDeliverables', $project);

        $items = Deliverable::query()
            ->where('project_id', $project->id)
            ->latest()
            ->paginate(20);

        return view('projects.deliverables.index', [
            'project' => $project,
            'deliverables' => $items,
        ]);
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('manageDeliverables', $project);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
            'milestone_id' => ['nullable', 'integer'],
            'task_id' => ['nullable', 'integer'],
        ]);

        $this->deliverables->create($project, $data, $request->user());

        return back()->with('status', __('Deliverable created.'));
    }

    public function show(Request $request, Project $project, Deliverable $deliverable): View
    {
        $this->authorize('manageDeliverables', $project);
        abort_unless((int) $deliverable->project_id === (int) $project->id, 404);

        return view('projects.deliverables.show', [
            'project' => $project,
            'deliverable' => $deliverable->load('versions', 'approvals'),
        ]);
    }

    public function submit(Request $request, Project $project, Deliverable $deliverable): RedirectResponse
    {
        $this->authorize('manageDeliverables', $project);
        abort_unless((int) $deliverable->project_id === (int) $project->id, 404);

        $data = $request->validate([
            'file' => ['nullable', 'file', 'max:20480'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->deliverables->submit($deliverable, $request->user(), $data['file'] ?? null, $data['notes'] ?? null);

        return back()->with('status', __('Deliverable submitted.'));
    }

    public function requestApproval(Request $request, Project $project, Deliverable $deliverable): RedirectResponse
    {
        $this->authorize('manageDeliverables', $project);
        abort_unless((int) $deliverable->project_id === (int) $project->id, 404);

        $data = $request->validate([
            'request_message' => ['nullable', 'string', 'max:5000'],
        ]);

        if ($deliverable->status === 'draft') {
            $this->deliverables->submit($deliverable, $request->user());
            $deliverable->refresh();
        }

        $this->approvals->createForDeliverable($deliverable, $request->user(), $data['request_message'] ?? null);

        return back()->with('status', __('Client approval requested.'));
    }
}
