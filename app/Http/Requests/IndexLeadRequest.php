<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesGeographicFilters;
use Illuminate\Foundation\Http\FormRequest;

class IndexLeadRequest extends FormRequest
{
    use ValidatesGeographicFilters;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('leads.view') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge($this->geographicFilterRules(), [
            'search' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
