@include('workflows._assets')

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold text-slate-900">{{ __('Execution history') }}</h1>
                <p class="text-sm text-slate-500">{{ $workflow->name }}</p>
            </div>
            <a href="{{ route('workflows.show', $workflow) }}" class="text-sm font-semibold text-indigo-600">Back to workflow</a>
        </div>
    </x-slot>

    <div class="workflow-bootstrap">
        <div class="card workflow-card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-sm-5 col-lg-3">
                        <label for="execution-status" class="form-label small fw-semibold">Status</label>
                        <select id="execution-status" name="status" class="form-select">
                            <option value="">All statuses</option>
                            @foreach (App\Models\WorkflowExecution::STATUSES as $status)
                                <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto"><button class="btn btn-primary">Filter</button></div>
                    <div class="col-auto"><a href="{{ route('workflows.executions.index', $workflow) }}" class="btn btn-outline-secondary">Reset</a></div>
                </form>
            </div>
        </div>

        <div class="card workflow-card overflow-hidden">
            @if ($executions->isEmpty())
                <div class="card-body text-center py-5">
                    <h2 class="h5">No executions found</h2>
                    <p class="text-body-secondary mb-0">Runs appear here when the configured trigger fires.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Execution</th>
                                <th>Status</th>
                                <th>Workflow version</th>
                                <th>Attempt</th>
                                <th>Started</th>
                                <th>Duration</th>
                                <th class="pe-4">Error</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($executions as $execution)
                                <tr>
                                    <td class="ps-4"><a class="fw-semibold text-decoration-none" href="{{ route('workflows.executions.show', [$workflow, $execution]) }}">#{{ $execution->id }}</a></td>
                                    <td><span class="badge text-bg-{{ $execution->status === 'completed' ? 'success' : ($execution->status === 'failed' ? 'danger' : 'secondary') }}">{{ ucfirst($execution->status) }}</span></td>
                                    <td>{{ $execution->workflow_version }}</td>
                                    <td>{{ $execution->attempt }}</td>
                                    <td>{{ ($execution->started_at ?? $execution->queued_at)?->format('M j, Y H:i:s') ?? '—' }}</td>
                                    <td>
                                        @if ($execution->started_at && $execution->finished_at)
                                            {{ $execution->started_at->diffForHumans($execution->finished_at, true) }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="pe-4 text-danger small text-truncate" style="max-width: 20rem">{{ $execution->error_message ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($executions->hasPages())
                    <div class="card-footer bg-white">{{ $executions->links() }}</div>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
