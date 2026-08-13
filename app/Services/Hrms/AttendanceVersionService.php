<?php

namespace App\Services\Hrms;

use App\Models\AttendanceRecord;
use App\Models\AttendanceRecordVersion;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class AttendanceVersionService
{
    public function __construct(
        protected AuditLogger $auditLogger,
    ) {}

    /**
     * Archive the current live record state, then bump the live version.
     * The live row remains authoritative.
     */
    public function archiveAndIncrement(
        AttendanceRecord $record,
        User $actor,
        ?string $changeReason = null,
    ): AttendanceRecordVersion {
        return DB::transaction(function () use ($record, $actor, $changeReason): AttendanceRecordVersion {
            $currentVersion = max(1, (int) ($record->version ?? 1));

            $payload = $this->snapshotPayload($record);

            $version = AttendanceRecordVersion::query()->create([
                'organization_id' => $record->organization_id,
                'attendance_record_id' => $record->id,
                'version' => $currentVersion,
                'employee_id' => $record->employee_id,
                'shift_id' => $record->shift_id,
                'attendance_date' => $record->attendance_date?->toDateString(),
                'clock_in_at' => $record->clock_in_at,
                'clock_out_at' => $record->clock_out_at,
                'status' => $record->status,
                'approval_status' => $record->approval_status ?? 'approved',
                'source' => $record->source ?? 'manual',
                'working_minutes' => (int) ($record->working_minutes ?? 0),
                'break_minutes' => (int) ($record->break_minutes ?? 0),
                'late_minutes' => (int) ($record->late_minutes ?? 0),
                'early_departure_minutes' => (int) ($record->early_departure_minutes ?? 0),
                'overtime_minutes' => (int) ($record->overtime_minutes ?? 0),
                'notes' => $record->notes,
                'change_reason' => $changeReason,
                'changed_by' => $actor->id,
                'payload' => $payload,
                'created_at' => now(),
            ]);

            $record->forceFill([
                'version' => $currentVersion + 1,
            ])->save();

            $this->auditLogger->log($record, 'attendance_version_created', [
                'attendance_record_id' => $record->id,
                'archived_version' => $currentVersion,
                'live_version' => $currentVersion + 1,
                'change_reason' => $changeReason,
            ], $actor);

            return $version;
        });
    }

    public function hasMaterialChange(AttendanceRecord $record, array $nextAttributes): bool
    {
        $tracked = [
            'clock_in_at',
            'clock_out_at',
            'status',
            'approval_status',
            'source',
            'shift_id',
            'working_minutes',
            'break_minutes',
            'late_minutes',
            'early_departure_minutes',
            'overtime_minutes',
            'notes',
            'clock_in_verification_status',
            'clock_out_verification_status',
            'clock_in_latitude',
            'clock_in_longitude',
            'clock_out_latitude',
            'clock_out_longitude',
        ];

        foreach ($tracked as $field) {
            if (! array_key_exists($field, $nextAttributes)) {
                continue;
            }

            $current = $record->getAttribute($field);
            $next = $nextAttributes[$field];

            if ($current instanceof \DateTimeInterface) {
                $current = $current->format('Y-m-d H:i:s');
            }
            if ($next instanceof \DateTimeInterface) {
                $next = $next->format('Y-m-d H:i:s');
            }

            if ((string) $current !== (string) $next) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, mixed> */
    protected function snapshotPayload(AttendanceRecord $record): array
    {
        return [
            'id' => $record->id,
            'employee_id' => $record->employee_id,
            'shift_id' => $record->shift_id,
            'attendance_date' => $record->attendance_date?->toDateString(),
            'clock_in_at' => $record->clock_in_at?->toIso8601String(),
            'clock_out_at' => $record->clock_out_at?->toIso8601String(),
            'status' => $record->status,
            'approval_status' => $record->approval_status ?? 'approved',
            'source' => $record->source,
            'working_minutes' => (int) ($record->working_minutes ?? 0),
            'break_minutes' => (int) ($record->break_minutes ?? 0),
            'late_minutes' => (int) ($record->late_minutes ?? 0),
            'early_departure_minutes' => (int) ($record->early_departure_minutes ?? 0),
            'overtime_minutes' => (int) ($record->overtime_minutes ?? 0),
            'notes' => $record->notes,
            'version' => (int) ($record->version ?? 1),
            'verification' => [
                'clock_in' => [
                    'latitude' => $record->clock_in_latitude,
                    'longitude' => $record->clock_in_longitude,
                    'accuracy_meters' => $record->clock_in_accuracy_meters,
                    'device_id' => $record->clock_in_device_id,
                    'geofence_id' => $record->clock_in_geofence_id,
                    'status' => $record->clock_in_verification_status,
                    'metadata' => $record->clock_in_verification_metadata,
                ],
                'clock_out' => [
                    'latitude' => $record->clock_out_latitude,
                    'longitude' => $record->clock_out_longitude,
                    'accuracy_meters' => $record->clock_out_accuracy_meters,
                    'device_id' => $record->clock_out_device_id,
                    'geofence_id' => $record->clock_out_geofence_id,
                    'status' => $record->clock_out_verification_status,
                    'metadata' => $record->clock_out_verification_metadata,
                ],
            ],
        ];
    }
}
