<?php

namespace App\Http\Requests\Hrms;

use App\Models\Holiday;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHolidayRequest extends FormRequest
{
    public function authorize(): bool
    {
        $holiday = $this->route('holiday');

        return $holiday instanceof Holiday
            && ($this->user()?->can('update', $holiday) ?? false);
    }

    public function rules(): array
    {
        /** @var Holiday $holiday */
        $holiday = $this->route('holiday');

        return [
            'name' => ['required', 'string', 'max:255'],
            'holiday_date' => ['required', 'date'],
            'branch_id' => [
                'nullable',
                'integer',
                Rule::exists('hrms_branches', 'id')->where('organization_id', $holiday->organization_id),
            ],
            'is_optional' => ['sometimes', 'boolean'],
            'is_recurring' => ['sometimes', 'boolean'],
        ];
    }
}
