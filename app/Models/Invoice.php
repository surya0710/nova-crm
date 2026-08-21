<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\HasAttachments;
use App\Models\Concerns\HasCommercialPartySnapshots;
use App\Models\Scopes\OrganizationScope;
use App\Services\InvoiceCalculationService;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use Auditable, BelongsToOrganization, HasAttachments, HasCommercialPartySnapshots, HasFactory;

    protected $fillable = [
        'organization_id',
        'number',
        'customer_id',
        'quotation_id',
        'sales_order_id',
        'opportunity_id',
        'title',
        'status',
        'issue_date',
        'due_date',
        'currency',
        'subtotal',
        'discount_amount',
        'taxable_amount',
        'tax_total',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'utgst_amount',
        'cess_amount',
        'other_tax_amount',
        'shipping_amount',
        'total',
        'amount_paid',
        'notes',
        'terms',
        'pricing_mode',
        'tax_treatment',
        'place_of_supply',
        'billing_snapshot',
        'shipping_snapshot',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'taxable_amount' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'cgst_amount' => 'decimal:2',
            'sgst_amount' => 'decimal:2',
            'igst_amount' => 'decimal:2',
            'utgst_amount' => 'decimal:2',
            'cess_amount' => 'decimal:2',
            'other_tax_amount' => 'decimal:2',
            'shipping_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'billing_snapshot' => 'array',
            'shipping_snapshot' => 'array',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('payment_date');
    }

    public function adjustmentNotes(): HasMany
    {
        return $this->hasMany(AdjustmentNote::class);
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(AdjustmentNote::class)->where('type', 'credit');
    }

    public function debitNotes(): HasMany
    {
        return $this->hasMany(AdjustmentNote::class)->where('type', 'debit');
    }

    public function creditedAmount(): float
    {
        return round((float) $this->creditNotes()
            ->withoutGlobalScope(OrganizationScope::class)
            ->where('adjustment_notes.organization_id', $this->organization_id)
            ->where('status', 'applied')
            ->sum('applied_amount'), 2);
    }

    public function debitedAmount(): float
    {
        return round((float) $this->debitNotes()
            ->withoutGlobalScope(OrganizationScope::class)
            ->where('adjustment_notes.organization_id', $this->organization_id)
            ->where('status', 'applied')
            ->sum('applied_amount'), 2);
    }

    public function getEffectiveBalanceAttribute(): float
    {
        return round(
            (float) $this->total - (float) $this->amount_paid - $this->creditedAmount() + $this->debitedAmount(),
            2
        );
    }

    public function isOverdue(): bool
    {
        if (in_array($this->status, ['draft', 'cancelled', 'paid', 'overpaid'], true)) {
            return false;
        }

        if ($this->effective_balance <= 0) {
            return false;
        }

        return $this->due_date && $this->due_date->startOfDay()->lt(now()->startOfDay());
    }

    public function agingBucket(?\Illuminate\Support\Carbon $asOf = null): string
    {
        $asOf = ($asOf ?? now())->startOfDay();
        $dueDate = $this->due_date?->startOfDay() ?? $asOf;
        $daysOverdue = $dueDate->lte($asOf) ? (int) $dueDate->diffInDays($asOf) : 0;

        if ($daysOverdue === 0) {
            return 'current';
        }
        if ($daysOverdue <= 30) {
            return '1_30';
        }
        if ($daysOverdue <= 60) {
            return '31_60';
        }
        if ($daysOverdue <= 90) {
            return '61_90';
        }

        return '90_plus';
    }

    public function getCollectionStatusAttribute(): string
    {
        if ($this->status === 'cancelled') {
            return 'cancelled';
        }

        $balance = $this->effective_balance;

        if ($balance <= 0 && (float) $this->amount_paid > 0) {
            return 'paid';
        }

        if ((float) $this->amount_paid > 0 || $this->creditedAmount() > 0) {
            return $this->isOverdue() ? 'overdue' : 'partial';
        }

        return $this->isOverdue() ? 'overdue' : 'unpaid';
    }

    public function isFullyEditable(): bool
    {
        return in_array($this->status, config('invoices.fully_editable_statuses', []), true);
    }

    public function isHeaderEditable(): bool
    {
        return $this->status === 'issued'
            && (float) $this->amount_paid === 0.0
            && ! $this->payments()->exists();
    }

    public function isLocked(): bool
    {
        return ! $this->isFullyEditable() && ! $this->isHeaderEditable();
    }

    public function isDeletable(): bool
    {
        return in_array($this->status, config('invoices.deletable_statuses', []), true);
    }

    public function canIssue(): bool
    {
        return $this->status === 'draft';
    }

    public function canCancel(): bool
    {
        if (in_array($this->status, ['paid', 'overpaid', 'cancelled'], true)) {
            return false;
        }

        if ($this->status === 'partially_paid') {
            return (float) $this->amount_paid === 0.0 && ! $this->payments()->exists();
        }

        return in_array($this->status, ['draft', 'issued'], true);
    }

    /**
     * @return list<string>
     */
    public function allowedTransitions(): array
    {
        return config('invoices.transitions.'.$this->status, []);
    }

    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, config('invoices.transitions.'.$from, []), true);
    }

    public function canAcceptPayment(): bool
    {
        return in_array($this->status, config('payments.payable_invoice_statuses', []), true)
            && (float) $this->total > 0;
    }

    public function recalculateAmountPaid(): void
    {
        $this->amount_paid = round((float) $this->payments()->sum('amount'), 2);
        $this->syncPaymentStatus();
        $this->save();
    }

    public function getStatusLabelAttribute(): string
    {
        return config('invoices.statuses.'.$this->status, ucfirst(str_replace('_', ' ', $this->status)));
    }

    public function getFormattedTotalAttribute(): string
    {
        return number_format((float) $this->total, 2).' '.$this->currency;
    }

    public function getBalanceDueAttribute(): float
    {
        return app(InvoiceCalculationService::class)->balanceDue(
            (float) $this->total,
            (float) $this->amount_paid,
        );
    }

    public function getFormattedBalanceDueAttribute(): string
    {
        return number_format($this->balance_due, 2).' '.$this->currency;
    }

    public function getFormattedEffectiveBalanceAttribute(): string
    {
        return number_format($this->effective_balance, 2).' '.$this->currency;
    }

    public static function generateNumber(Organization $organization): string
    {
        $year = now()->format('Y');
        $prefix = "INV-{$year}-";

        $lastNumber = static::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->where('number', 'like', $prefix.'%')
            ->orderByDesc('number')
            ->value('number');

        $sequence = $lastNumber
            ? ((int) substr($lastNumber, strlen($prefix))) + 1
            : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public static function calculateTotals(array $items, array $context = []): array
    {
        return app(InvoiceCalculationService::class)->calculateTotals($items, $context);
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public static function calculateLine(array $item, array $context = []): array
    {
        return app(InvoiceCalculationService::class)->calculateLine($item, $context);
    }

    public function syncPaymentStatus(): void
    {
        if ($this->status === 'cancelled') {
            return;
        }

        $paid = (float) $this->amount_paid;
        $total = (float) $this->total;

        if ($paid > $total && $total > 0) {
            $this->status = 'overpaid';
        } elseif ($paid >= $total && $total > 0) {
            $this->status = 'paid';
        } elseif ($paid > 0) {
            $this->status = 'partially_paid';
        } elseif (in_array($this->status, ['partially_paid', 'paid', 'overpaid'], true)) {
            $this->status = 'issued';
        }
    }

    public function getPaymentStatusAttribute(): string
    {
        $paid = (float) $this->amount_paid;
        $total = (float) $this->total;

        if ($paid > $total && $total > 0) {
            return 'overpaid';
        }

        if ($paid >= $total && $total > 0) {
            return 'paid';
        }

        if ($paid > 0) {
            return 'partial';
        }

        return 'unpaid';
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return config('payments.invoice_statuses.'.$this->payment_status, ucfirst($this->payment_status));
    }

    public function getOverpaidAmountAttribute(): float
    {
        return max(0, round((float) $this->amount_paid - (float) $this->total, 2));
    }
}
