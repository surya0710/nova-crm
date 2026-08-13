<?php

namespace App\Http\Requests\Concerns;

trait CapturesAttendanceVerificationContext
{
    /** @return array<string, mixed> */
    public function verificationContext(): array
    {
        $validated = $this->validated();

        return array_filter([
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'accuracy_meters' => $validated['accuracy_meters'] ?? null,
            'device_id' => $validated['device_id'] ?? null,
            'biometric_verified' => $validated['biometric_verified'] ?? null,
            'biometric_token' => $validated['biometric_token'] ?? null,
            'biometric_reference' => $validated['biometric_reference'] ?? null,
            'biometric_provider' => $validated['biometric_provider'] ?? null,
        ], static fn ($value) => $value !== null && $value !== '');
    }

    /** @return array<string, mixed> */
    protected function verificationRules(): array
    {
        return [
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'accuracy_meters' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'device_id' => ['nullable', 'string', 'max:191'],
            'biometric_verified' => ['nullable', 'boolean'],
            'biometric_token' => ['nullable', 'string', 'max:500'],
            'biometric_reference' => ['nullable', 'string', 'max:191'],
            'biometric_provider' => ['nullable', 'string', 'max:100'],
        ];
    }
}
