<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkflowRequest;
use App\Http\Requests\UpdateWorkflowRequest;
use App\Models\Organization;
use App\Models\Workflow;
use App\Services\TenantContext;
use App\Services\WorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkflowController extends Controller
{
    public function __construct(protected WorkflowService $workflows)
    {
        $this->authorizeResource(Workflow::class, 'workflow');
    }

    public function index(Request $request): View
    {
        $query = Workflow::query()
            ->withCount(['actions', 'executions'])
            ->with(['executions' => fn ($query) => $query->latest()->limit(1)])
            ->latest();

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (in_array($status = $request->string('status')->toString(), Workflow::STATUSES, true)) {
            $query->where('status', $status);
        }

        if (array_key_exists($trigger = $request->string('trigger_type')->toString(), config('workflows.triggers', []))) {
            $query->where('trigger_type', $trigger);
        }

        return view('workflows.index', [
            'workflows' => $query->paginate(20)->withQueryString(),
            'filters' => $request->only(['search', 'status', 'trigger_type']),
        ]);
    }

    public function create(TenantContext $tenant): View
    {
        return view('workflows.create', [
            'workflow' => new Workflow([
                'trigger_config' => [],
                'concurrency_limit' => 1,
                'execution_timeout_seconds' => 300,
            ]),
            ...$this->formOptions($tenant),
        ]);
    }

    public function store(StoreWorkflowRequest $request, TenantContext $tenant): RedirectResponse
    {
        $workflow = $this->workflows->create(
            $this->organization($tenant),
            $request->validated(),
            $request->user(),
        );

        return redirect()->route('workflows.show', $workflow)->with('status', 'workflow-created');
    }

    public function show(Workflow $workflow): View
    {
        $workflow->load([
            'rootConditions.childrenRecursive',
            'actions',
            'creator',
            'updater',
        ])->loadCount('executions');

        $executionSummary = $workflow->executions()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return view('workflows.show', compact('workflow', 'executionSummary'));
    }

    public function edit(Workflow $workflow, TenantContext $tenant): View
    {
        $workflow->load(['rootConditions.childrenRecursive', 'actions']);

        return view('workflows.edit', [
            'workflow' => $workflow,
            ...$this->formOptions($tenant),
        ]);
    }

    public function update(UpdateWorkflowRequest $request, Workflow $workflow): RedirectResponse
    {
        $workflow = $this->workflows->update($workflow, $request->validated(), $request->user());

        return redirect()->route('workflows.show', $workflow)->with('status', 'workflow-updated');
    }

    public function destroy(Request $request, Workflow $workflow): RedirectResponse
    {
        $this->workflows->delete($workflow, $request->user());

        return redirect()->route('workflows.index')->with('status', 'workflow-deleted');
    }

    public function enable(Request $request, Workflow $workflow): RedirectResponse
    {
        $this->authorize('manage', $workflow);
        $this->workflows->enable($workflow, $request->user());

        return back()->with('status', 'workflow-enabled');
    }

    public function disable(Request $request, Workflow $workflow): RedirectResponse
    {
        $this->authorize('manage', $workflow);
        $this->workflows->disable($workflow, $request->user());

        return back()->with('status', 'workflow-disabled');
    }

    private function formOptions(TenantContext $tenant): array
    {
        $organization = $this->organization($tenant);
        $actions = collect(config('workflows.actions', []))
            ->map(fn (array $definition) => collect($definition)->except('handler')->all())
            ->all();

        return [
            'members' => $organization->users()->orderBy('name')->get(['users.id', 'users.name']),
            'catalog' => [
                'triggers' => config('workflows.triggers', []),
                'operators' => config('workflows.operator_definitions', []),
                'actions' => $actions,
                'maxDepth' => (int) config('workflows.max_depth', 10),
            ],
        ];
    }

    private function organization(TenantContext $tenant): Organization
    {
        $organization = $tenant->get();
        abort_if($organization === null, 404);

        return $organization;
    }
}
