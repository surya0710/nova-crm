<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\CustomerTicketFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerTicket extends Model
{
    /** @use HasFactory<CustomerTicketFactory> */
    use Auditable, BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'customer_id',
        'contact_id',
        'number',
        'subject',
        'body',
        'status',
        'priority',
        'assigned_to',
        'created_by',
        'resolved_at',
        'due_at',
        'first_response_at',
        'closed_at',
        'sla_hours',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
            'due_at' => 'datetime',
            'first_response_at' => 'datetime',
            'closed_at' => 'datetime',
            'sla_hours' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
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
        return $this->hasMany(CustomerTicketNote::class)->latest();
    }

    public function getStatusLabelAttribute(): string
    {
        return config('customer_tickets.statuses.'.$this->status, ucfirst($this->status));
    }

    public function getPriorityLabelAttribute(): string
    {
        return config('customer_tickets.priorities.'.$this->priority, ucfirst($this->priority));
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['open', 'pending'], true);
    }

    public function isClosed(): bool
    {
        return in_array($this->status, ['resolved', 'closed'], true);
    }

    public function isOverdue(): bool
    {
        return $this->isOpen()
            && $this->due_at !== null
            && $this->due_at->isPast();
    }

    public function canReopen(): bool
    {
        return in_array($this->status, ['resolved', 'closed'], true);
    }

    /**
     * @return list<string>
     */
    public function allowedTransitions(): array
    {
        return config('customer_tickets.transitions.'.$this->status, []);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', ['open', 'pending']);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->open()->whereNotNull('due_at')->where('due_at', '<', now());
    }
}
