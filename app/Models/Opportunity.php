<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\HasAttachments;
use Database\Factories\OpportunityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Opportunity extends Model
{
    /** @use HasFactory<OpportunityFactory> */
    use Auditable, BelongsToOrganization, HasAttachments, HasFactory;

    protected $fillable = [
        'organization_id',
        'title',
        'customer_id',
        'lead_id',
        'stage',
        'amount',
        'currency',
        'probability',
        'expected_close_date',
        'won_at',
        'lost_reason',
        'source',
        'competitor',
        'next_activity_at',
        'next_activity_type',
        'next_activity_subject',
        'description',
        'custom_fields',
        'assigned_to',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'probability' => 'integer',
            'expected_close_date' => 'date',
            'won_at' => 'date',
            'next_activity_at' => 'datetime',
            'custom_fields' => 'array',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function marketingAttribution(): HasOne
    {
        return $this->hasOne(MarketingAttribution::class)->where('is_primary', true);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(OpportunityNote::class)->latest();
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(OpportunityContact::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(OpportunityProduct::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(CrmActivity::class)->latest('occurred_at');
    }

    public function tasks(): MorphMany
    {
        return $this->morphMany(Task::class, 'taskable')->latest();
    }

    public function weightedAmount(): float
    {
        return round(((float) $this->amount) * ((int) $this->probability) / 100, 2);
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class)->latest('issue_date');
    }

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class)->latest('order_date');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class)->latest('issue_date');
    }

    public function getStageLabelAttribute(): string
    {
        return config('pipeline.stages.'.$this->stage, ucfirst(str_replace('_', ' ', $this->stage)));
    }

    public function isOpen(): bool
    {
        return ! in_array($this->stage, config('pipeline.closed_stages', ['closed_won', 'closed_lost']), true);
    }

    public function isWon(): bool
    {
        return $this->stage === 'closed_won';
    }

    public function isLost(): bool
    {
        return $this->stage === 'closed_lost';
    }

    public function isClosed(): bool
    {
        return ! $this->isOpen();
    }
}
