<?php

namespace App\Http\Controllers;

use App\Models\Workflow;
use App\Models\WorkflowExecution;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkflowExecutionController extends Controller
{
    public function index(Request $request, Workflow $workflow): View
    {
        $this->authorize('view', $workflow);

        $query = $workflow->executions()->latest();

        if (in_array($status = $request->string('status')->toString(), WorkflowExecution::STATUSES, true)) {
            $query->where('status', $status);
        }

        return view('workflows.executions.index', [
            'workflow' => $workflow,
            'executions' => $query->paginate(25)->withQueryString(),
            'filters' => $request->only('status'),
        ]);
    }

    public function show(Workflow $workflow, WorkflowExecution $execution): View
    {
        abort_unless($execution->workflow_id === $workflow->id, 404);
        $this->authorize('view', $execution);

        $execution->load([
            'workflow',
            'logs' => fn ($query) => $query->with(['action', 'condition']),
        ]);

        return view('workflows.executions.show', compact('workflow', 'execution'));
    }
}
