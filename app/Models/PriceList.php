<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\PriceListFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class PriceList extends Model
{
    /** @use HasFactory<PriceListFactory> */
    use Auditable, BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'currency',
        'is_default',
        'status',
        'starts_at',
        'ends_at',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'starts_at' => 'date',
            'ends_at' => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PriceListItem::class);
    }

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'customer_price_lists')
            ->withPivot('priority')
            ->withTimestamps();
    }

    public function isActive(?Carbon $on = null): bool
    {
        if ($this->status !== 'active') {
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

    public function getStatusLabelAttribute(): string
    {
        return config('price_lists.statuses.'.$this->status, ucfirst($this->status));
    }
}
