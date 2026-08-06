@php
    $csrf = csrf_token();
    $metrics = $metrics ?? [];
    $columns = $columns ?? [];
    $filters = $filters ?? [];
    $preferences = $preferences ?? [];
    $swimlane = $filters['swimlane'] ?? ($preferences['swimlane'] ?? 'none');
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Task Board')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Task Board'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <a href="{{ route('tasks.backlog') }}" class="inline-flex items-center rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50">{{ __('Backlog') }}</a>
            <a href="{{ route('sprints.index') }}" class="inline-flex items-center rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50">{{ __('Sprints') }}</a>
        </x-slot:actions>

        {{-- Metrics --}}
        <div id="board-metrics" class="mb-4 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 xl:grid-cols-9 gap-3">
            @foreach ([
                'total' => __('Total'),
                'todo' => __('Todo'),
                'in_progress' => __('In Progress'),
                'review' => __('Review'),
                'done' => __('Done'),
                'overdue' => __('Overdue'),
                'average_completion' => __('Avg %'),
                'estimated_hours' => __('Est. Hours'),
                'logged_hours' => __('Logged'),
            ] as $key => $label)
                <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-3">
                    <p class="text-[11px] uppercase tracking-wide text-slate-500">{{ $label }}</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900" data-metric="{{ $key }}">
                        @if (str_contains($key, 'hours'))
                            {{ number_format((float) ($metrics[$key] ?? 0), 1) }}
                        @elseif ($key === 'average_completion')
                            {{ (int) ($metrics[$key] ?? 0) }}%
                        @else
                            {{ (int) ($metrics[$key] ?? 0) }}
                        @endif
                    </p>
                </div>
            @endforeach
        </div>

        {{-- Filters --}}
        <form method="GET" action="{{ route('tasks.board') }}" class="mb-4 rounded-xl bg-white border border-slate-200 shadow-sm p-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-3">
                <select name="project_id" class="border-gray-300 rounded-md shadow-sm text-sm">
                    <option value="">{{ __('All projects') }}</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}" @selected(($filters['project_id'] ?? '') == $project->id)>{{ $project->name }}</option>
                    @endforeach
                </select>
                <select name="sprint_id" class="border-gray-300 rounded-md shadow-sm text-sm">
                    <option value="">{{ __('All sprints') }}</option>
                    @foreach ($sprints as $sprint)
                        <option value="{{ $sprint->id }}" @selected(($filters['sprint_id'] ?? '') == $sprint->id)>{{ $sprint->name }}</option>
                    @endforeach
                </select>
                <select name="assigned_to" class="border-gray-300 rounded-md shadow-sm text-sm">
                    <option value="">{{ __('All assignees') }}</option>
                    @foreach ($assignees as $user)
                        <option value="{{ $user->id }}" @selected(($filters['assigned_to'] ?? '') == $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
                <select name="priority_id" class="border-gray-300 rounded-md shadow-sm text-sm">
                    <option value="">{{ __('All priorities') }}</option>
                    @foreach ($priorities as $priority)
                        <option value="{{ $priority->id }}" @selected(($filters['priority_id'] ?? '') == $priority->id)>{{ $priority->name }}</option>
                    @endforeach
                </select>
                <select name="swimlane" class="border-gray-300 rounded-md shadow-sm text-sm">
                    @foreach ($swimlaneOptions as $key => $label)
                        <option value="{{ $key }}" @selected($swimlane === $key)>{{ __('Swimlane') }}: {{ $label }}</option>
                    @endforeach
                </select>
                <div class="flex items-center gap-2">
                    <label class="inline-flex items-center gap-1.5 text-sm text-slate-600">
                        <input type="checkbox" name="overdue_only" value="1" @checked(! empty($filters['overdue_only'])) class="rounded border-gray-300">
                        {{ __('Overdue only') }}
                    </label>
                    <x-primary-button type="submit" class="ml-auto">{{ __('Apply') }}</x-primary-button>
                </div>
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <input type="text" id="save-view-name" placeholder="{{ __('View name…') }}" class="border-gray-300 rounded-md shadow-sm text-sm">
                <button type="button" id="save-board-view" class="text-sm text-primary-600 hover:text-primary-700">{{ __('Save view') }}</button>
                @foreach (($preferences['saved_views'] ?? []) as $view)
                    <a href="{{ route('tasks.board', array_merge($view['filters'] ?? [], ['swimlane' => $view['swimlane'] ?? 'none'])) }}"
                       class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700 hover:bg-indigo-50 hover:text-indigo-700">
                        {{ $view['name'] }}
                    </a>
                @endforeach
            </div>
        </form>

        {{-- Board --}}
        <div id="task-board" class="flex gap-4 overflow-x-auto pb-4" data-csrf="{{ $csrf }}">
            @foreach ($columns as $columnKey => $column)
                <div class="min-w-[280px] w-72 shrink-0 rounded-xl border {{ ! empty($column['wip_exceeded']) ? 'border-amber-400 ring-2 ring-amber-200' : 'border-slate-200' }} bg-slate-50 board-column"
                     data-column="{{ $columnKey }}"
                     data-status-id="{{ $column['primary_status_id'] }}"
                     data-wip-limit="{{ $column['wip_limit'] ?? '' }}">
                    <div class="px-3 py-2 border-b border-slate-200 flex items-center gap-2">
                        <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $column['color'] ?? '#94a3b8' }}"></span>
                        <h2 class="text-sm font-semibold text-slate-800">{{ $column['label'] }}</h2>
                        <span class="ml-auto text-xs text-slate-500 column-count">{{ $column['count'] }}</span>
                        @if ($column['wip_limit'] !== null)
                            <span class="text-[10px] text-slate-400">WIP {{ $column['wip_limit'] }}</span>
                        @endif
                    </div>
                    <div class="p-2 space-y-2 max-h-[70vh] overflow-y-auto board-drop-zone min-h-[120px]">
                        @foreach ($column['tasks'] as $card)
                            @include('tasks.partials.board-card', ['card' => $card, 'statuses' => $statuses, 'assignees' => $assignees, 'priorities' => $priorities])
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </x-layouts.entity-listing>

    <script>
    (function () {
        const board = document.getElementById('task-board');
        if (!board) return;
        const csrf = board.dataset.csrf;

        function updateMetrics(metrics) {
            if (!metrics) return;
            Object.keys(metrics).forEach((key) => {
                const el = document.querySelector('[data-metric="' + key + '"]');
                if (!el) return;
                const val = metrics[key];
                if (key.indexOf('hours') !== -1) el.textContent = Number(val).toFixed(1);
                else if (key === 'average_completion') el.textContent = String(val) + '%';
                else if (typeof val !== 'object') el.textContent = String(val);
            });
        }

        function refreshColumnCounts() {
            board.querySelectorAll('.board-column').forEach((col) => {
                const count = col.querySelectorAll('.board-card').length;
                const badge = col.querySelector('.column-count');
                if (badge) badge.textContent = String(count);
                const limit = col.dataset.wipLimit ? parseInt(col.dataset.wipLimit, 10) : null;
                if (limit !== null && !Number.isNaN(limit) && count > limit) {
                    col.classList.add('border-amber-400', 'ring-2', 'ring-amber-200');
                } else {
                    col.classList.remove('border-amber-400', 'ring-2', 'ring-amber-200');
                }
            });
        }

        async function postJson(url, body) {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(body),
            });
            if (!res.ok) {
                const data = await res.json().catch(() => ({}));
                throw new Error(data.message || Objecterrors?.status?.[0] || 'Request failed');
            }
            return res.json();
        }

        board.querySelectorAll('.board-card').forEach((card) => {
            card.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData('text/task-id', card.dataset.taskId);
                e.dataTransfer.effectAllowed = 'move';
                card.classList.add('opacity-50');
            });
            card.addEventListener('dragend', () => card.classList.remove('opacity-50'));
        });

        board.querySelectorAll('.board-drop-zone').forEach((zone) => {
            zone.addEventListener('dragover', (e) => {
                e.preventDefault();
                zone.classList.add('ring-2', 'ring-indigo-300');
            });
            zone.addEventListener('dragleave', () => zone.classList.remove('ring-2', 'ring-indigo-300'));
            zone.addEventListener('drop', async (e) => {
                e.preventDefault();
                zone.classList.remove('ring-2', 'ring-indigo-300');
                const taskId = e.dataTransfer.getData('text/task-id');
                const card = board.querySelector('.board-card[data-task-id="' + taskId + '"]');
                const column = zone.closest('.board-column');
                if (!card || !column) return;

                const after = e.target.closest('.board-card');
                if (after && after !== card) zone.insertBefore(card, after);
                else zone.appendChild(card);

                try {
                    const payload = {
                        column: column.dataset.column,
                        status_id: parseInt(column.dataset.statusId, 10) || null,
                        sort_order: Array.from(zone.querySelectorAll('.board-card')).indexOf(card) * 10,
                    };
                    const data = await postJson(@json(url('/tasks')) + '/' + taskId + '/board/move', payload);
                    if (data.data?.metrics) updateMetrics(data.data.metrics);
                    refreshColumnCounts();
                } catch (err) {
                    alert(err.message || 'Move failed');
                    window.location.reload();
                }
            });
        });

        document.getElementById('save-board-view')?.addEventListener('click', async () => {
            const name = document.getElementById('save-view-name')?.value?.trim();
            if (!name) return;
            const params = new URLSearchParams(window.location.search);
            const filters = Object.fromEntries(params.entries());
            try {
                await postJson(@json(route('tasks.board.preferences')), {
                    save_view: { name, filters, swimlane: filters.swimlane || 'none' },
                    swimlane: filters.swimlane || 'none',
                    filters,
                });
                window.location.reload();
            } catch (err) {
                alert(err.message || 'Could not save view');
            }
        });

        board.addEventListener('click', async (e) => {
            const btn = e.target.closest('[data-quick-action]');
            if (!btn) return;
            e.preventDefault();
            e.stopPropagation();
            const card = btn.closest('.board-card');
            const taskId = card?.dataset.taskId;
            if (!taskId) return;
            const action = btn.dataset.quickAction;
            const panel = card.querySelector('[data-quick-panel="' + action + '"]');
            if (panel) {
                panel.classList.toggle('hidden');
                return;
            }
            if (action === 'open') {
                window.location.href = card.dataset.url;
            }
        });

        board.addEventListener('submit', async (e) => {
            const form = e.target.closest('.quick-action-form');
            if (!form) return;
            e.preventDefault();
            const card = form.closest('.board-card');
            const taskId = card?.dataset.taskId;
            if (!taskId) return;
            const fd = new FormData(form);
            const body = Object.fromEntries(fd.entries());
            body.action = form.dataset.action;
            try {
                const data = await postJson(@json(url('/tasks')) + '/' + taskId + '/board/quick-action', body);
                if (data.data) {
                    // Soft refresh card fields
                    const title = card.querySelector('[data-field="title"]');
                    if (title) title.textContent = data.data.title;
                    const progress = card.querySelector('[data-field="progress"]');
                    if (progress) progress.textContent = (data.data.completion_percentage || 0) + '%';
                    form.classList.add('hidden');
                }
            } catch (err) {
                alert(err.message || 'Action failed');
            }
        });
    })();
    </script>
</x-app-layout>
