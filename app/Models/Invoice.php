<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\HasAttachments;
use App\Services\InvoiceCalculationService;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use Auditable, BelongsToOrganization, HasAttachments, HasFactory;

    protected $fillable = [
        'organization_id',
        'number',
        'customer_id',
        'quotation_id',
        'opportunity_id',
        'title',
        'status',
        'issue_date',
        'due_date',
        'currency',
        'subtotal',
        'discount_amount',
        'tax_total',
        'total',
        'amount_paid',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
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
        if (in_array($this->status, ['paid', 'cancelled'], true)) {
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
            && (float) $this->total > 0
            && $this->balance_due > 0;
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
     * @return array{subtotal: float, discount_amount: float, tax_total: float, total: float, items: array<int, array<string, mixed>>}
     */
    public static function calculateTotals(array $items): array
    {
        return app(InvoiceCalculationService::class)->calculateTotals($items);
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    public static function calculateLine(array $item): array
    {
        return app(InvoiceCalculationService::class)->calculateLine($item);
    }

    public function syncPaymentStatus(): void
    {
        if ($this->status === 'cancelled') {
            return;
        }

        $paid = (float) $this->amount_paid;
        $total = (float) $this->total;

        if ($paid >= $total && $total > 0) {
            $this->status = 'paid';
        } elseif ($paid > 0) {
            $this->status = 'partially_paid';
        } elseif (in_array($this->status, ['partially_paid', 'paid'], true)) {
            $this->status = 'issued';
        }
    }
}
