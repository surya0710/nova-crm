<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\InterviewParticipantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class InterviewParticipant extends Model
{
    /** @use HasFactory<InterviewParticipantFactory> */
    use Auditable, BelongsToOrganization, HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'interview_round_id',
        'participant_type',
        'employee_id',
        'name',
        'email',
        'role',
        'created_by',
        'updated_by',
    ];

    public function interviewRound(): BelongsTo
    {
        return $this->belongsTo(InterviewRound::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function feedback(): HasOne
    {
        return $this->hasOne(InterviewFeedback::class);
    }

    public function evaluation(): HasOne
    {
        return $this->hasOne(CandidateEvaluation::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function displayName(): string
    {
        if ($this->participant_type === 'internal' && $this->employee) {
            return trim($this->employee->first_name.' '.$this->employee->last_name);
        }

        return $this->name ?? '—';
    }

    public function participantTypeLabel(): string
    {
        return config('hrms.recruitment.participant_types.'.$this->participant_type, $this->participant_type);
    }

    public function roleLabel(): string
    {
        return config('hrms.recruitment.participant_roles.'.$this->role, $this->role);
    }
}
