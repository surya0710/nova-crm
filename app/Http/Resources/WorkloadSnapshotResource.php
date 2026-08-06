<?php

namespace App\Http\Resources;

use App\Models\WorkloadSnapshot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkloadSnapshot */
class WorkloadSnapshotResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'employee_id' => $this->employee_id,
            'employee' => $this->whenLoaded('employee', fn () => [
                'id' => $this->employee?->id,
                'name' => $this->employee?->full_name,
            ]),
            'snapshot_date' => $this->snapshot_date?->toDateString(),
            'allocated_hours' => $this->allocated_hours,
            'available_hours' => $this->available_hours,
            'utilization_percentage' => $this->utilization_percentage,
            'overall_status' => $this->overall_status,
            'overall_status_label' => $this->overall_status_label,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
