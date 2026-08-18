<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PanFormat implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $value = strtoupper(preg_replace('/\s+/', '', (string) $value) ?? '');

        if (! preg_match((string) config('tax.pan_pattern'), $value)) {
            $fail(__('Enter a valid 10-character PAN.'));
        }
    }
}
