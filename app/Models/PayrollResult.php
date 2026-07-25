<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\PayrollResultFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollResult extends Model
{
    /** @use HasFactory<PayrollResultFactory> */
    use Auditable, BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'payroll_run_id',
        'employee_id',
        'gross_salary',
        'total_earnings',
        'total_deductions',
        'net_salary',
        'working_days',
        'payable_days',
        'overtime_minutes',
        'overtime_amount',
        'snapshot',
        'calculation_hash',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'gross_salary' => 'decimal:2',
            'total_earnings' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'working_days' => 'decimal:2',
            'payable_days' => 'decimal:2',
            'overtime_minutes' => 'integer',
            'overtime_amount' => 'decimal:2',
            'snapshot' => 'array',
            'version' => 'integer',
        ];
    }

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
