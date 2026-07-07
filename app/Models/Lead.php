<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\HasAttachments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Lead extends Model
{
    /** @use HasFactory<\Database\Factories\LeadFactory> */
    use Auditable, BelongsToOrganization, HasAttachments, HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'company',
        'email',
        'phone',
        'source',
        'industry',
        'budget',
        'priority',
        'assigned_to',
        'status',
        'next_follow_up_at',
        'next_follow_up_note',
        'follow_up_alerted_at',
        'tags',
        'custom_fields',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'budget' => 'decimal:2',
            'tags' => 'array',
            'custom_fields' => 'array',
            'next_follow_up_at' => 'datetime',
            'follow_up_alerted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (Lead $lead) {
            if ($lead->isDirty('next_follow_up_at')) {
                $lead->follow_up_alerted_at = null;
            }

            if ($lead->next_follow_up_at === null) {
                $lead->next_follow_up_note = null;
                $lead->follow_up_alerted_at = null;
            }
        });
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
        return $this->hasMany(LeadNote::class)->latest();
    }

    public function tasks(): MorphMany
    {
        return $this->morphMany(Task::class, 'taskable')->latest();
    }

    public function getStatusLabelAttribute(): string
    {
        return config('leads.statuses.'.$this->status, ucfirst(str_replace('_', ' ', $this->status)));
    }

    public function getSourceLabelAttribute(): string
    {
        return config('leads.sources.'.$this->source, ucfirst(str_replace('_', ' ', $this->source)));
    }

    public function getPriorityLabelAttribute(): string
    {
        return config('leads.priorities.'.$this->priority, ucfirst($this->priority));
    }

    public function isOpen(): bool
    {
        return ! in_array($this->status, ['won', 'lost'], true);
    }

    public function hasFollowUpScheduled(): bool
    {
        return $this->next_follow_up_at !== null;
    }

    public function isFollowUpDue(): bool
    {
        return $this->hasFollowUpScheduled()
            && $this->next_follow_up_at->lte(now())
            && $this->isOpen();
    }

    public function needsFollowUpAlert(): bool
    {
        if (! $this->isFollowUpDue()) {
            return false;
        }

        if ($this->follow_up_alerted_at === null) {
            return true;
        }

        return $this->follow_up_alerted_at->lt($this->next_follow_up_at);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Lead>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Lead>
     */
    public function scopeDueForFollowUpAlert($query)
    {
        return $query
            ->whereNotNull('next_follow_up_at')
            ->where('next_follow_up_at', '<=', now())
            ->whereNotIn('status', ['won', 'lost'])
            ->where(function ($q) {
                $q->whereNull('follow_up_alerted_at')
                    ->orWhereColumn('follow_up_alerted_at', '<', 'next_follow_up_at');
            });
    }
}
