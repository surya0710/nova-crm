<?php

namespace App\Http\Requests\Hrms;

use App\Models\Holiday;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateHolidayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Holiday::class) ?? false;
    }

    public function rules(): array
    {
        $org = app(TenantContext::class)->get();

        return [
            'name' => ['required', 'string', 'max:255'],
            'holiday_date' => ['required', 'date'],
            'branch_id' => [
                'nullable',
                'integer',
                Rule::exists('hrms_branches', 'id')->where('organization_id', $org?->id),
            ],
            'is_optional' => ['sometimes', 'boolean'],
            'is_recurring' => ['sometimes', 'boolean'],
        ];
    }
}
