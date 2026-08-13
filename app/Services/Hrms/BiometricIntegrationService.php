<?php

namespace App\Services\Hrms;

use App\Models\Employee;
use Illuminate\Support\Arr;

/**
 * Validates biometric verification payloads supplied by devices/clients.
 *
 * Hardware vendor connectors are intentionally out of scope for Phase 10.8;
 * this service normalizes and verifies the attendance proof shape so clock
 * flows can treat biometric as a first-class verification source.
 */
class BiometricIntegrationService
{
    /**
     * @param  array<string, mixed>  $context
     * @return array{
     *     verified: bool,
     *     status: string,
     *     reason: ?string,
     *     metadata: array<string, mixed>
     * }
     */
    public function verify(Employee $employee, array $context = []): array
    {
        $verifiedFlag = Arr::get($context, 'biometric_verified');
        $token = Arr::get($context, 'biometric_token');
        $reference = Arr::get($context, 'biometric_reference');
        $provider = Arr::get($context, 'biometric_provider', 'device');
        $deviceId = Arr::get($context, 'device_id');

        $metadata = [
            'provider' => $provider,
            'device_id' => $deviceId,
            'biometric_reference' => $reference,
            'employee_id' => $employee->id,
        ];

        if ($verifiedFlag === true || $verifiedFlag === 1 || $verifiedFlag === '1') {
            if (! filled($token) && ! filled($reference)) {
                return [
                    'verified' => false,
                    'status' => 'failed',
                    'reason' => 'biometric_proof_missing',
                    'metadata' => $metadata,
                ];
            }

            return [
                'verified' => true,
                'status' => 'verified',
                'reason' => null,
                'metadata' => array_merge($metadata, [
                    'proof_type' => filled($token) ? 'token' : 'reference',
                ]),
            ];
        }

        if (filled($token) || filled($reference)) {
            return [
                'verified' => true,
                'status' => 'verified',
                'reason' => null,
                'metadata' => array_merge($metadata, [
                    'proof_type' => filled($token) ? 'token' : 'reference',
                ]),
            ];
        }

        return [
            'verified' => false,
            'status' => 'failed',
            'reason' => 'biometric_not_provided',
            'metadata' => $metadata,
        ];
    }
}
