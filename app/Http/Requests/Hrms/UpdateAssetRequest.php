<?php

namespace App\Http\Requests\Hrms;

class UpdateAssetRequest extends CreateAssetRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('asset')) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'asset_code' => ['prohibited'],
        ];
    }
}
