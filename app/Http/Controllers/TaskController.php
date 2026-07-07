<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Task;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Task::class, 'task');
    }

    public function index(Request $request, TenantContext $tenant): View
    {
        $organization = $tenant->get();

        $query = Task::query()
            ->with(['assignee', 'creator', 'taskable'])
            ->latest();

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($priority = $request->string('priority')->toString()) {
            $query->where('priority', $priority);
        }

        if ($assignedTo = $request->integer('assigned_to')) {
            $query->where('assigned_to', $assignedTo);
        }

        if ($request->string('filter')->toString() === 'overdue') {
            $query->whereIn('status', ['pending', 'in_progress'])
                ->whereNotNull('due_at')
                ->where('due_at', '<', now());
        }

        if ($request->string('filter')->toString() === 'due_today') {
            $query->whereIn('status', ['pending', 'in_progress'])
                ->whereDate('due_at', today());
        }

        return view('tasks.index', [
            'tasks' => $query->paginate(15)->withQueryString(),
            'organization' => $organization,
            'assignees' => $this->organizationMembers($organization),
            'filters' => $request->only(['search', 'status', 'priority', 'assigned_to', 'filter']),
        ]);
    }

    public function create(Request $request, TenantContext $tenant): View
    {
        $task = new Task([
            'status' => 'pending',
            'priority' => 'medium',
            'due_at' => now()->addDay(),
        ]);

        if ($request->filled('taskable_type') && $request->filled('taskable_id')) {
            $task->taskable_type = config('tasks.taskable.'.$request->string('taskable_type'));
            $task->taskable_id = $request->integer('taskable_id');
        }

        return view('tasks.create', [
            'task' => $task,
            'assignees' => $this->organizationMembers($tenant->get()),
            'taskableOptions' => $this->taskableOptions($tenant->get()),
        ]);
    }

    public function store(StoreTaskRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $taskable = $this->resolveTaskable($validated);

        $task = Task::query()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'priority' => $validated['priority'],
            'due_at' => $validated['due_at'] ?? null,
            'assigned_to' => $validated['assigned_to'] ?? null,
            'taskable_type' => $taskable?->getMorphClass(),
            'taskable_id' => $taskable?->getKey(),
            'created_by' => $request->user()->id,
            'completed_at' => $validated['status'] === 'completed' ? now() : null,
        ]);

        if ($request->boolean('redirect_back') && $taskable) {
            return back()->with('status', 'task-created');
        }

        return redirect()
            ->route('tasks.show', $task)
            ->with('status', 'task-created');
    }

    public function show(Task $task): View
    {
        $task->load(['assignee', 'creator', 'taskable']);

        return view('tasks.show', [
            'task' => $task,
        ]);
    }

    public function edit(Task $task, TenantContext $tenant): View
    {
        return view('tasks.edit', [
            'task' => $task,
            'assignees' => $this->organizationMembers($tenant->get()),
            'taskableOptions' => $this->taskableOptions($tenant->get()),
        ]);
    }

    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        $validated = $request->validated();
        $taskable = $this->resolveTaskable($validated);

        $completedAt = $task->completed_at;

        if ($validated['status'] === 'completed' && ! $completedAt) {
            $completedAt = now();
        } elseif ($validated['status'] !== 'completed') {
            $completedAt = null;
        }

        $task->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'priority' => $validated['priority'],
            'due_at' => $validated['due_at'] ?? null,
            'assigned_to' => $validated['assigned_to'] ?? null,
            'taskable_type' => $taskable?->getMorphClass(),
            'taskable_id' => $taskable?->getKey(),
            'completed_at' => $completedAt,
        ]);

        return redirect()
            ->route('tasks.show', $task)
            ->with('status', 'task-updated');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $task->delete();

        return redirect()
            ->route('tasks.index')
            ->with('status', 'task-deleted');
    }

    public function complete(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $task->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return back()->with('status', 'task-completed');
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    protected function organizationMembers($organization)
    {
        return $organization->users()->orderBy('name')->get();
    }

    /**
     * @return array<string, array<int, array{id: int, label: string}>>
     */
    protected function taskableOptions($organization): array
    {
        return [
            'lead' => Lead::query()->orderBy('name')->get()->map(fn (Lead $lead) => [
                'id' => $lead->id,
                'label' => $lead->name,
            ])->all(),
            'customer' => Customer::query()->orderBy('name')->get()->map(fn (Customer $customer) => [
                'id' => $customer->id,
                'label' => $customer->display_name,
            ])->all(),
            'opportunity' => Opportunity::query()->orderBy('title')->get()->map(fn (Opportunity $opportunity) => [
                'id' => $opportunity->id,
                'label' => $opportunity->title,
            ])->all(),
        ];
    }

    protected function resolveTaskable(array $validated): ?Model
    {
        if (empty($validated['taskable_type']) || empty($validated['taskable_id'])) {
            return null;
        }

        $class = config('tasks.taskable.'.$validated['taskable_type']);

        return $class::query()->find($validated['taskable_id']);
    }
}
