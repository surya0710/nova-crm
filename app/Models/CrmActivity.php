<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\CrmActivityFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmActivity extends Model
{
    /** @use HasFactory<CrmActivityFactory> */
    use Auditable, BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'customer_id',
        'contact_id',
        'opportunity_id',
        'type',
        'subject',
        'body',
        'occurred_at',
        'due_at',
        'duration_minutes',
        'direction',
        'outcome',
        'status',
        'priority',
        'completed_at',
        'assigned_to',
        'created_by',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
            'duration_minutes' => 'integer',
            'metadata' => 'array',
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

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return config('crm_activities.types.'.$this->type, ucfirst(str_replace('_', ' ', (string) $this->type)));
    }

    public function getStatusLabelAttribute(): string
    {
        return config('crm_activities.statuses.'.$this->status, ucfirst((string) $this->status));
    }

    public function getPriorityLabelAttribute(): ?string
    {
        if (! $this->priority) {
            return null;
        }

        return config('crm_activities.priorities.'.$this->priority, ucfirst($this->priority));
    }

    public function getDirectionLabelAttribute(): ?string
    {
        if (! $this->direction) {
            return null;
        }

        return config('crm_activities.directions.'.$this->direction, ucfirst($this->direction));
    }

    public function getOutcomeLabelAttribute(): ?string
    {
        if (! $this->outcome) {
            return null;
        }

        return config('crm_activities.outcomes.'.$this->outcome, ucfirst(str_replace('_', ' ', $this->outcome)));
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isOverdue(): bool
    {
        return $this->isOpen()
            && $this->due_at !== null
            && $this->due_at->isPast();
    }

    public function scopeMine(Builder $query, int $userId): Builder
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('status', 'open')
            ->whereNotNull('due_at')
            ->where('due_at', '>=', now());
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', 'open')
            ->whereNotNull('due_at')
            ->where('due_at', '<', now());
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }
}
