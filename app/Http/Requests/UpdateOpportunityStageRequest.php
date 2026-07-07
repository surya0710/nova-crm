<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateOpportunityStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $opportunity = $this->route('opportunity');

        return $opportunity && ($this->user()?->can('update', $opportunity) ?? false);
    }

    public function rules(): array
    {
        return [
            'stage' => ['required', 'string', Rule::in(array_keys(config('pipeline.stages')))],
            'won_at' => ['nullable', 'date', 'required_if:stage,closed_won'],
            'lost_reason' => ['nullable', 'string', 'max:2000', 'required_if:stage,closed_lost'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $opportunity = $this->route('opportunity');
            $stage = $this->input('stage');

            if (! $opportunity) {
                return;
            }

            if ($opportunity->isClosed()) {
                $validator->errors()->add('stage', __('Closed deals cannot be moved to another stage.'));

                return;
            }

            if (in_array($stage, config('pipeline.open_stages', []), true)) {
                return;
            }

            if (! in_array($stage, config('pipeline.closed_stages', []), true)) {
                $validator->errors()->add('stage', __('Invalid stage transition.'));
            }
        });
    }
}
