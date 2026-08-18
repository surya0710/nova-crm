<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\HasAttachments;
use App\Models\Concerns\HasCommercialPartySnapshots;
use App\Services\QuotationCalculationService;
use Database\Factories\QuotationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Quotation extends Model
{
    /** @use HasFactory<QuotationFactory> */
    use Auditable, BelongsToOrganization, HasAttachments, HasCommercialPartySnapshots, HasFactory;

    protected $fillable = [
        'organization_id',
        'number',
        'customer_id',
        'opportunity_id',
        'title',
        'status',
        'issue_date',
        'valid_until',
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
            'valid_until' => 'date',
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
            'billing_snapshot' => 'array',
            'shipping_snapshot' => 'array',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
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
        return $this->hasMany(QuotationItem::class)->orderBy('sort_order');
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class)->latestOfMany();
    }

    public function canConvert(): bool
    {
        return $this->status === 'accepted';
    }

    public function isEditable(): bool
    {
        return in_array($this->status, config('quotations.editable_statuses', []), true);
    }

    public function isDeletable(): bool
    {
        return in_array($this->status, config('quotations.deletable_statuses', []), true);
    }

    public function isFinal(): bool
    {
        return $this->allowedTransitions() === [];
    }

    /**
     * @return list<string>
     */
    public function allowedTransitions(): array
    {
        return config('quotations.transitions.'.$this->status, []);
    }

    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, config('quotations.transitions.'.$from, []), true);
    }

    public function getStatusLabelAttribute(): string
    {
        return config('quotations.statuses.'.$this->status, ucfirst($this->status));
    }

    public function getFormattedTotalAttribute(): string
    {
        return number_format((float) $this->total, 2).' '.$this->currency;
    }

    public static function generateNumber(Organization $organization): string
    {
        $year = now()->format('Y');
        $prefix = "QUO-{$year}-";

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
        return app(QuotationCalculationService::class)->calculateTotals($items, $context);
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public static function calculateLine(array $item, array $context = []): array
    {
        return app(QuotationCalculationService::class)->calculateLine($item, $context);
    }
}
