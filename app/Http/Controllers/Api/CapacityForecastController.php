<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CapacityForecastRequest;
use App\Models\Employee;
use App\Services\CapacityPlanningService;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class CapacityForecastController extends Controller
{
    public function __construct(protected CapacityPlanningService $capacity) {}

    public function forecast(CapacityForecastRequest $request, TenantContext $tenant): JsonResponse
    {
        $organization = $tenant->get();
        abort_unless($organization !== null, 422);

        $from = Carbon::parse($request->input('from', now()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->input(
            'to',
            now()->addDays((int) config('resources.capacity_risk_days', 14))->toDateString()
        ))->startOfDay();

        if ($employeeId = $request->integer('employee_id')) {
            $employee = Employee::query()
                ->where('organization_id', $organization->id)
                ->whereKey($employeeId)
                ->firstOrFail();

            return response()->json($this->capacity->forecast($employee, $from, $to));
        }

        return response()->json($this->capacity->forecast($organization, $from, $to));
    }

    public function risks(CapacityForecastRequest $request, TenantContext $tenant): JsonResponse
    {
        $organization = $tenant->get();
        abort_unless($organization !== null, 422);

        $days = $request->integer('days') ?: null;

        return response()->json([
            'organization_id' => $organization->id,
            'days' => $days ?? (int) config('resources.capacity_risk_days', 14),
            'risks' => $this->capacity->upcomingRisks($organization, $days),
        ]);
    }
}
