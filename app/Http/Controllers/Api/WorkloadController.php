<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexWorkloadRequest;
use App\Http\Resources\WorkloadSnapshotResource;
use App\Models\Employee;
use App\Models\WorkloadSnapshot;
use App\Services\TenantContext;
use App\Services\WorkloadService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WorkloadController extends Controller
{
    public function __construct(protected WorkloadService $workload) {}

    public function employee(IndexWorkloadRequest $request, Employee $employee, TenantContext $tenant): JsonResponse
    {
        abort_unless((int) $employee->organization_id === (int) $tenant->id(), 404);

        $from = Carbon::parse($request->input('from', now()->startOfMonth()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->input('to', now()->endOfMonth()->toDateString()))->startOfDay();

        return response()->json(
            $this->workload->calculateForEmployee($employee, $from, $to)
        );
    }

    public function team(IndexWorkloadRequest $request, TenantContext $tenant): JsonResponse
    {
        $organization = $tenant->get();
        abort_unless($organization !== null, 422);

        $from = Carbon::parse($request->input('from', now()->startOfWeek()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->input('to', now()->endOfWeek()->toDateString()))->startOfDay();

        return response()->json([
            'organization_id' => $organization->id,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'employees' => $this->workload->calculateTeam($organization, $from, $to),
        ]);
    }

    public function storeSnapshots(IndexWorkloadRequest $request, TenantContext $tenant): AnonymousResourceCollection|JsonResponse
    {
        $this->authorize('createSnapshot', WorkloadSnapshot::class);

        $organization = $tenant->get();
        if (! $organization) {
            return response()->json(['message' => __('Organization context is required.')], 422);
        }

        $date = Carbon::parse($request->input('date', now()->toDateString()))->startOfDay();

        if ($employeeId = $request->integer('employee_id')) {
            $employee = Employee::query()
                ->where('organization_id', $organization->id)
                ->whereKey($employeeId)
                ->firstOrFail();

            $snapshot = $this->workload->snapshotEmployee($employee, $date);
            $snapshot->load('employee');

            return WorkloadSnapshotResource::collection(collect([$snapshot]));
        }

        $snapshots = $this->workload->snapshotTeam($organization, $date);
        $snapshots->load('employee');

        return WorkloadSnapshotResource::collection($snapshots);
    }
}
