<?php

namespace App\Http\Requests\Ess;

use App\Models\AttendanceRecord;
use App\Services\Hrms\EssContext;
use Illuminate\Foundation\Http\FormRequest;

class EssAttendanceCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $record = AttendanceRecord::query()->find($this->input('attendance_record_id'));

        return $record !== null && ($this->user()?->can('submitCorrection', $record) ?? false);
    }

    public function rules(): array
    {
        $employee = app(EssContext::class)->requireEmployee($this->user());

        return [
            'attendance_record_id' => ['required', 'integer', 'exists:attendance_records,id'],
            'requested_clock_in_at' => ['nullable', 'date'],
            'requested_clock_out_at' => ['nullable', 'date'],
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }
}
