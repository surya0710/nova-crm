<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollJournalLine extends Model
{
    use Auditable, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'payroll_journal_id',
        'payroll_ledger_entry_id',
        'employee_id',
        'account_code',
        'account_name',
        'entry_type',
        'amount',
        'description',
        'line_order',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'line_order' => 'integer',
        ];
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(PayrollJournal::class, 'payroll_journal_id');
    }

    public function ledgerEntry(): BelongsTo
    {
        return $this->belongsTo(PayrollLedgerEntry::class, 'payroll_ledger_entry_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
