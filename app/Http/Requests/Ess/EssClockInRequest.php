<?php

namespace App\Http\Requests\Ess;

use App\Services\Hrms\EssContext;
use Illuminate\Foundation\Http\FormRequest;

class EssClockInRequest extends FormRequest
{
    public function authorize(): bool
    {
        $employee = app(EssContext::class)->requireEmployee($this->user());

        return $this->user()?->can('clock', [$employee]) ?? false;
    }

    public function rules(): array
    {
        return [
            'clock_in_at' => ['nullable', 'date'],
        ];
    }

    public function employee()
    {
        return app(EssContext::class)->requireEmployee($this->user());
    }
}
