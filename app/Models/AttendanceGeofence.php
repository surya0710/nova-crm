<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\AttendanceGeofenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceGeofence extends Model
{
    /** @use HasFactory<AttendanceGeofenceFactory> */
    use Auditable, BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'branch_id',
        'name',
        'latitude',
        'longitude',
        'radius_meters',
        'is_active',
        'effective_from',
        'effective_to',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'radius_meters' => 'integer',
            'is_active' => 'boolean',
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function verificationAudits(): HasMany
    {
        return $this->hasMany(AttendanceVerificationAudit::class, 'geofence_id');
    }

    public function isOrganizationWide(): bool
    {
        return $this->branch_id === null;
    }

    public function isEffectiveOn(\DateTimeInterface|string|null $date = null): bool
    {
        $day = $date === null
            ? now()->startOfDay()
            : \Carbon\Carbon::parse($date)->startOfDay();

        if ($this->effective_from !== null && $day->lt($this->effective_from->copy()->startOfDay())) {
            return false;
        }

        if ($this->effective_to !== null && $day->gt($this->effective_to->copy()->startOfDay())) {
            return false;
        }

        return true;
    }
}
