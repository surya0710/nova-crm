<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\PayrollReversalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollReversal extends Model
{
    /** @use HasFactory<PayrollReversalFactory> */
    use Auditable, BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'payroll_run_id',
        'reversal_number',
        'reason',
        'reversing_journal_id',
        'meta',
        'reversed_by',
        'reversed_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'reversed_at' => 'datetime',
        ];
    }

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function reversingJournal(): BelongsTo
    {
        return $this->belongsTo(PayrollJournal::class, 'reversing_journal_id');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }
}
