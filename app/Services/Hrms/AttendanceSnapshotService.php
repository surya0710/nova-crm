<?php

namespace App\Services\Hrms;

use App\Models\AttendancePeriod;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSnapshot;
use App\Models\AttendanceSnapshotRow;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceSnapshotService
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected AttendanceCalculationService $calculationService,
        protected LeaveService $leaveService,
    ) {}

    public function generate(AttendancePeriod $period, User $actor): AttendanceSnapshot
    {
        return DB::transaction(function () use ($period, $actor): AttendanceSnapshot {
            $active = $period->activeSnapshot();
            if ($active !== null) {
                $active->update([
                    'status' => AttendanceSnapshot::STATUS_SUPERSEDED,
                    'superseded_at' => now(),
                ]);
                $this->auditLogger->log($active, 'attendance_snapshot_superseded', [
                    'attendance_period_id' => $period->id,
                    'snapshot_version' => $active->snapshot_version,
                ], $actor);
            }

            $nextVersion = (int) ($active?->snapshot_version ?? 0) + 1;

            $records = AttendanceRecord::query()
                ->whereBetween('attendance_date', [
                    $period->start_date->toDateString(),
                    $period->end_date->toDateString(),
                ])
                ->with(['employee', 'shift'])
                ->orderBy('employee_id')
                ->orderBy('attendance_date')
                ->get();

            $rowPayloads = [];
            foreach ($records as $record) {
                $leave = $this->leaveService
                    ->getApprovedLeaveForDate($record->employee, $record->attendance_date)
                    ->map(fn ($application) => [
                        'leave_application_id' => $application->id,
                        'leave_type_id' => $application->leave_type_id,
                        'days' => $application->days,
                        'is_paid' => $application->leaveType?->is_paid,
                    ])
                    ->values()
                    ->all();

                $payload = [
                    'attendance_record_id' => $record->id,
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
                    'attendance_record_version' => (int) ($record->version ?? 1),
                    'leave_context' => $leave,
                ];

                $rowPayloads[] = [
                    'record' => $record,
                    'payload' => $payload,
                    'payload_hash' => hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)),
                    'leave_context' => $leave,
                ];
            }

            $aggregateHash = hash('sha256', json_encode(
                array_map(fn (array $row) => $row['payload'], $rowPayloads),
                JSON_THROW_ON_ERROR
            ));

            $snapshot = AttendanceSnapshot::query()->create([
                'organization_id' => $period->organization_id,
                'attendance_period_id' => $period->id,
                'snapshot_version' => $nextVersion,
                'status' => AttendanceSnapshot::STATUS_ACTIVE,
                'payload_hash' => $aggregateHash,
                'record_count' => count($rowPayloads),
                'generated_by' => $actor->id,
                'generated_at' => now(),
                'meta' => [
                    'period_name' => $period->name,
                    'start_date' => $period->start_date->toDateString(),
                    'end_date' => $period->end_date->toDateString(),
                ],
            ]);

            foreach ($rowPayloads as $row) {
                /** @var AttendanceRecord $record */
                $record = $row['record'];
                AttendanceSnapshotRow::query()->create([
                    'organization_id' => $period->organization_id,
                    'attendance_snapshot_id' => $snapshot->id,
                    'attendance_record_id' => $record->id,
                    'employee_id' => $record->employee_id,
                    'attendance_date' => $record->attendance_date?->toDateString(),
                    'attendance_record_version' => (int) ($record->version ?? 1),
                    'status' => $record->status,
                    'working_minutes' => (int) ($record->working_minutes ?? 0),
                    'break_minutes' => (int) ($record->break_minutes ?? 0),
                    'late_minutes' => (int) ($record->late_minutes ?? 0),
                    'early_departure_minutes' => (int) ($record->early_departure_minutes ?? 0),
                    'overtime_minutes' => (int) ($record->overtime_minutes ?? 0),
                    'leave_context' => $row['leave_context'],
                    'payload' => $row['payload'],
                    'payload_hash' => $row['payload_hash'],
                    'created_at' => now(),
                ]);
            }

            $this->auditLogger->log($snapshot, 'attendance_snapshot_generated', [
                'attendance_period_id' => $period->id,
                'snapshot_version' => $snapshot->snapshot_version,
                'record_count' => $snapshot->record_count,
                'payload_hash' => $snapshot->payload_hash,
            ], $actor);

            return $snapshot->load('rows');
        });
    }

    public function assertImmutable(AttendanceSnapshot $snapshot): void
    {
        if ($snapshot->isSuperseded()) {
            throw ValidationException::withMessages([
                'snapshot' => __('Superseded attendance snapshots cannot be modified.'),
            ]);
        }
    }

    /**
     * @return array{
     *     working_days: int,
     *     overtime_minutes: int,
     *     summary: array<string, int>,
     *     record_count: int,
     *     snapshot_id: int,
     *     snapshot_version: int,
     *     payload_hash: string
     * }
     */
    public function summarizeForEmployee(AttendanceSnapshot $snapshot, int $employeeId): array
    {
        $rows = $snapshot->rows()
            ->where('employee_id', $employeeId)
            ->get();

        return [
            'working_days' => $rows->whereIn('status', ['present', 'late', 'half_day'])->count(),
            'overtime_minutes' => (int) $rows->sum('overtime_minutes'),
            'summary' => $rows->countBy('status')->all(),
            'record_count' => $rows->count(),
            'snapshot_id' => $snapshot->id,
            'snapshot_version' => $snapshot->snapshot_version,
            'payload_hash' => $snapshot->payload_hash,
        ];
    }
}
