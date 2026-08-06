<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceOvertimeRule extends Model
{
    use Auditable, BelongsToOrganization;

    public const TYPE_DAILY = 'daily';

    public const TYPE_HOLIDAY = 'holiday';

    public const TYPE_WEEKLY_OFF = 'weekly_off';

    protected $fillable = [
        'organization_id',
        'name',
        'code',
        'rule_type',
        'minimum_minutes',
        'maximum_minutes',
        'round_off_minutes',
        'multiplier',
        'requires_approval',
        'is_active',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'minimum_minutes' => 'integer',
            'maximum_minutes' => 'integer',
            'round_off_minutes' => 'integer',
            'multiplier' => 'decimal:2',
            'requires_approval' => 'boolean',
            'is_active' => 'boolean',
            'meta' => 'array',
        ];
    }

    /**
     * @return list<string>
     */
    public static function types(): array
    {
        return [
            self::TYPE_DAILY,
            self::TYPE_HOLIDAY,
            self::TYPE_WEEKLY_OFF,
        ];
    }

    public function entries(): HasMany
    {
        return $this->hasMany(AttendanceOvertimeEntry::class, 'attendance_overtime_rule_id');
    }

    public function ruleTypeLabel(): string
    {
        return config('hrms.attendance_overtime_rule_types.'.$this->rule_type, $this->rule_type);
    }
}
