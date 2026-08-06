<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\InterviewRoundFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InterviewRound extends Model
{
    /** @use HasFactory<InterviewRoundFactory> */
    use Auditable, BelongsToOrganization, HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'job_application_id',
        'interview_stage_id',
        'round_number',
        'interview_type',
        'scheduled_at',
        'duration_minutes',
        'location',
        'meeting_link',
        'meeting_provider',
        'meeting_id',
        'join_instructions',
        'notes',
        'status',
        'evaluation_template_id',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'round_number' => 'integer',
            'duration_minutes' => 'integer',
        ];
    }

    public function jobApplication(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class);
    }

    public function interviewStage(): BelongsTo
    {
        return $this->belongsTo(InterviewStage::class);
    }

    public function evaluationTemplate(): BelongsTo
    {
        return $this->belongsTo(EvaluationTemplate::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(InterviewParticipant::class);
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(InterviewFeedback::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(CandidateEvaluation::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function statusLabel(): string
    {
        return config('hrms.recruitment.interview_round_statuses.'.$this->status, $this->status);
    }

    public function interviewTypeLabel(): string
    {
        return config('hrms.recruitment.interview_types.'.$this->interview_type, $this->interview_type);
    }
}
