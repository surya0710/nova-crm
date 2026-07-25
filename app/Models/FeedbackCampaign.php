<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\FeedbackCampaignFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeedbackCampaign extends Model
{
    /** @use HasFactory<FeedbackCampaignFactory> */
    use Auditable, BelongsToOrganization, HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'performance_cycle_id',
        'feedback_template_id',
        'name',
        'description',
        'start_date',
        'due_date',
        'is_anonymous',
        'status',
        'summary',
        'summary_generated_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'performance_cycle_id' => 'integer',
            'feedback_template_id' => 'integer',
            'start_date' => 'date',
            'due_date' => 'date',
            'is_anonymous' => 'boolean',
            'summary' => 'array',
            'summary_generated_at' => 'datetime',
            'created_by' => 'integer',
        ];
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(PerformanceCycle::class, 'performance_cycle_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(FeedbackTemplate::class, 'feedback_template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(FeedbackParticipant::class, 'feedback_campaign_id');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(FeedbackRequest::class, 'feedback_campaign_id');
    }

    public function isEditable(): bool
    {
        return in_array($this->status, config('hrms.feedback.editable_campaign_statuses', ['draft', 'scheduled']), true);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isClosed(): bool
    {
        return in_array($this->status, ['closed', 'archived'], true);
    }
}
