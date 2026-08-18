<?php

namespace App\Rules;

use App\Support\Gstin;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class GstinFormat implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! Gstin::isValid((string) $value)) {
            $fail(__('Enter a valid 15-character GSTIN.'));
        }
    }
}
