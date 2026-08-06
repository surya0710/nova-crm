<?php

namespace App\Http\Requests\Hrms;

use App\Models\AttendanceOvertimeEntry;
use Illuminate\Foundation\Http\FormRequest;

class ApproveAttendanceOvertimeEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $entry = $this->route('entry');

        return $entry instanceof AttendanceOvertimeEntry
            && ($this->user()?->can('approveOvertime', $entry) ?? false);
    }

    public function rules(): array
    {
        return [
            'review_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'review_notes.max' => __('attendance.overtime.validation.review_notes_max'),
        ];
    }

    public function attributes(): array
    {
        return [
            'review_notes' => __('attendance.overtime.attributes.review_notes'),
        ];
    }

    /**
     * @return array{review_notes?: string|null}
     */
    public function reviewData(): array
    {
        return $this->validated();
    }
}
