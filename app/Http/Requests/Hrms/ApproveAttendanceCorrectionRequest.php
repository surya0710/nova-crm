<?php

namespace App\Http\Requests\Hrms;

use App\Models\AttendanceCorrection;
use Illuminate\Foundation\Http\FormRequest;

class ApproveAttendanceCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $correction = $this->route('correction');

        return $correction instanceof AttendanceCorrection
            && ($this->user()?->can('review', $correction) ?? false);
    }

    public function rules(): array
    {
        return [
            'review_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
