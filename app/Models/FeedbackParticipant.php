<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\FeedbackParticipantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeedbackParticipant extends Model
{
    /** @use HasFactory<FeedbackParticipantFactory> */
    use Auditable, BelongsToOrganization, HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'feedback_campaign_id',
        'performance_review_id',
        'subject_employee_id',
        'participant_employee_id',
        'external_name',
        'external_email',
        'participant_type',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'feedback_campaign_id' => 'integer',
            'performance_review_id' => 'integer',
            'subject_employee_id' => 'integer',
            'participant_employee_id' => 'integer',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(FeedbackCampaign::class, 'feedback_campaign_id');
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

    public function request(): HasOne
    {
        return $this->hasOne(FeedbackRequest::class, 'feedback_participant_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
