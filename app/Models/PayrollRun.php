<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\PayrollRunFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollRun extends Model
{
    /** @use HasFactory<PayrollRunFactory> */
    use Auditable, BelongsToOrganization, HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'payroll_period_id',
        'status',
        'started_at',
        'completed_at',
        'triggered_by',
        'employee_count',
        'success_count',
        'error_count',
        'engine_version',
        'payment_reference',
        'payment_date',
        'paid_at',
        'paid_by',
        'payment_notes',
        'custom_fields',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'employee_count' => 'integer',
            'success_count' => 'integer',
            'error_count' => 'integer',
            'payment_date' => 'date',
            'paid_at' => 'datetime',
            'custom_fields' => 'array',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function results(): HasMany
    {
        return $this->hasMany(PayrollResult::class);
    }

    public function validationErrors(): HasMany
    {
        return $this->hasMany(PayrollValidationError::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(PayrollApproval::class);
    }

    public function publication(): HasOne
    {
        return $this->hasOne(PayrollPublication::class);
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(PayrollLedgerEntry::class);
    }

    public function journals(): HasMany
    {
        return $this->hasMany(PayrollJournal::class);
    }

    public function bankExports(): HasMany
    {
        return $this->hasMany(PayrollBankExport::class);
    }

    public function reversal(): HasOne
    {
        return $this->hasOne(PayrollReversal::class);
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function canRecalculate(): bool
    {
        return in_array($this->status, ['draft', 'running'], true);
    }

    public function isImmutable(): bool
    {
        return in_array($this->status, ['calculated', 'approved', 'published', 'paid', 'reversed'], true);
    }

    public function canApprove(): bool
    {
        return $this->status === 'calculated';
    }

    public function canPublish(): bool
    {
        return $this->status === 'approved';
    }

    public function canPay(): bool
    {
        return $this->status === 'published';
    }

    public function isPublished(): bool
    {
        return in_array($this->status, ['published', 'paid'], true);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isReversed(): bool
    {
        return $this->status === 'reversed';
    }

    public function canReverse(): bool
    {
        return in_array($this->status, ['published', 'paid'], true);
    }
}
