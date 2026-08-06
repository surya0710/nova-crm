<?php

namespace App\Http\Requests\Platform;

use App\Services\Modules\ModuleRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationModulesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $keys = app(ModuleRegistry::class)->keys();

        return [
            'modules' => ['nullable', 'array'],
            'modules.*' => ['string', Rule::in($keys)],
        ];
    }
}
