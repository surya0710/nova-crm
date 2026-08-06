<?php

namespace App\Http\Resources\Hrms;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Employee */
class EmployeeResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_code' => $this->employee_code,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'personal_email' => $this->personal_email,
            'mobile' => $this->mobile,
            'phone' => $this->phone,
            'gender' => $this->gender,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'status' => $this->status,
            'employment_type' => $this->employment_type,
            'joining_date' => $this->joining_date?->toDateString(),
            'profile_photo_path' => $this->profile_photo_path,
            'address_line_1' => $this->address_line_1,
            'address_line_2' => $this->address_line_2,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'country' => $this->country,
            'department' => $this->whenLoaded('department', fn () => [
                'id' => $this->department?->id,
                'name' => $this->department?->name,
            ]),
            'designation' => $this->whenLoaded('designation', fn () => [
                'id' => $this->designation?->id,
                'name' => $this->designation?->name,
            ]),
            'branch' => $this->whenLoaded('branch', fn () => [
                'id' => $this->branch?->id,
                'name' => $this->branch?->name,
            ]),
            'reporting_manager' => $this->whenLoaded('reportingManager', fn () => [
                'id' => $this->reportingManager?->id,
                'full_name' => $this->reportingManager?->full_name,
                'employee_code' => $this->reportingManager?->employee_code,
            ]),
            'emergency_contacts' => $this->whenLoaded('emergencyContacts', fn () => $this->emergencyContacts->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'relationship' => $c->relationship,
                'phone' => $c->phone,
                'is_primary' => (bool) $c->is_primary,
            ])->values()->all()),
        ];
    }
}
