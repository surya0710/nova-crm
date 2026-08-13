<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceVerificationAudit extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'attendance_record_id',
        'employee_id',
        'event',
        'verification_mode',
        'verification_status',
        'reason',
        'latitude',
        'longitude',
        'accuracy_meters',
        'device_id',
        'geofence_id',
        'metadata',
        'actor_id',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'accuracy_meters' => 'integer',
            'metadata' => 'array',
            'verified_at' => 'datetime',
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

    public function geofence(): BelongsTo
    {
        return $this->belongsTo(AttendanceGeofence::class, 'geofence_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function statusLabel(): string
    {
        return config('hrms.attendance_verification_statuses.'.$this->verification_status, $this->verification_status);
    }
}
