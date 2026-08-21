<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\HasAttachments;
use App\Models\Concerns\HasCommercialPartySnapshots;
use App\Services\AdjustmentNoteCalculationService;
use Database\Factories\AdjustmentNoteFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdjustmentNote extends Model
{
    /** @use HasFactory<AdjustmentNoteFactory> */
    use Auditable, BelongsToOrganization, HasAttachments, HasCommercialPartySnapshots, HasFactory;

    protected $fillable = [
        'organization_id',
        'number',
        'type',
        'customer_id',
        'invoice_id',
        'opportunity_id',
        'title',
        'status',
        'reason',
        'reason_detail',
        'issue_date',
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
        'applied_amount',
        'applied_at',
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
            'applied_at' => 'datetime',
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
            'applied_amount' => 'decimal:2',
            'billing_snapshot' => 'array',
            'shipping_snapshot' => 'array',
        ];
    }

    public function scopeCredit(Builder $query): Builder
    {
        return $query->where('type', 'credit');
    }

    public function scopeDebit(Builder $query): Builder
    {
        return $query->where('type', 'debit');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
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
        return $this->hasMany(AdjustmentNoteItem::class)->orderBy('sort_order');
    }

    public function isCredit(): bool
    {
        return $this->type === 'credit';
    }

    public function isDebit(): bool
    {
        return $this->type === 'debit';
    }

    public function isEditable(): bool
    {
        return in_array($this->status, config('adjustment_notes.editable_statuses', []), true);
    }

    public function isDeletable(): bool
    {
        return in_array($this->status, config('adjustment_notes.deletable_statuses', []), true);
    }

    public function canIssue(): bool
    {
        return $this->status === 'draft';
    }

    public function canApply(): bool
    {
        return in_array($this->status, config('adjustment_notes.applyable_statuses', []), true)
            && $this->invoice_id
            && (float) $this->total > 0;
    }

    public function canCancel(): bool
    {
        return in_array($this->status, ['draft', 'issued'], true);
    }

    /**
     * @return list<string>
     */
    public function allowedTransitions(): array
    {
        return config('adjustment_notes.transitions.'.$this->status, []);
    }

    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, config('adjustment_notes.transitions.'.$from, []), true);
    }

    public function getStatusLabelAttribute(): string
    {
        return config('adjustment_notes.statuses.'.$this->status, ucfirst($this->status));
    }

    public function getTypeLabelAttribute(): string
    {
        return config('adjustment_notes.types.'.$this->type, ucfirst($this->type));
    }

    public function getReasonLabelAttribute(): ?string
    {
        if (! $this->reason) {
            return null;
        }

        return config('adjustment_notes.reasons.'.$this->reason, $this->reason);
    }

    public function getFormattedTotalAttribute(): string
    {
        return number_format((float) $this->total, 2).' '.$this->currency;
    }

    public function routePrefix(): string
    {
        return $this->isCredit() ? 'credit-notes' : 'debit-notes';
    }

    public static function generateNumber(Organization $organization, string $type): string
    {
        $year = now()->format('Y');
        $code = config('adjustment_notes.prefixes.'.$type, strtoupper(substr($type, 0, 2)));
        $prefix = "{$code}-{$year}-";

        $lastNumber = static::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->where('type', $type)
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
        return app(AdjustmentNoteCalculationService::class)->calculateTotals($items, $context);
    }
}
