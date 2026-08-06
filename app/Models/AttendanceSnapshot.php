<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceSnapshot extends Model
{
    use Auditable, BelongsToOrganization;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUPERSEDED = 'superseded';

    protected $fillable = [
        'organization_id',
        'attendance_period_id',
        'snapshot_version',
        'status',
        'payload_hash',
        'record_count',
        'generated_by',
        'generated_at',
        'superseded_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_version' => 'integer',
            'record_count' => 'integer',
            'generated_at' => 'datetime',
            'superseded_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(AttendancePeriod::class, 'attendance_period_id');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(AttendanceSnapshotRow::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isSuperseded(): bool
    {
        return $this->status === self::STATUS_SUPERSEDED;
    }
}
