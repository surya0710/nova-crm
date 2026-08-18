<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\HasAttachments;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use Auditable, BelongsToOrganization, HasAttachments, HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'company',
        'email',
        'phone',
        'website',
        'industry',
        'status',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country',
        'tax_number',
        'gstin',
        'pan',
        'gst_registration_type',
        'tax_registration_status',
        'billing_state_code',
        'place_of_supply',
        'tax_exemption_status',
        'tax_exemption_reason',
        'default_tax_preference',
        'shipping_same_as_billing',
        'shipping_address_line_1',
        'shipping_address_line_2',
        'shipping_city',
        'shipping_state',
        'shipping_postal_code',
        'shipping_country',
        'assigned_to',
        'lead_id',
        'tags',
        'custom_fields',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'custom_fields' => 'array',
            'shipping_same_as_billing' => 'boolean',
        ];
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function marketingAttribution(): HasOne
    {
        return $this->hasOne(MarketingAttribution::class)->where('is_primary', true);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(CustomerNote::class)->latest();
    }

    public function tasks(): MorphMany
    {
        return $this->morphMany(Task::class, 'taskable')->latest();
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class)->latest('issue_date');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('payment_date');
    }

    public function getStatusLabelAttribute(): string
    {
        return config('customers.statuses.'.$this->status, ucfirst($this->status));
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->company) {
            return $this->company;
        }

        return $this->name;
    }

    public function isTaxExempt(): bool
    {
        return $this->tax_exemption_status === 'exempt';
    }

    public function getGstRegistrationTypeLabelAttribute(): ?string
    {
        if (! $this->gst_registration_type) {
            return null;
        }

        return config('tax.gst_registration_types.'.$this->gst_registration_type, $this->gst_registration_type);
    }

    public function getPlaceOfSupplyLabelAttribute(): ?string
    {
        $code = $this->place_of_supply ?: $this->billing_state_code;

        if (! $code) {
            return $this->state;
        }

        return data_get(config('tax.states.'.$code), 'name', $code);
    }

    public function getBillingAddressLinesAttribute(): array
    {
        return array_values(array_filter([
            $this->address_line_1,
            $this->address_line_2,
            collect([$this->city, $this->state, $this->postal_code])->filter()->join(', ') ?: null,
            $this->country,
        ]));
    }

    public function getShippingAddressLinesAttribute(): array
    {
        if ($this->shipping_same_as_billing) {
            return $this->billing_address_lines;
        }

        return array_values(array_filter([
            $this->shipping_address_line_1,
            $this->shipping_address_line_2,
            collect([$this->shipping_city, $this->shipping_state, $this->shipping_postal_code])->filter()->join(', ') ?: null,
            $this->shipping_country,
        ]));
    }
}
