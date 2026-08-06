<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceOvertimeEntry extends Model
{
    use Auditable, BelongsToOrganization;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'organization_id',
        'employee_id',
        'attendance_record_id',
        'attendance_overtime_rule_id',
        'attendance_date',
        'rule_type',
        'minutes',
        'amount',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'minutes' => 'integer',
            'amount' => 'decimal:2',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return list<string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(AttendanceOvertimeRule::class, 'attendance_overtime_rule_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function statusLabel(): string
    {
        return config('hrms.attendance_overtime_entry_statuses.'.$this->status, $this->status);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
