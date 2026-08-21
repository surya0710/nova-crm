<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class DiscountRule extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'name',
        'type',
        'value',
        'product_id',
        'customer_id',
        'min_quantity',
        'starts_at',
        'ends_at',
        'is_active',
        'priority',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'min_quantity' => 'decimal:2',
            'starts_at' => 'date',
            'ends_at' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isEffective(?Carbon $on = null): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $on = ($on ?? now())->startOfDay();

        if ($this->starts_at && $this->starts_at->startOfDay()->gt($on)) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->startOfDay()->lt($on)) {
            return false;
        }

        return true;
    }

    public function discountFor(float $unitPrice, float $quantity): float
    {
        if ($quantity < (float) $this->min_quantity) {
            return 0.0;
        }

        if ($this->type === 'fixed') {
            return min(100.0, round(((float) $this->value / max($unitPrice, 0.01)) * 100, 2));
        }

        return min(100.0, (float) $this->value);
    }
}
