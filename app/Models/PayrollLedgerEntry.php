<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\PayrollLedgerEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollLedgerEntry extends Model
{
    /** @use HasFactory<PayrollLedgerEntryFactory> */
    use Auditable, BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'payroll_run_id',
        'payroll_result_id',
        'employee_id',
        'account_code',
        'account_name',
        'entry_type',
        'amount',
        'currency',
        'description',
        'is_reversal',
        'reverses_entry_id',
        'meta',
        'generated_by',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'is_reversal' => 'boolean',
            'meta' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function payrollResult(): BelongsTo
    {
        return $this->belongsTo(PayrollResult::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function reversesEntry(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reverses_entry_id');
    }

    public function isDebit(): bool
    {
        return $this->entry_type === 'debit';
    }

    public function isCredit(): bool
    {
        return $this->entry_type === 'credit';
    }
}
