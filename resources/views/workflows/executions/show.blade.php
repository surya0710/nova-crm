@include('workflows._assets')

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold text-slate-900">Execution #{{ $execution->id }}</h1>
                <p class="text-sm text-slate-500">{{ $workflow->name }} · Version {{ $execution->workflow_version }}</p>
            </div>
            <a href="{{ route('workflows.executions.index', $workflow) }}" class="text-sm font-semibold text-indigo-600">Back to history</a>
        </div>
    </x-slot>

    <div class="workflow-bootstrap">
        <div class="row g-4">
            <div class="col-xl-8">
                <div class="card workflow-card mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h2 class="h5 mb-0">Chronological log</h2>
                        <span class="badge text-bg-{{ $execution->status === 'completed' ? 'success' : ($execution->status === 'failed' ? 'danger' : 'secondary') }}">{{ ucfirst($execution->status) }}</span>
                    </div>
                    <div class="card-body">
                        @forelse ($execution->logs as $log)
                            <div class="d-flex gap-3 pb-4 position-relative">
                                <div class="rounded-circle bg-{{ $log->level === 'error' ? 'danger' : ($log->status === 'completed' ? 'success' : 'secondary') }} flex-shrink-0" style="width:.75rem;height:.75rem;margin-top:.4rem"></div>
                                <div class="flex-grow-1 border-bottom pb-3">
                                    <div class="d-flex flex-wrap justify-content-between gap-2">
                                        <div>
                                            <strong>{{ $log->event }}</strong>
                                            @if ($log->status)<span class="badge text-bg-light ms-1">{{ $log->status }}</span>@endif
                                        </div>
                                        <time class="small text-body-secondary">{{ $log->occurred_at?->format('M j, Y H:i:s.u') }}</time>
                                    </div>
                                    @if ($log->message)<p class="mb-1 mt-2">{{ $log->message }}</p>@endif
                                    @if ($log->action)<div class="small text-body-secondary">Action: {{ $log->action->name ?: config("workflows.actions.{$log->action->type}.label", $log->action->type) }}</div>@endif
                                    @if ($log->condition)<div class="small text-body-secondary">Condition: {{ $log->condition->field ?: strtoupper($log->condition->boolean_operator ?? '') }}</div>@endif
                                    @if ($log->context)
                                        <details class="mt-2">
                                            <summary class="small text-primary">Outcome context</summary>
                                            <pre class="execution-json small bg-light rounded p-3 mt-2 mb-0">{{ json_encode($log->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                        </details>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-body-secondary mb-0">No log entries were recorded.</p>
                        @endforelse
                    </div>
                </div>

                @if ($execution->error_message)
                    <div class="alert alert-danger">
                        <h2 class="h6 alert-heading">Execution error</h2>
                        <div>{{ $execution->error_message }}</div>
                    </div>
                @endif
            </div>

            <div class="col-xl-4">
                <div class="card workflow-card mb-4">
                    <div class="card-header bg-white"><h2 class="h5 mb-0">Run details</h2></div>
                    <dl class="card-body row small mb-0">
                        <dt class="col-6">Queued</dt><dd class="col-6 text-end">{{ $execution->queued_at?->format('M j, H:i:s') ?? '—' }}</dd>
                        <dt class="col-6">Started</dt><dd class="col-6 text-end">{{ $execution->started_at?->format('M j, H:i:s') ?? '—' }}</dd>
                        <dt class="col-6">Finished</dt><dd class="col-6 text-end">{{ $execution->finished_at?->format('M j, H:i:s') ?? '—' }}</dd>
                        <dt class="col-6">Attempt</dt><dd class="col-6 text-end">{{ $execution->attempt }}</dd>
                        <dt class="col-6">Current action</dt><dd class="col-6 text-end">{{ $execution->current_action_position }}</dd>
                        <dt class="col-6">Subject</dt><dd class="col-6 text-end">{{ class_basename($execution->trigger_subject_type ?? '') ?: '—' }} #{{ $execution->trigger_subject_id ?? '—' }}</dd>
                        <dt class="col-6">Idempotency key</dt><dd class="col-6 text-end text-break">{{ $execution->idempotency_key ?? '—' }}</dd>
                    </dl>
                </div>

                @foreach ([
                    'Trigger snapshot' => $execution->trigger_subject_snapshot,
                    'Trigger payload' => $execution->trigger_payload,
                    'Result' => $execution->result,
                ] as $label => $payload)
                    <div class="card workflow-card mb-4">
                        <div class="card-header bg-white"><h2 class="h6 mb-0">{{ $label }}</h2></div>
                        <div class="card-body">
                            <pre class="execution-json small bg-light rounded p-3 mb-0">{{ json_encode($payload ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
