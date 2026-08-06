<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\InterviewFeedbackFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InterviewFeedback extends Model
{
    /** @use HasFactory<InterviewFeedbackFactory> */
    use Auditable, BelongsToOrganization, HasFactory, SoftDeletes;

    protected $table = 'interview_feedback';

    protected $fillable = [
        'organization_id',
        'interview_round_id',
        'interview_participant_id',
        'rating',
        'comments',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
        ];
    }

    public function interviewRound(): BelongsTo
    {
        return $this->belongsTo(InterviewRound::class);
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(InterviewParticipant::class, 'interview_participant_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
