<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\EmployeeSettlementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeSettlement extends Model
{
    /** @use HasFactory<EmployeeSettlementFactory> */
    use Auditable, BelongsToOrganization, HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'employee_id',
        'employee_exit_process_id',
        'settlement_number',
        'status',
        'pending_salary',
        'leave_encashment',
        'loan_recovery',
        'advance_recovery',
        'reimbursements',
        'asset_deductions',
        'statutory_deductions',
        'net_settlement',
        'statement',
        'notes',
        'completed_by',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'pending_salary' => 'decimal:2',
            'leave_encashment' => 'decimal:2',
            'loan_recovery' => 'decimal:2',
            'advance_recovery' => 'decimal:2',
            'reimbursements' => 'decimal:2',
            'asset_deductions' => 'decimal:2',
            'statutory_deductions' => 'decimal:2',
            'net_settlement' => 'decimal:2',
            'statement' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function exitProcess(): BelongsTo
    {
        return $this->belongsTo(EmployeeExitProcess::class, 'employee_exit_process_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
