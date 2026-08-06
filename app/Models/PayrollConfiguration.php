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
        'salary_mode',
        'salary_credit_day',
        'auto_generate',
        'auto_generate_payroll',
        'reminder_days_before_credit',
        'attendance_freeze_day',
    ];

    protected function casts(): array
    {
        return [
            'week_off_days' => 'array',
            'working_days_per_month' => 'integer',
            'salary_credit_day' => 'integer',
            'auto_generate' => 'boolean',
            'auto_generate_payroll' => 'boolean',
            'reminder_days_before_credit' => 'integer',
            'attendance_freeze_day' => 'integer',
        ];
    }

    public function getAutoGenerateAttribute(): bool
    {
        if (array_key_exists('auto_generate', $this->attributes)) {
            return (bool) $this->attributes['auto_generate'];
        }

        return (bool) ($this->attributes['auto_generate_payroll'] ?? false);
    }

    public function setAutoGenerateAttribute(mixed $value): void
    {
        $bool = (bool) $value;
        if (array_key_exists('auto_generate_payroll', $this->attributes) || ! array_key_exists('auto_generate', $this->attributes)) {
            // Prefer legacy column when that is what the schema has.
            if (\Illuminate\Support\Facades\Schema::hasColumn($this->getTable(), 'auto_generate_payroll')) {
                $this->attributes['auto_generate_payroll'] = $bool;

                return;
            }
        }

        $this->attributes['auto_generate'] = $bool;
    }
}
