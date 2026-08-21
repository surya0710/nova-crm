<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class PriceListItem extends Model
{
    protected $fillable = [
        'price_list_id',
        'product_id',
        'unit_price',
        'min_quantity',
        'max_quantity',
        'tax_inclusive',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'min_quantity' => 'decimal:2',
            'max_quantity' => 'decimal:2',
            'tax_inclusive' => 'boolean',
            'starts_at' => 'date',
            'ends_at' => 'date',
        ];
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function matchesQuantity(float $quantity): bool
    {
        if ($quantity < (float) $this->min_quantity) {
            return false;
        }

        if ($this->max_quantity !== null && $quantity > (float) $this->max_quantity) {
            return false;
        }

        return true;
    }

    public function isEffective(?Carbon $on = null): bool
    {
        $on = ($on ?? now())->startOfDay();

        if ($this->starts_at && $this->starts_at->startOfDay()->gt($on)) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->startOfDay()->lt($on)) {
            return false;
        }

        return true;
    }
}
