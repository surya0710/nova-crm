<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\AttendanceRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceRecord extends Model
{
    /** @use HasFactory<AttendanceRecordFactory> */
    use Auditable, BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'employee_id',
        'shift_id',
        'attendance_date',
        'clock_in_at',
        'clock_out_at',
        'status',
        'source',
        'clock_in_latitude',
        'clock_in_longitude',
        'clock_in_accuracy_meters',
        'clock_in_device_id',
        'clock_in_geofence_id',
        'clock_in_verification_status',
        'clock_in_verification_metadata',
        'clock_out_latitude',
        'clock_out_longitude',
        'clock_out_accuracy_meters',
        'clock_out_device_id',
        'clock_out_geofence_id',
        'clock_out_verification_status',
        'clock_out_verification_metadata',
        'working_minutes',
        'break_minutes',
        'late_minutes',
        'early_departure_minutes',
        'overtime_minutes',
        'notes',
        'version',
        'approval_status',
        'locked_at',
        'locked_by',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'clock_in_at' => 'datetime',
            'clock_out_at' => 'datetime',
            'clock_in_latitude' => 'float',
            'clock_in_longitude' => 'float',
            'clock_in_accuracy_meters' => 'integer',
            'clock_in_verification_metadata' => 'array',
            'clock_out_latitude' => 'float',
            'clock_out_longitude' => 'float',
            'clock_out_accuracy_meters' => 'integer',
            'clock_out_verification_metadata' => 'array',
            'working_minutes' => 'integer',
            'break_minutes' => 'integer',
            'late_minutes' => 'integer',
            'early_departure_minutes' => 'integer',
            'overtime_minutes' => 'integer',
            'version' => 'integer',
            'locked_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(HrmsShift::class, 'shift_id');
    }

    public function corrections(): HasMany
    {
        return $this->hasMany(AttendanceCorrection::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(AttendanceRecordVersion::class);
    }

    public function verificationAudits(): HasMany
    {
        return $this->hasMany(AttendanceVerificationAudit::class);
    }

    public function clockInGeofence(): BelongsTo
    {
        return $this->belongsTo(AttendanceGeofence::class, 'clock_in_geofence_id');
    }

    public function clockOutGeofence(): BelongsTo
    {
        return $this->belongsTo(AttendanceGeofence::class, 'clock_out_geofence_id');
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function isLocked(): bool
    {
        return $this->locked_at !== null;
    }

    public function statusLabel(): string
    {
        return config('hrms.attendance_statuses.'.$this->status, $this->status);
    }

    public function sourceLabel(): string
    {
        return config('hrms.attendance_sources.'.$this->source, $this->source);
    }

    public function clockInVerificationStatusLabel(): string
    {
        $status = $this->clock_in_verification_status;

        return $status
            ? config('hrms.attendance_verification_statuses.'.$status, $status)
            : '';
    }

    public function clockOutVerificationStatusLabel(): string
    {
        $status = $this->clock_out_verification_status;

        return $status
            ? config('hrms.attendance_verification_statuses.'.$status, $status)
            : '';
    }
}
