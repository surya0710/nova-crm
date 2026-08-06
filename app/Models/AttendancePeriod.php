<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendancePeriod extends Model
{
    use Auditable, BelongsToOrganization;

    public const STATUS_OPEN = 'open';

    public const STATUS_FROZEN = 'frozen';

    public const STATUS_LOCKED = 'locked';

    protected $fillable = [
        'organization_id',
        'name',
        'start_date',
        'end_date',
        'status',
        'payroll_period_id',
        'frozen_at',
        'frozen_by',
        'locked_at',
        'locked_by',
        'reopened_at',
        'reopened_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'frozen_at' => 'datetime',
            'locked_at' => 'datetime',
            'reopened_at' => 'datetime',
        ];
    }

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(AttendanceSnapshot::class);
    }

    public function frozenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'frozen_by');
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function reopenedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reopened_by');
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isFrozen(): bool
    {
        return $this->status === self::STATUS_FROZEN;
    }

    public function isLocked(): bool
    {
        return $this->status === self::STATUS_LOCKED;
    }

    public function statusLabel(): string
    {
        return config('hrms.attendance_period_statuses.'.$this->status, $this->status);
    }

    public function activeSnapshot(): ?AttendanceSnapshot
    {
        return $this->snapshots()
            ->where('status', AttendanceSnapshot::STATUS_ACTIVE)
            ->orderByDesc('snapshot_version')
            ->first();
    }
}
