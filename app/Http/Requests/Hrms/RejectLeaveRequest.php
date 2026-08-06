<?php

namespace App\Http\Requests\Hrms;

use App\Models\LeaveApplication;
use Illuminate\Foundation\Http\FormRequest;

class RejectLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        $application = $this->route('leave_application');

        return $application instanceof LeaveApplication
            && ($this->user()?->can('approve', $application) ?? false);
    }

    public function rules(): array
    {
        return [
            'remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
