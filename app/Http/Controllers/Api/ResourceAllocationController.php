<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexResourceAllocationRequest;
use App\Http\Requests\StoreResourceAllocationRequest;
use App\Http\Requests\UpdateResourceAllocationRequest;
use App\Http\Resources\ResourceAllocationResource;
use App\Models\ResourceAllocation;
use App\Services\MetadataEntityFormService;
use App\Services\ResourceAllocationService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ResourceAllocationController extends Controller
{
    public function __construct(
        protected ResourceAllocationService $allocations,
        protected MetadataEntityFormService $metadataForms,
    ) {}

    public function index(IndexResourceAllocationRequest $request): AnonymousResourceCollection
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

        if ($taskId = $request->integer('task_id')) {
            $query->where('task_id', $taskId);
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

        return ResourceAllocationResource::collection(
            $query->paginate($request->perPage())
        );
    }

    public function show(ResourceAllocation $resource_allocation): ResourceAllocationResource
    {
        $this->authorize('view', $resource_allocation);
        $resource_allocation->load(['employee', 'project', 'task', 'creator']);

        return new ResourceAllocationResource($resource_allocation);
    }

    public function store(StoreResourceAllocationRequest $request, TenantContext $tenant): JsonResponse
    {
        if (! $tenant->get()) {
            return response()->json(['message' => __('Organization context is required.')], 422);
        }

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
        $allocation->load(['employee', 'project', 'task', 'creator']);

        return (new ResourceAllocationResource($allocation))
            ->response()
            ->setStatusCode(201);
    }

    public function update(
        UpdateResourceAllocationRequest $request,
        ResourceAllocation $resource_allocation,
        TenantContext $tenant,
    ): ResourceAllocationResource {
        $metadataValues = $this->metadataForms->validatedValuesFromRequest(
            $resource_allocation,
            $tenant->get(),
            'resource_allocation',
            'edit',
            $request,
        );

        $allocation = $this->allocations->update(
            $resource_allocation,
            $request->validated(),
            $request->user(),
            $metadataValues,
        );
        $allocation->load(['employee', 'project', 'task', 'creator']);

        return new ResourceAllocationResource($allocation);
    }

    public function destroy(ResourceAllocation $resource_allocation): JsonResponse
    {
        $this->authorize('delete', $resource_allocation);
        $this->allocations->delete($resource_allocation, request()->user());

        return response()->json(null, 204);
    }
}
