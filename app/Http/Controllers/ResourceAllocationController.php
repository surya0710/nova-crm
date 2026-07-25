<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexResourceAllocationRequest;
use App\Http\Requests\StoreResourceAllocationRequest;
use App\Http\Requests\UpdateResourceAllocationRequest;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ResourceAllocation;
use App\Models\Task;
use App\Services\MetadataEntityFormService;
use App\Services\ResourceAllocationService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ResourceAllocationController extends Controller
{
    public function __construct(
        protected ResourceAllocationService $allocations,
        protected MetadataEntityFormService $metadataForms,
    ) {
        $this->authorizeResource(ResourceAllocation::class, 'allocation');
    }

    public function index(IndexResourceAllocationRequest $request): View
    {
        $query = ResourceAllocation::query()
            ->with(['employee', 'project', 'task', 'creator'])
            ->latest('planned_start_date');

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'like', "%{$search}%")
                    ->orWhereHas('employee', fn ($e) => $e
                        ->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%"))
                    ->orWhereHas('project', fn ($p) => $p->where('name', 'like', "%{$search}%"));
            });
        }

        if ($employeeId = $request->integer('employee_id')) {
            $query->where('employee_id', $employeeId);
        }

        if ($projectId = $request->integer('project_id')) {
            $query->where('project_id', $projectId);
        }

        if ($type = $request->string('allocation_type')->toString()) {
            $query->where('allocation_type', $type);
        }

        if ($from = $request->input('from')) {
            $query->whereDate('planned_end_date', '>=', $from);
        }

        if ($to = $request->input('to')) {
            $query->whereDate('planned_start_date', '<=', $to);
        }

        return view('resources.allocations.index', [
            'allocations' => $query->paginate($request->perPage())->withQueryString(),
            'employees' => Employee::query()->orderBy('first_name')->limit(200)->get(),
            'projects' => Project::query()->where('is_archived', false)->orderBy('name')->limit(200)->get(),
            'types' => config('resources.allocation_types', []),
            'filters' => $request->only(['search', 'employee_id', 'project_id', 'allocation_type', 'from', 'to']),
        ]);
    }

    public function create(TenantContext $tenant): View
    {
        return view('resources.allocations.create', [
            'allocation' => new ResourceAllocation([
                'allocation_type' => 'project',
                'allocation_percentage' => 50,
                'planned_start_date' => now()->toDateString(),
                'planned_end_date' => now()->addWeeks(2)->toDateString(),
            ]),
            ...$this->formOptions($tenant),
            'metadataFields' => $this->metadataForms->fieldsFor($tenant->get(), 'resource_allocation', 'create'),
            'metadataPresenter' => $this->metadataForms->presenter(),
        ]);
    }

    public function store(StoreResourceAllocationRequest $request, TenantContext $tenant): RedirectResponse
    {
        $metadataValues = $this->metadataForms->validatedValuesFromRequest(
            null,
            $tenant->get(),
            'resource_allocation',
            'create',
            $request,
        );

        $allocation = $this->allocations->create(
            $request->validated(),
            $request->user(),
            $metadataValues,
        );

        return redirect()
            ->route('resources.allocations.show', $allocation)
            ->with('success', __('Resource allocation created.'));
    }

    public function show(ResourceAllocation $allocation): View
    {
        $allocation->load(['employee', 'project', 'task', 'creator']);

        return view('resources.allocations.show', [
            'allocation' => $allocation,
            'metadataFields' => $this->metadataForms->fieldsFor(
                $allocation->organization,
                'resource_allocation',
                'detail',
            ),
            'metadataPresenter' => $this->metadataForms->presenter(),
        ]);
    }

    public function edit(ResourceAllocation $allocation, TenantContext $tenant): View
    {
        return view('resources.allocations.edit', [
            'allocation' => $allocation,
            ...$this->formOptions($tenant),
            'metadataFields' => $this->metadataForms->fieldsFor($tenant->get(), 'resource_allocation', 'edit'),
            'metadataPresenter' => $this->metadataForms->presenter(),
        ]);
    }

    public function update(
        UpdateResourceAllocationRequest $request,
        ResourceAllocation $allocation,
        TenantContext $tenant,
    ): RedirectResponse {
        $metadataValues = $this->metadataForms->validatedValuesFromRequest(
            $allocation,
            $tenant->get(),
            'resource_allocation',
            'edit',
            $request,
        );

        $this->allocations->update(
            $allocation,
            $request->validated(),
            $request->user(),
            $metadataValues,
        );

        return redirect()
            ->route('resources.allocations.show', $allocation)
            ->with('success', __('Resource allocation updated.'));
    }

    public function destroy(ResourceAllocation $allocation): RedirectResponse
    {
        $this->allocations->delete($allocation, request()->user());

        return redirect()
            ->route('resources.allocations.index')
            ->with('success', __('Resource allocation released.'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function formOptions(TenantContext $tenant): array
    {
        return [
            'employees' => Employee::query()->orderBy('first_name')->limit(200)->get(),
            'projects' => Project::query()->where('is_archived', false)->orderBy('name')->limit(200)->get(),
            'tasks' => Task::query()->where('is_archived', false)->orderBy('title')->limit(200)->get(),
            'types' => config('resources.allocation_types', []),
        ];
    }
}
