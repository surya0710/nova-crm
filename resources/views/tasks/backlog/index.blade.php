<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Backlog')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Task Board'), 'href' => route('tasks.board')],
                ['label' => __('Backlog'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <a href="{{ route('tasks.board') }}" class="inline-flex items-center rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50">{{ __('Board') }}</a>
            <a href="{{ route('sprints.index') }}" class="inline-flex items-center rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50">{{ __('Sprints') }}</a>
        </x-slot:actions>

        <form method="GET" action="{{ route('tasks.backlog') }}" class="mb-4 rounded-xl bg-white border border-slate-200 p-4 grid grid-cols-1 sm:grid-cols-4 gap-3">
            <select name="project_id" class="border-gray-300 rounded-md text-sm">
                <option value="">{{ __('All projects') }}</option>
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}" @selected(($filters['project_id'] ?? '') == $project->id)>{{ $project->name }}</option>
                @endforeach
            </select>
            <select name="sprint_id" class="border-gray-300 rounded-md text-sm">
                <option value="">{{ __('Any sprint') }}</option>
                <option value="none" @selected(($filters['sprint_id'] ?? '') === 'none')>{{ __('No sprint (backlog)') }}</option>
                @foreach ($sprints as $sprint)
                    <option value="{{ $sprint->id }}" @selected(($filters['sprint_id'] ?? '') == $sprint->id)>{{ $sprint->name }}</option>
                @endforeach
            </select>
            <x-primary-button type="submit">{{ __('Filter') }}</x-primary-button>
        </form>

        <form method="POST" action="{{ route('tasks.backlog.bulk') }}" class="mb-4 rounded-xl bg-white border border-slate-200 p-4 flex flex-wrap gap-3 items-end">
            @csrf
            <div>
                <label class="block text-xs text-slate-500 mb-1">{{ __('Bulk action') }}</label>
                <select name="action" class="border-gray-300 rounded-md text-sm">
                    <option value="assign">{{ __('Bulk assign') }}</option>
                    <option value="priority">{{ __('Bulk priority') }}</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">{{ __('Assignee') }}</label>
                <select name="assigned_to" class="border-gray-300 rounded-md text-sm">
                    <option value="">{{ __('Unassigned') }}</option>
                    @foreach ($assignees as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">{{ __('Priority') }}</label>
                <select name="priority" class="border-gray-300 rounded-md text-sm">
                    @foreach ($priorities as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <x-primary-button type="submit">{{ __('Apply to selected') }}</x-primary-button>

            <div class="w-full overflow-x-auto mt-2">
                <table class="min-w-full divide-y divide-slate-200 text-sm" id="backlog-table">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-3 py-2"><input type="checkbox" id="backlog-select-all"></th>
                            <th class="px-3 py-2 text-left">{{ __('Task') }}</th>
                            <th class="px-3 py-2 text-left">{{ __('Priority') }}</th>
                            <th class="px-3 py-2 text-left">{{ __('Assignee') }}</th>
                            <th class="px-3 py-2 text-left">{{ __('Sprint') }}</th>
                            <th class="px-3 py-2 text-left">{{ __('Milestone') }}</th>
                            <th class="px-3 py-2 text-left">{{ __('Move') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100" id="backlog-body">
                        @forelse ($tasks as $task)
                            <tr draggable="true" data-task-id="{{ $task->id }}" class="hover:bg-slate-50">
                                <td class="px-3 py-2"><input type="checkbox" name="task_ids[]" value="{{ $task->id }}" class="backlog-check"></td>
                                <td class="px-3 py-2">
                                    <a href="{{ route('tasks.show', $task) }}" class="font-medium text-primary-600 hover:text-primary-700">{{ $task->title }}</a>
                                </td>
                                <td class="px-3 py-2">{{ $task->taskPriority?->name ?? $task->priority }}</td>
                                <td class="px-3 py-2">{{ $task->assignee?->name ?? '—' }}</td>
                                <td class="px-3 py-2">{{ $task->sprint?->name ?? '—' }}</td>
                                <td class="px-3 py-2">{{ $task->milestone?->name ?? '—' }}</td>
                                <td class="px-3 py-2">
                                    <select class="backlog-sprint border-gray-300 rounded text-xs" data-task-id="{{ $task->id }}">
                                        <option value="">{{ __('No sprint') }}</option>
                                        @foreach ($sprints as $sprint)
                                            <option value="{{ $sprint->id }}" @selected($task->sprint_id == $sprint->id)>{{ $sprint->name }}</option>
                                        @endforeach
                                    </select>
                                    <select class="backlog-milestone border-gray-300 rounded text-xs mt-1" data-task-id="{{ $task->id }}">
                                        <option value="">{{ __('No milestone') }}</option>
                                        @foreach ($milestones as $milestone)
                                            <option value="{{ $milestone->id }}" @selected($task->milestone_id == $milestone->id)>{{ $milestone->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-3 py-8 text-center text-slate-500">{{ __('No backlog tasks.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>
    </x-layouts.entity-listing>

    <script>
    (function () {
        const csrf = @json(csrf_token());
        document.getElementById('backlog-select-all')?.addEventListener('change', (e) => {
            document.querySelectorAll('.backlog-check').forEach((c) => { c.checked = e.target.checked; });
        });

        async function moveTask(taskId, body) {
            await fetch(@json(url('/tasks')) + '/' + taskId + '/backlog/move', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(body),
            });
        }

        document.querySelectorAll('.backlog-sprint').forEach((el) => {
            el.addEventListener('change', () => moveTask(el.dataset.taskId, { sprint_id: el.value || null }));
        });
        document.querySelectorAll('.backlog-milestone').forEach((el) => {
            el.addEventListener('change', () => moveTask(el.dataset.taskId, { milestone_id: el.value || null }));
        });

        const body = document.getElementById('backlog-body');
        let dragId = null;
        body?.querySelectorAll('tr[draggable]').forEach((row) => {
            row.addEventListener('dragstart', () => { dragId = row.dataset.taskId; });
            row.addEventListener('dragover', (e) => e.preventDefault());
            row.addEventListener('drop', async (e) => {
                e.preventDefault();
                const target = e.currentTarget;
                const dragged = body.querySelector('tr[data-task-id="' + dragId + '"]');
                if (!dragged || dragged === target) return;
                body.insertBefore(dragged, target);
                const ids = Array.from(body.querySelectorAll('tr[data-task-id]')).map((r) => parseInt(r.dataset.taskId, 10));
                await fetch(@json(route('tasks.backlog.reorder')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ task_ids: ids }),
                });
            });
        });
    })();
    </script>
</x-app-layout>
