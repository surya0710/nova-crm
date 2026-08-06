<?php

namespace App\Http\Requests\Hrms;

use App\Models\AttendanceOvertimeRule;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAttendanceOvertimeRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $rule = $this->route('rule');

        return $rule instanceof AttendanceOvertimeRule
            && ($this->user()?->can('manageRules', $rule) ?? false);
    }

    public function rules(): array
    {
        $org = app(TenantContext::class)->get();
        $rule = $this->route('rule');
        $ruleId = $rule instanceof AttendanceOvertimeRule ? $rule->id : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('attendance_overtime_rules', 'code')
                    ->where('organization_id', $org?->id)
                    ->ignore($ruleId),
            ],
            'rule_type' => ['required', 'string', Rule::in(AttendanceOvertimeRule::types())],
            'minimum_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'maximum_minutes' => ['nullable', 'integer', 'min:0', 'max:1440', 'gte:minimum_minutes'],
            'round_off_minutes' => ['nullable', 'integer', 'min:0', 'max:240'],
            'multiplier' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'requires_approval' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'rule_type.in' => __('attendance.overtime.validation.rule_type'),
            'maximum_minutes.gte' => __('attendance.overtime.validation.maximum_gte_minimum'),
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('attendance.overtime.attributes.name'),
            'code' => __('attendance.overtime.attributes.code'),
            'rule_type' => __('attendance.overtime.attributes.rule_type'),
            'minimum_minutes' => __('attendance.overtime.attributes.minimum_minutes'),
            'maximum_minutes' => __('attendance.overtime.attributes.maximum_minutes'),
            'round_off_minutes' => __('attendance.overtime.attributes.round_off_minutes'),
            'multiplier' => __('attendance.overtime.attributes.multiplier'),
            'requires_approval' => __('attendance.overtime.attributes.requires_approval'),
            'is_active' => __('attendance.overtime.attributes.is_active'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function overtimeRuleData(): array
    {
        $data = $this->validated();
        $data['requires_approval'] = $this->boolean('requires_approval');
        $data['is_active'] = $this->boolean('is_active', true);

        return $data;
    }
}
