<?php

namespace App\Http\Resources\Hrms;

use App\Models\AttendanceRecord;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AttendanceRecord */
class AttendanceResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'attendance_date' => $this->attendance_date?->toDateString(),
            'clock_in_at' => $this->clock_in_at?->toIso8601String(),
            'clock_out_at' => $this->clock_out_at?->toIso8601String(),
            'status' => $this->status,
            'source' => $this->source,
            'working_minutes' => $this->working_minutes,
            'break_minutes' => $this->break_minutes,
            'late_minutes' => $this->late_minutes,
            'early_departure_minutes' => $this->early_departure_minutes,
            'overtime_minutes' => $this->overtime_minutes,
            'notes' => $this->notes,
            'approval_status' => $this->approval_status,
            'verification' => [
                'clock_in' => [
                    'latitude' => $this->clock_in_latitude,
                    'longitude' => $this->clock_in_longitude,
                    'accuracy_meters' => $this->clock_in_accuracy_meters,
                    'device_id' => $this->clock_in_device_id,
                    'geofence_id' => $this->clock_in_geofence_id,
                    'status' => $this->clock_in_verification_status,
                    'metadata' => $this->clock_in_verification_metadata,
                ],
                'clock_out' => [
                    'latitude' => $this->clock_out_latitude,
                    'longitude' => $this->clock_out_longitude,
                    'accuracy_meters' => $this->clock_out_accuracy_meters,
                    'device_id' => $this->clock_out_device_id,
                    'geofence_id' => $this->clock_out_geofence_id,
                    'status' => $this->clock_out_verification_status,
                    'metadata' => $this->clock_out_verification_metadata,
                ],
            ],
            'shift' => $this->whenLoaded('shift', fn () => [
                'id' => $this->shift?->id,
                'name' => $this->shift?->name,
                'code' => $this->shift?->code,
                'start_time' => $this->shift?->start_time,
                'end_time' => $this->shift?->end_time,
            ]),
            'employee' => $this->whenLoaded('employee', fn () => [
                'id' => $this->employee?->id,
                'full_name' => $this->employee?->full_name,
                'employee_code' => $this->employee?->employee_code,
            ]),
        ];
    }
}
