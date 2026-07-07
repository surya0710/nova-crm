<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\HasAttachments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    /** @use HasFactory<\Database\Factories\InvoiceFactory> */
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

    public function recalculateAmountPaid(): void
    {
        $this->amount_paid = round((float) $this->payments()->sum('amount'), 2);
        $this->syncPaymentStatus();
        $this->save();
    }

    public function getStatusLabelAttribute(): string
    {
        return config('invoices.statuses.'.$this->status, ucfirst($this->status));
    }

    public function getFormattedTotalAttribute(): string
    {
        return number_format((float) $this->total, 2).' '.$this->currency;
    }

    public function getBalanceDueAttribute(): float
    {
        return max(0, round((float) $this->total - (float) $this->amount_paid, 2));
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
        $subtotal = 0.0;
        $discountAmount = 0.0;
        $taxTotal = 0.0;
        $calculatedItems = [];

        foreach ($items as $index => $item) {
            $line = self::calculateLine($item);
            $line['sort_order'] = $index;
            $calculatedItems[] = $line;

            $qty = (float) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];
            $lineSubtotal = round($qty * $unitPrice, 2);

            $subtotal += $lineSubtotal;
            $discountAmount += $line['discount_amount'];
            $taxTotal += $line['tax_amount'];
        }

        return [
            'subtotal' => round($subtotal, 2),
            'discount_amount' => round($discountAmount, 2),
            'tax_total' => round($taxTotal, 2),
            'total' => round($subtotal - $discountAmount + $taxTotal, 2),
            'items' => $calculatedItems,
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    public static function calculateLine(array $item): array
    {
        $quantity = (float) ($item['quantity'] ?? 0);
        $unitPrice = (float) ($item['unit_price'] ?? 0);
        $taxRate = (float) ($item['tax_rate'] ?? 0);
        $discountPercent = (float) ($item['discount_percent'] ?? 0);

        $lineSubtotal = round($quantity * $unitPrice, 2);
        $discountAmount = round($lineSubtotal * ($discountPercent / 100), 2);
        $taxable = $lineSubtotal - $discountAmount;
        $taxAmount = round($taxable * ($taxRate / 100), 2);
        $lineTotal = round($taxable + $taxAmount, 2);

        return [
            'product_id' => $item['product_id'] ?? null,
            'description' => $item['description'],
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'tax_rate' => $taxRate,
            'discount_percent' => $discountPercent,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'line_total' => $lineTotal,
        ];
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
            $this->status = 'partial';
        } elseif (in_array($this->status, ['partial', 'paid'], true)) {
            $this->status = 'sent';
        }
    }
}
