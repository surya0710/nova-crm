<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\LeaveTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveType extends Model
{
    /** @use HasFactory<LeaveTypeFactory> */
    use Auditable, BelongsToOrganization, HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'name',
        'code',
        'is_paid',
        'requires_approval',
        'requires_hr_approval',
        'allow_half_day',
        'max_days_per_year',
        'allocation_days',
        'carry_forward_allowed',
        'negative_balance_allowed',
        'max_consecutive_days',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_paid' => 'boolean',
            'requires_approval' => 'boolean',
            'requires_hr_approval' => 'boolean',
            'allow_half_day' => 'boolean',
            'max_days_per_year' => 'integer',
            'allocation_days' => 'integer',
            'carry_forward_allowed' => 'boolean',
            'negative_balance_allowed' => 'boolean',
            'max_consecutive_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function balances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(LeaveApplication::class);
    }
}
