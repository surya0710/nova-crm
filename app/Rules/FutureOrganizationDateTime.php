<?php

namespace App\Rules;

use App\Services\LeadFollowUpService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class FutureOrganizationDateTime implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $service = app(LeadFollowUpService::class);
        $parsed = $service->parseInput(is_string($value) ? $value : null);

        if (! $parsed) {
            return;
        }

        if ($parsed->copy()->timezone($service->organizationTimezone())->lte($service->organizationNow())) {
            $fail(__('The follow-up must be scheduled after the current date and time (:time).', [
                'time' => $service->organizationNow()->format('M j, Y g:i A'),
            ]));
        }
    }
}
