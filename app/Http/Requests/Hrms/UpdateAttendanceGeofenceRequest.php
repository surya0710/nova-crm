<?php

namespace App\Http\Requests\Hrms;

use App\Models\AttendanceGeofence;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAttendanceGeofenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var AttendanceGeofence $geofence */
        $geofence = $this->route('geofence');

        return $this->user()?->can('update', $geofence) ?? false;
    }

    public function rules(): array
    {
        $org = app(TenantContext::class)->get();

        return [
            'name' => ['required', 'string', 'max:255'],
            'branch_id' => [
                'nullable',
                'integer',
                Rule::exists('hrms_branches', 'id')->where('organization_id', $org?->id),
            ],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_meters' => [
                'required',
                'integer',
                'min:'.(int) config('hrms.attendance_geofence.min_radius_meters', 10),
                'max:'.(int) config('hrms.attendance_geofence.max_radius_meters', 50000),
            ],
            'is_active' => ['sometimes', 'boolean'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ];
    }
}
