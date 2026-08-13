<?php

namespace App\Http\Requests\Ess;

use Illuminate\Foundation\Http\FormRequest;

class EssCancelWfhRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $wfhRequest = $this->route('wfhRequest');

        return $this->user()?->can('cancel', $wfhRequest) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
