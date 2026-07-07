<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        ];
    }
}
