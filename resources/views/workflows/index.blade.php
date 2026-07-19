@include('workflows._assets')

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold text-slate-900">{{ __('Workflows') }}</h1>
                <p class="text-sm text-slate-500">{{ __('Automate tenant-safe CRM processes') }}</p>
            </div>
            @can('create', App\Models\Workflow::class)
                <a href="{{ route('workflows.create') }}" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Create workflow</a>
            @endcan
        </div>
    </x-slot>

    <x-flash-messages />

    <div class="workflow-bootstrap">
        <div class="card workflow-card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('workflows.index') }}" class="row g-3 align-items-end">
                    <div class="col-lg-5">
                        <label class="form-label small fw-semibold" for="workflow-search">Search</label>
                        <input id="workflow-search" type="search" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Name or description">
                    </div>
                    <div class="col-sm-5 col-lg-2">
                        <label class="form-label small fw-semibold" for="workflow-status">Status</label>
                        <select id="workflow-status" name="status" class="form-select">
                            <option value="">All statuses</option>
                            @foreach (App\Models\Workflow::STATUSES as $status)
                                <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-7 col-lg-3">
                        <label class="form-label small fw-semibold" for="workflow-trigger-filter">Trigger</label>
                        <select id="workflow-trigger-filter" name="trigger_type" class="form-select">
                            <option value="">All triggers</option>
                            @foreach (config('workflows.triggers') as $value => $definition)
                                <option value="{{ $value }}" @selected(($filters['trigger_type'] ?? '') === $value)>{{ $definition['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 d-flex gap-2">
                        <button class="btn btn-primary flex-grow-1">Filter</button>
                        <a href="{{ route('workflows.index') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card workflow-card overflow-hidden">
            @if ($workflows->isEmpty())
                <div class="card-body text-center py-5">
                    <h2 class="h5">No workflows found</h2>
                    <p class="text-body-secondary mb-0">Create a workflow or adjust the filters.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Workflow</th>
                                <th>Status</th>
                                <th>Trigger</th>
                                <th>Actions</th>
                                <th>Executions</th>
                                <th>Latest result</th>
                                <th class="text-end pe-4">Controls</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($workflows as $workflow)
                                @php $latest = $workflow->executions->first(); @endphp
                                <tr>
                                    <td class="ps-4">
                                        <a href="{{ route('workflows.show', $workflow) }}" class="fw-semibold text-decoration-none">{{ $workflow->name }}</a>
                                        <div class="small text-body-secondary">Version {{ $workflow->version }}</div>
                                    </td>
                                    <td><span class="badge text-bg-{{ $workflow->status === 'active' ? 'success' : ($workflow->status === 'disabled' ? 'secondary' : 'warning') }}">{{ ucfirst($workflow->status) }}</span></td>
                                    <td>
                                        <div>{{ config('workflows.triggers', [])[$workflow->trigger_type]['label'] ?? $workflow->trigger_type }}</div>
                                        <div class="small text-body-secondary">{{ $workflow->trigger_type }}</div>
                                    </td>
                                    <td>{{ $workflow->actions_count }}</td>
                                    <td><a href="{{ route('workflows.executions.index', $workflow) }}">{{ $workflow->executions_count }}</a></td>
                                    <td>
                                        @if ($latest)
                                            <span class="badge text-bg-{{ $latest->status === 'completed' ? 'success' : ($latest->status === 'failed' ? 'danger' : 'secondary') }}">{{ ucfirst($latest->status) }}</span>
                                            <div class="small text-body-secondary">{{ $latest->created_at->diffForHumans() }}</div>
                                        @else
                                            <span class="text-body-secondary">Never run</span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('workflows.show', $workflow) }}" class="btn btn-outline-secondary">View</a>
                                            @can('update', $workflow)
                                                <a href="{{ route('workflows.edit', $workflow) }}" class="btn btn-outline-primary">Edit</a>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($workflows->hasPages())
                    <div class="card-footer bg-white">{{ $workflows->links() }}</div>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
