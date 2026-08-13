<?php

namespace App\Http\Requests\Ess;

use App\Http\Requests\Concerns\CapturesAttendanceVerificationContext;
use App\Services\Hrms\EssContext;
use Illuminate\Foundation\Http\FormRequest;

class EssClockOutRequest extends FormRequest
{
    use CapturesAttendanceVerificationContext;

    public function authorize(): bool
    {
        $employee = app(EssContext::class)->requireEmployee($this->user());

        return $this->user()?->can('clock', [$employee]) ?? false;
    }

    public function rules(): array
    {
        return array_merge([
            'clock_out_at' => ['nullable', 'date'],
        ], $this->verificationRules());
    }

    public function employee()
    {
        return app(EssContext::class)->requireEmployee($this->user());
    }
}
