<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\FeedbackRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeedbackRequest extends Model
{
    /** @use HasFactory<FeedbackRequestFactory> */
    use Auditable, BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'feedback_campaign_id',
        'feedback_participant_id',
        'performance_review_id',
        'subject_employee_id',
        'participant_employee_id',
        'participant_type',
        'due_date',
        'status',
        'is_anonymous',
        'started_at',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'feedback_campaign_id' => 'integer',
            'feedback_participant_id' => 'integer',
            'performance_review_id' => 'integer',
            'subject_employee_id' => 'integer',
            'participant_employee_id' => 'integer',
            'due_date' => 'date',
            'is_anonymous' => 'boolean',
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(FeedbackCampaign::class, 'feedback_campaign_id');
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(FeedbackParticipant::class, 'feedback_participant_id');
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(PerformanceReview::class, 'performance_review_id');
    }

    public function subjectEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'subject_employee_id');
    }

    public function participantEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'participant_employee_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(FeedbackResponse::class, 'feedback_request_id');
    }

    public function isImmutable(): bool
    {
        return in_array($this->status, config('hrms.feedback.immutable_request_statuses', ['submitted', 'expired', 'cancelled']), true);
    }

    public function isSubmittable(): bool
    {
        return in_array($this->status, config('hrms.feedback.submittable_request_statuses', ['pending', 'started']), true);
    }
}
