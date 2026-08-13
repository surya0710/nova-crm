<?php

namespace App\Services\Hrms;

use App\Models\AttendanceRecord;
use App\Models\AttendanceVerificationAudit;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class AttendanceVerificationService
{
    public function __construct(
        protected GeofenceService $geofenceService,
        protected BiometricIntegrationService $biometricIntegrationService,
        protected AuditLogger $auditLogger,
        protected WfhPolicyService $wfhPolicyService,
    ) {}

    /**
     * @return array{mode: string, max_accuracy_meters: int, require_device_id: bool}
     */
    public function resolveOrganizationPolicy(Organization|Employee $subject): array
    {
        $organization = $subject instanceof Employee
            ? ($subject->organization ?? Organization::query()->find($subject->organization_id))
            : $subject;

        $rules = $organization?->settings['attendance_rules'] ?? [];
        $mode = (string) ($rules['attendance_verification_mode']
            ?? config('hrms.attendance_verification_modes_default', 'none'));

        if (! array_key_exists($mode, config('hrms.attendance_verification_modes', []))) {
            $mode = 'none';
        }

        return [
            'mode' => $mode,
            'max_accuracy_meters' => (int) ($rules['max_accuracy_meters']
                ?? config('hrms.attendance_geofence.default_max_accuracy_meters', 100)),
            'require_device_id' => (bool) ($rules['require_device_id'] ?? false),
        ];
    }

    /**
     * Verify clock context against organization policy.
     *
     * @param  array<string, mixed>  $context
     * @return array{
     *     passed: bool,
     *     status: string,
     *     reason: ?string,
     *     mode: string,
     *     latitude: ?float,
     *     longitude: ?float,
     *     accuracy_meters: ?int,
     *     device_id: ?string,
     *     geofence_id: ?int,
     *     metadata: array<string, mixed>
     * }
     */
    public function verify(
        Employee $employee,
        string $event,
        array $context = [],
        Carbon|string|null $at = null,
    ): array {
        $policy = $this->resolveOrganizationPolicy($employee);
        $mode = $policy['mode'];
        $at = $at === null ? now() : Carbon::parse($at);
        $wfh = $this->wfhPolicyService->resolveForDate($employee, $at);

        $latitude = $this->nullableFloat(Arr::get($context, 'latitude'));
        $longitude = $this->nullableFloat(Arr::get($context, 'longitude'));
        $accuracy = Arr::has($context, 'accuracy_meters')
            ? (int) Arr::get($context, 'accuracy_meters')
            : null;
        $deviceId = Arr::get($context, 'device_id');

        $base = [
            'passed' => true,
            'status' => 'not_required',
            'reason' => null,
            'mode' => $mode,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy_meters' => $accuracy,
            'device_id' => $deviceId !== null ? (string) $deviceId : null,
            'geofence_id' => null,
            'metadata' => [
                'event' => $event,
                'verified_at' => $at->toIso8601String(),
                'policy' => $policy,
                'wfh' => [
                    'is_wfh' => $wfh['is_wfh'],
                    'policy_type' => $wfh['policy_type'],
                    'source' => $wfh['source'],
                    'source_id' => $wfh['source_id'],
                    'bypass_geofence' => $wfh['bypass_geofence'],
                    'record_gps' => $wfh['record_gps'],
                ],
            ],
        ];

        $wfhGeofenceExemption = $wfh['is_wfh'] && $wfh['bypass_geofence'];

        if ($mode === 'none' || ($wfhGeofenceExemption && ! $wfh['record_gps'] && $mode === 'geofence')) {
            if ($wfhGeofenceExemption) {
                $base['status'] = 'not_required';
                $base['reason'] = 'wfh_exemption';
                $base['metadata']['wfh_exemption'] = true;
            }

            return $base;
        }

        if ($policy['require_device_id'] && ! filled($deviceId)) {
            return $this->fail($base, 'device_id_required', [
                'requirement' => 'device_id',
            ]);
        }

        $requiresGps = in_array($mode, ['gps', 'geofence', 'gps_and_biometric'], true);
        // Permanent/daily/selected WFH may skip office geofence while still
        // optionally recording GPS when organization policy requires it.
        $requiresGeofence = $mode === 'geofence' && ! $wfhGeofenceExemption;
        if ($wfhGeofenceExemption && $mode === 'geofence' && $wfh['record_gps']) {
            $requiresGps = true;
        }
        $requiresBiometric = in_array($mode, ['biometric', 'gps_and_biometric'], true);

        if ($requiresGps) {
            if ($latitude === null || $longitude === null) {
                return $this->fail($base, 'gps_coordinates_required');
            }

            $this->geofenceService->assertValidCoordinates($latitude, $longitude);

            if ($accuracy !== null && $accuracy > $policy['max_accuracy_meters']) {
                return $this->fail($base, 'gps_accuracy_too_low', [
                    'accuracy_meters' => $accuracy,
                    'max_accuracy_meters' => $policy['max_accuracy_meters'],
                ]);
            }
        }

        if ($requiresGeofence || ($mode === 'gps' && $latitude !== null && $longitude !== null)) {
            // GPS-only mode still records geofence match metadata when available,
            // but only geofence mode fails when outside / missing fences.
            $geo = $this->geofenceService->validateCoordinates($employee, $latitude, $longitude, $at);
            $base['metadata']['geofence'] = [
                'inside' => $geo['inside'],
                'reason' => $geo['reason'],
                'distance_meters' => $geo['distance_meters'],
                'candidates' => $geo['candidates'],
            ];
            $base['geofence_id'] = $geo['geofence']?->id;

            if ($requiresGeofence) {
                if (! $geo['inside']) {
                    return $this->fail($base, $geo['reason'] ?? 'outside_geofence', [
                        'geofence' => $base['metadata']['geofence'],
                    ]);
                }
            }
        }

        if ($requiresBiometric) {
            $biometric = $this->biometricIntegrationService->verify($employee, $context);
            $base['metadata']['biometric'] = $biometric['metadata'];

            if (! $biometric['verified']) {
                return $this->fail($base, $biometric['reason'] ?? 'biometric_failed', [
                    'biometric' => $biometric,
                ]);
            }
        }

        // Mark WFH geofence exemption on successful verify paths that still
        // recorded GPS / biometric without enforcing office fence.
        if ($wfhGeofenceExemption) {
            $base['metadata']['wfh_exemption'] = true;
            if ($mode === 'geofence') {
                $base['metadata']['geofence_skipped'] = true;
            }
        }

        $base['status'] = 'verified';
        $base['passed'] = true;
        $base['reason'] = null;

        return $base;
    }

    /**
     * Persist an immutable verification audit row and optionally attach to a record.
     *
     * @param  array<string, mixed>  $result  from verify()
     */
    public function recordAudit(
        Employee $employee,
        string $event,
        array $result,
        ?User $actor = null,
        ?AttendanceRecord $record = null,
    ): AttendanceVerificationAudit {
        $audit = AttendanceVerificationAudit::query()->create([
            'organization_id' => $employee->organization_id,
            'attendance_record_id' => $record?->id,
            'employee_id' => $employee->id,
            'event' => $event,
            'verification_mode' => $result['mode'],
            'verification_status' => $result['status'],
            'reason' => $result['reason'],
            'latitude' => $result['latitude'],
            'longitude' => $result['longitude'],
            'accuracy_meters' => $result['accuracy_meters'],
            'device_id' => $result['device_id'],
            'geofence_id' => $result['geofence_id'],
            'metadata' => $result['metadata'] ?? [],
            'actor_id' => $actor?->id,
            'verified_at' => now(),
        ]);

        if ($record !== null) {
            $this->auditLogger->log($record, 'attendance_verification_recorded', [
                'event' => $event,
                'verification_status' => $result['status'],
                'verification_mode' => $result['mode'],
                'reason' => $result['reason'],
                'audit_id' => $audit->id,
            ], $actor);
        }

        return $audit;
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    public function attributesForEvent(string $event, array $result): array
    {
        $prefix = $event === 'clock_out' ? 'clock_out_' : 'clock_in_';

        return [
            $prefix.'latitude' => $result['latitude'],
            $prefix.'longitude' => $result['longitude'],
            $prefix.'accuracy_meters' => $result['accuracy_meters'],
            $prefix.'device_id' => $result['device_id'],
            $prefix.'geofence_id' => $result['geofence_id'],
            $prefix.'verification_status' => $result['status'],
            $prefix.'verification_metadata' => [
                'mode' => $result['mode'],
                'reason' => $result['reason'],
                'metadata' => $result['metadata'] ?? [],
            ],
        ];
    }

    /**
     * Verify and throw when policy rejects the attempt.
     * Failed attempts are audited immediately (no live record yet).
     * Successful attempts are returned for the caller to persist on the record.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function assertVerified(
        Employee $employee,
        string $event,
        array $context = [],
        Carbon|string|null $at = null,
        ?User $actor = null,
    ): array {
        $result = $this->verify($employee, $event, $context, $at);

        if (! $result['passed']) {
            $this->recordAudit($employee, $event, $result, $actor);

            throw ValidationException::withMessages([
                'verification' => $this->reasonMessage($result['reason'] ?? 'verification_failed'),
            ]);
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $extraMetadata
     * @return array<string, mixed>
     */
    protected function fail(array $base, string $reason, array $extraMetadata = []): array
    {
        $base['passed'] = false;
        $base['status'] = 'failed';
        $base['reason'] = $reason;
        $base['metadata'] = array_merge($base['metadata'] ?? [], $extraMetadata);

        return $base;
    }

    protected function reasonMessage(string $reason): string
    {
        return match ($reason) {
            'gps_coordinates_required' => __('GPS coordinates are required for attendance verification.'),
            'gps_accuracy_too_low' => __('GPS accuracy is too low for attendance verification.'),
            'outside_geofence' => __('You are outside the allowed attendance geofence.'),
            'no_applicable_geofence' => __('No active geofence is configured for this location.'),
            'biometric_not_provided' => __('Biometric verification is required for attendance.'),
            'biometric_proof_missing' => __('Biometric proof is incomplete.'),
            'biometric_failed' => __('Biometric verification failed.'),
            'device_id_required' => __('A device identifier is required for attendance verification.'),
            default => __('Attendance verification failed.'),
        };
    }

    protected function nullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }
}
