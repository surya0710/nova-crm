<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\AttendanceCorrectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceCorrection extends Model
{
    /** @use HasFactory<AttendanceCorrectionFactory> */
    use Auditable, BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'attendance_record_id',
        'employee_id',
        'requested_clock_in_at',
        'requested_clock_out_at',
        'reason',
        'status',
        'target_version',
        'resulting_version',
        'current_step',
        'requires_hr_approval',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected function casts(): array
    {
        return [
            'requested_clock_in_at' => 'datetime',
            'requested_clock_out_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'target_version' => 'integer',
            'resulting_version' => 'integer',
            'requires_hr_approval' => 'boolean',
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

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function statusLabel(): string
    {
        return config('hrms.attendance_correction_statuses.'.$this->status, $this->status);
    }
}
