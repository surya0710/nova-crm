<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\HrmsShiftFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HrmsShift extends Model
{
    /** @use HasFactory<HrmsShiftFactory> */
    use Auditable, BelongsToOrganization, HasFactory, SoftDeletes;

    protected $table = 'hrms_shifts';

    protected $fillable = [
        'organization_id',
        'name',
        'code',
        'start_time',
        'end_time',
        'break_minutes',
        'grace_period_minutes',
        'working_hours',
        'minimum_working_minutes',
        'overtime_threshold_minutes',
        'is_overnight',
        'is_active',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'break_minutes' => 'integer',
            'grace_period_minutes' => 'integer',
            'working_hours' => 'decimal:2',
            'minimum_working_minutes' => 'integer',
            'overtime_threshold_minutes' => 'integer',
            'is_overnight' => 'boolean',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(EmployeeShiftAssignment::class, 'shift_id');
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'shift_id');
    }
}
