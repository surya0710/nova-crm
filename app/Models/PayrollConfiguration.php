<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\PayrollConfigurationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollConfiguration extends Model
{
    /** @use HasFactory<PayrollConfigurationFactory> */
    use Auditable, BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'payroll_frequency',
        'currency',
        'working_days_per_month',
        'week_off_days',
        'overtime_handling',
        'rounding_policy',
    ];

    protected function casts(): array
    {
        return [
            'week_off_days' => 'array',
            'working_days_per_month' => 'integer',
        ];
    }
}
