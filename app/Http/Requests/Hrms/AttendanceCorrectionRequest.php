<?php

namespace App\Http\Requests\Hrms;

use App\Models\AttendanceCorrection;
use App\Models\AttendanceRecord;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttendanceCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', AttendanceCorrection::class) ?? false;
    }

    public function rules(): array
    {
        $org = app(TenantContext::class)->get();

        return [
            'attendance_record_id' => [
                'required',
                'integer',
                Rule::exists('attendance_records', 'id')->where('organization_id', $org?->id),
            ],
            'requested_clock_in_at' => ['nullable', 'date'],
            'requested_clock_out_at' => ['nullable', 'date', 'after:requested_clock_in_at'],
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }

    public function attendanceRecord(): AttendanceRecord
    {
        return AttendanceRecord::query()->findOrFail((int) $this->validated('attendance_record_id'));
    }
}
