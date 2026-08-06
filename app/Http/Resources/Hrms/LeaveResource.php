<?php

namespace App\Http\Resources\Hrms;

use App\Models\LeaveApplication;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin LeaveApplication */
class LeaveResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'leave_type_id' => $this->leave_type_id,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'is_half_day' => (bool) $this->is_half_day,
            'half_day_period' => $this->half_day_period,
            'days' => $this->days,
            'reason' => $this->reason,
            'status' => $this->status,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'leave_type' => $this->whenLoaded('leaveType', fn () => [
                'id' => $this->leaveType?->id,
                'name' => $this->leaveType?->name,
                'code' => $this->leaveType?->code,
                'is_paid' => (bool) $this->leaveType?->is_paid,
            ]),
            'employee' => $this->whenLoaded('employee', fn () => [
                'id' => $this->employee?->id,
                'full_name' => $this->employee?->full_name,
                'employee_code' => $this->employee?->employee_code,
            ]),
        ];
    }
}
