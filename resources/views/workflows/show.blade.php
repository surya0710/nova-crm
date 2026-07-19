@include('workflows._assets')

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold text-slate-900">{{ $workflow->name }}</h1>
                <p class="text-sm text-slate-500">{{ config('workflows.triggers', [])[$workflow->trigger_type]['label'] ?? $workflow->trigger_type }} · Version {{ $workflow->version }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('workflows.executions.index', $workflow) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700">Execution history</a>
                @can('update', $workflow)
                    <a href="{{ route('workflows.edit', $workflow) }}" class="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white">Edit</a>
                @endcan
            </div>
        </div>
    </x-slot>

    <x-flash-messages />

    <div class="workflow-bootstrap">
        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="row g-4">
            <div class="col-xl-8">
                <div class="card workflow-card mb-4">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between gap-3">
                            <div>
                                <span class="badge text-bg-{{ $workflow->status === 'active' ? 'success' : ($workflow->status === 'disabled' ? 'secondary' : 'warning') }}">{{ ucfirst($workflow->status) }}</span>
                                <p class="mt-3 mb-0">{{ $workflow->description ?: 'No description provided.' }}</p>
                            </div>
                            <div class="text-end small text-body-secondary">
                                <div>Concurrency: {{ $workflow->concurrency_limit }}</div>
                                <div>Timeout: {{ $workflow->execution_timeout_seconds }} seconds</div>
                                <div>Created {{ $workflow->created_at->diffForHumans() }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card workflow-card mb-4">
                    <div class="card-header bg-white"><h2 class="h5 mb-0">Conditions</h2></div>
                    <div class="card-body">
                        @if ($workflow->rootConditions->isEmpty())
                            <p class="text-body-secondary mb-0">No conditions; every matching trigger can run.</p>
                        @else
                            @include('workflows._condition_summary', ['conditions' => $workflow->rootConditions])
                        @endif
                    </div>
                </div>

                <div class="card workflow-card">
                    <div class="card-header bg-white"><h2 class="h5 mb-0">Sequential actions</h2></div>
                    <ol class="list-group list-group-numbered list-group-flush">
                        @foreach ($workflow->actions as $action)
                            <li class="list-group-item d-flex justify-content-between align-items-start py-3">
                                <div class="ms-2 me-auto">
                                    <div class="fw-semibold">{{ $action->name ?: config("workflows.actions.{$action->type}.label", $action->type) }}</div>
                                    <div class="small text-body-secondary">{{ $action->type }}</div>
                                    <pre class="small bg-light rounded p-2 mt-2 mb-0">{{ json_encode($action->configuration, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                </div>
                                <span class="badge text-bg-{{ $action->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($action->status) }}</span>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card workflow-card mb-4">
                    <div class="card-header bg-white"><h2 class="h5 mb-0">Lifecycle</h2></div>
                    <div class="card-body d-grid gap-2">
                        @can('manage', $workflow)
                            @if ($workflow->isActive())
                                <form method="POST" action="{{ route('workflows.disable', $workflow) }}">
                                    @csrf
                                    <button class="btn btn-outline-warning w-100">Disable workflow</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('workflows.enable', $workflow) }}">
                                    @csrf
                                    <button class="btn btn-success w-100">Enable workflow</button>
                                </form>
                            @endif
                        @endcan
                        @can('delete', $workflow)
                            <form method="POST" action="{{ route('workflows.destroy', $workflow) }}" onsubmit="return confirm('Delete this workflow? Execution history will remain subject to database retention rules.')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-outline-danger w-100">Delete workflow</button>
                            </form>
                        @endcan
                    </div>
                </div>

                <div class="card workflow-card">
                    <div class="card-header bg-white d-flex justify-content-between">
                        <h2 class="h5 mb-0">Executions</h2>
                        <a href="{{ route('workflows.executions.index', $workflow) }}">View all</a>
                    </div>
                    <div class="card-body">
                        <div class="display-6">{{ $workflow->executions_count }}</div>
                        <div class="row g-2 mt-2">
                            @foreach (App\Models\WorkflowExecution::STATUSES as $status)
                                @if (($executionSummary[$status] ?? 0) > 0)
                                    <div class="col-6">
                                        <div class="border rounded p-2">
                                            <div class="small text-body-secondary">{{ ucfirst($status) }}</div>
                                            <div class="fw-semibold">{{ $executionSummary[$status] }}</div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
