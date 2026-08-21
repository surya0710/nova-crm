<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use Auditable, BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'sku',
        'description',
        'type',
        'unit_price',
        'cost_price',
        'currency',
        'unit',
        'tax_rate',
        'default_discount_percent',
        'hsn_sac',
        'tax_inclusive',
        'cess_rate',
        'category',
        'product_category_id',
        'status',
        'custom_fields',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'default_discount_percent' => 'decimal:2',
            'cess_rate' => 'decimal:2',
            'tax_inclusive' => 'boolean',
            'custom_fields' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function priceListItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PriceListItem::class);
    }

    public function priceHistories(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProductPriceHistory::class)->latest();
    }

    public function getStatusLabelAttribute(): string
    {
        return config('products.statuses.'.$this->status, ucfirst($this->status));
    }

    public function getTypeLabelAttribute(): string
    {
        return config('products.types.'.$this->type, ucfirst($this->type));
    }

    public function getUnitLabelAttribute(): ?string
    {
        if (! $this->unit) {
            return null;
        }

        return config('products.units.'.$this->unit, $this->unit);
    }

    public function getFormattedPriceAttribute(): string
    {
        return number_format((float) $this->unit_price, 2).' '.$this->currency;
    }

    public function getHsnSacLabelAttribute(): string
    {
        return $this->type === 'service' ? __('SAC') : __('HSN');
    }

    /**
     * @return array<string, mixed>
     */
    public function catalogPayload(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'description' => $this->description ?: $this->name,
            'unit' => $this->unit,
            'unit_price' => (float) $this->unit_price,
            'tax_rate' => (float) $this->tax_rate,
            'default_discount_percent' => (float) $this->default_discount_percent,
            'hsn_sac' => $this->hsn_sac,
            'tax_inclusive' => (bool) $this->tax_inclusive,
            'cess_rate' => (float) $this->cess_rate,
        ];
    }
}
