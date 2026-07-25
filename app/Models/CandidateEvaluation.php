<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\CandidateEvaluationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CandidateEvaluation extends Model
{
    /** @use HasFactory<CandidateEvaluationFactory> */
    use Auditable, BelongsToOrganization, HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'interview_round_id',
        'interview_participant_id',
        'evaluation_template_id',
        'overall_rating',
        'recommendation',
        'strengths',
        'concerns',
        'summary',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'overall_rating' => 'decimal:2',
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

    public function evaluationTemplate(): BelongsTo
    {
        return $this->belongsTo(EvaluationTemplate::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(EvaluationResponse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function recommendationLabel(): string
    {
        return config('hrms.recruitment.evaluation_recommendations.'.$this->recommendation, $this->recommendation ?? '—');
    }

    public function statusLabel(): string
    {
        return config('hrms.recruitment.evaluation_statuses.'.$this->status, $this->status);
    }
}
