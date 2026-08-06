<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceSnapshotRow extends Model
{
    use Auditable, BelongsToOrganization;

    public $timestamps = false;

    protected $fillable = [
        'organization_id',
        'attendance_snapshot_id',
        'attendance_record_id',
        'employee_id',
        'attendance_date',
        'attendance_record_version',
        'status',
        'working_minutes',
        'break_minutes',
        'late_minutes',
        'early_departure_minutes',
        'overtime_minutes',
        'leave_context',
        'payload',
        'payload_hash',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'attendance_record_version' => 'integer',
            'working_minutes' => 'integer',
            'break_minutes' => 'integer',
            'late_minutes' => 'integer',
            'early_departure_minutes' => 'integer',
            'overtime_minutes' => 'integer',
            'leave_context' => 'array',
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(AttendanceSnapshot::class, 'attendance_snapshot_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class);
    }
}
