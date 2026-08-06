<?php

namespace App\Http\Requests;

use App\Models\ResourceCalendar;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateResourceCalendarRequest extends FormRequest
{
    public function authorize(): bool
    {
        $calendar = $this->route('calendar') ?? $this->route('resource_calendar');

        return $calendar instanceof ResourceCalendar
            ? ($this->user()?->can('update', $calendar) ?? false)
            : false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = app(TenantContext::class)->id();

        return [
            'employee_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('employees', 'id')->where('organization_id', $organizationId),
            ],
            'working_hours_per_day' => ['sometimes', 'required', 'numeric', 'min:0.25', 'max:24'],
            'working_days' => ['sometimes', 'required', 'array', 'min:1'],
            'working_days.*' => ['required', 'string', 'max:20'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'effective_from' => ['sometimes', 'required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('effective_to') && $this->input('effective_to') === '') {
            $this->merge(['effective_to' => null]);
        }
    }
}
