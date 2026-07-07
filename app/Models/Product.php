<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use Auditable, BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'sku',
        'description',
        'type',
        'unit_price',
        'currency',
        'unit',
        'tax_rate',
        'category',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'tax_rate' => 'decimal:2',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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
}
