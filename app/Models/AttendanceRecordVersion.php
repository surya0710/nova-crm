<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceRecordVersion extends Model
{
    use Auditable, BelongsToOrganization;

    public $timestamps = false;

    protected $fillable = [
        'organization_id',
        'attendance_record_id',
        'version',
        'employee_id',
        'shift_id',
        'attendance_date',
        'clock_in_at',
        'clock_out_at',
        'status',
        'approval_status',
        'source',
        'working_minutes',
        'break_minutes',
        'late_minutes',
        'early_departure_minutes',
        'overtime_minutes',
        'notes',
        'change_reason',
        'changed_by',
        'payload',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'clock_in_at' => 'datetime',
            'clock_out_at' => 'datetime',
            'version' => 'integer',
            'working_minutes' => 'integer',
            'break_minutes' => 'integer',
            'late_minutes' => 'integer',
            'early_departure_minutes' => 'integer',
            'overtime_minutes' => 'integer',
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(HrmsShift::class, 'shift_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
